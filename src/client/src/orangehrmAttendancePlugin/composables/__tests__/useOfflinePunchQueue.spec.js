/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

import useOfflinePunchQueue, {
  isUndelivered,
} from '../useOfflinePunchQueue';

/**
 * The queue holds punches taken without signal until they reach the server.
 *
 * What makes it worth having is that it keeps the moment the button was
 * pressed, not the moment the sync happened -- that is the punch that actually
 * occurred, and it is what the geofence and the timesheet have to see.
 */
describe('useOfflinePunchQueue', () => {
  const payload = () => ({
    date: '2026-08-28',
    time: '08:00',
    latitude: -14.676963,
    longitude: -39.377871,
    timezoneOffset: -3,
    timezoneName: 'America/Sao_Paulo',
    note: null,
  });

  beforeEach(() => {
    window.localStorage.clear();
  });

  it('keeps a punch that could not be sent', () => {
    const queue = useOfflinePunchQueue();

    queue.enqueue('POST', payload());

    expect(queue.size()).toBe(1);
  });

  it('survives a reload, because the punch would otherwise be lost', () => {
    useOfflinePunchQueue().enqueue('POST', payload());

    expect(useOfflinePunchQueue().size()).toBe(1);
  });

  it('keeps the time and place of the punch, not of the sync', () => {
    const queue = useOfflinePunchQueue();

    queue.enqueue('POST', payload());

    expect(queue.list()[0].payload).toMatchObject({
      date: '2026-08-28',
      time: '08:00',
      latitude: -14.676963,
      longitude: -39.377871,
    });
  });

  it('tells the server the punch came from the queue', () => {
    const queue = useOfflinePunchQueue();

    queue.enqueue('POST', payload());

    expect(queue.list()[0].payload.offlineSync).toBe(true);
  });

  it('remembers whether the punch was an in or an out', () => {
    const queue = useOfflinePunchQueue();

    queue.enqueue('POST', payload());
    queue.enqueue('PUT', payload());

    expect(queue.list().map((item) => item.method)).toEqual([
      'POST',
      'PUT',
    ]);
  });

  /**
   * A punch-out replayed before its punch-in is refused as having no open
   * record, so order is not a nicety.
   */
  it('drains oldest first', async () => {
    const queue = useOfflinePunchQueue();
    queue.enqueue('POST', {...payload(), time: '08:00'});
    queue.enqueue('PUT', {...payload(), time: '17:00'});
    const sent = [];

    await queue.flush(async (item) => {
      sent.push(item.payload.time);
    });

    expect(sent).toEqual(['08:00', '17:00']);
    expect(queue.size()).toBe(0);
  });

  it('stops at the first punch it still cannot deliver, keeping the rest', async () => {
    const queue = useOfflinePunchQueue();
    queue.enqueue('POST', {...payload(), time: '08:00'});
    queue.enqueue('PUT', {...payload(), time: '17:00'});

    const result = await queue.flush(async (item) => {
      if (item.payload.time === '08:00') return;
      throw {status: 0};
    });

    expect(result.synced).toBe(1);
    expect(result.pending).toBe(1);
    expect(queue.list()[0].payload.time).toBe('17:00');
  });

  /**
   * A punch the server refused would be refused again on every retry. Keeping
   * it would block every later punch behind it, forever.
   */
  it('drops a punch the server answered and refused, and reports it', async () => {
    const queue = useOfflinePunchQueue();
    queue.enqueue('POST', payload());

    const result = await queue.flush(async () => {
      throw {status: 422};
    });

    expect(result.synced).toBe(0);
    expect(result.pending).toBe(0);
    expect(result.rejected).toHaveLength(1);
  });

  it('forgets a punch once it lands', async () => {
    const queue = useOfflinePunchQueue();
    queue.enqueue('POST', payload());

    await queue.flush(async () => undefined);

    expect(queue.size()).toBe(0);
  });

  describe('isUndelivered', () => {
    it('treats an answer from the server as delivered', () => {
      expect(isUndelivered({status: 422})).toBe(false);
    });

    it('treats a request that never got an answer as undelivered', () => {
      expect(isUndelivered({status: 0})).toBe(true);
      expect(isUndelivered({})).toBe(true);
    });

    it('reads the status out of an axios-shaped error', () => {
      expect(isUndelivered({response: {status: 400}})).toBe(false);
    });
  });
});
