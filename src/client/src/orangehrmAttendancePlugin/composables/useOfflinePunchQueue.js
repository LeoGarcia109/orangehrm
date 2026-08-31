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

/**
 * BR: punches taken without signal, waiting to reach the server.
 *
 * The queue keeps the time and the coordinates of the moment the button was
 * pressed -- not of the moment the sync happens -- because that is the punch
 * that actually occurred. The server checks those against the offline window
 * and marks the record, so a punch it never witnessed live stays visible as
 * such in the audit trail.
 */

const STORAGE_KEY = 'ohrm-br-offline-punches';

function read() {
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed : [];
  } catch (e) {
    // Private mode, storage disabled, or corrupted content: an unusable queue
    // must not take the punch screen down with it.
    return [];
  }
}

function write(items) {
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    return true;
  } catch (e) {
    return false;
  }
}

/**
 * Whether the request never reached the server, as opposed to reaching it and
 * being refused. Only the former is worth keeping: a punch the server said no
 * to would be refused again on every retry, forever, blocking every punch
 * queued behind it.
 */
export function isUndelivered(error) {
  if (window.navigator && window.navigator.onLine === false) return true;
  if (!error) return false;
  const status =
    error.status ?? error.response?.status ?? error.data?.error?.status ?? null;
  return status === null || status === undefined || status === 0;
}

export default function useOfflinePunchQueue() {
  const list = () => read();

  const size = () => read().length;

  /**
   * @param {string} method POST for punch-in, PUT for punch-out
   * @param {object} payload the body the online punch would have sent
   */
  const enqueue = (method, payload) => {
    const items = read();
    const item = {
      id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
      method,
      payload: {...payload, offlineSync: true},
      queuedAt: new Date().toISOString(),
    };
    items.push(item);
    return write(items) ? item : null;
  };

  const remove = (id) => {
    write(read().filter((item) => item.id !== id));
  };

  const clear = () => write([]);

  /**
   * Replay the queue oldest first.
   *
   * Order is not a nicety: a punch-out replayed before its punch-in is refused
   * as having no open record. So the drain stops at the first item that could
   * not be delivered, leaving it and everything after it for the next attempt.
   *
   * @param {(item: object) => Promise} send delivers one item
   * @returns {Promise<{synced: number, rejected: Array, pending: number}>}
   */
  const flush = async (send) => {
    const items = read();
    const rejected = [];
    let synced = 0;

    for (const item of items) {
      try {
        await send(item);
        remove(item.id);
        synced += 1;
      } catch (error) {
        if (isUndelivered(error)) {
          break;
        }
        remove(item.id);
        rejected.push({item, error});
      }
    }

    return {synced, rejected, pending: read().length};
  };

  return {list, size, enqueue, remove, clear, flush};
}
