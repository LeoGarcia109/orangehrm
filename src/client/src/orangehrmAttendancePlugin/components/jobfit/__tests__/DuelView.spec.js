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

import {mount} from '@vue/test-utils';
import DuelView from '../DuelView.vue';
import {result} from '../__fixtures__/comparison';

/**
 * X against Y in RPG bars, over the job's range: the arrow goes to whoever is
 * closer to the job, not to the bigger bar.
 */
describe('DuelView', () => {
  const mountIt = () =>
    mount(DuelView, {
      props: {result},
      global: {mocks: {$t: (k) => k}},
    });
  const row = (wrapper, key) => wrapper.find(`[data-row="${key}"]`);

  it('starts with the first two of the ranking', () => {
    const wrapper = mountIt();
    const names = wrapper.findAll('.ohrm-duel__name').map((n) => n.text());

    expect(names).toEqual(['Ana Souza', 'Bruno Lima']);
    expect(
      wrapper.find('.ohrm-duel__hp--a .ohrm-duel__hp-fill').attributes('style'),
    ).toContain('width: 87.5%');
  });

  it('lights one block per ten points', () => {
    const wrapper = mountIt();

    expect(
      row(wrapper, 'BIG5-C').findAll('.ohrm-duel__side--a .is-on'),
    ).toHaveLength(8);
    expect(
      row(wrapper, 'BIG5-C').findAll('.ohrm-duel__side--b .is-on'),
    ).toHaveLength(6);
  });

  it('frames the job range on both sides, mirrored for X', () => {
    const wrapper = mountIt();
    const a = row(wrapper, 'BIG5-C')
      .find('.ohrm-duel__side--a .ohrm-duel__band')
      .attributes('style');
    const b = row(wrapper, 'BIG5-C')
      .find('.ohrm-duel__side--b .ohrm-duel__band')
      .attributes('style');

    expect(a).toContain('left: 10%');
    expect(b).toContain('left: 60%');
    expect(b).toContain('width: 30%');
  });

  it('gives the line to the one inside the range, not the bigger bar', () => {
    const wrapper = mountIt();

    expect(row(wrapper, 'BIG5-E').find('.ohrm-duel__win--a').exists()).toBe(
      true,
    );
    expect(row(wrapper, 'BIG5-E').find('.ohrm-duel__win--b').exists()).toBe(
      false,
    );
  });

  it('no arrow on a tie or an ignored factor', () => {
    const wrapper = mountIt();

    expect(row(wrapper, 'DISC-I').find('.ohrm-duel__win').exists()).toBe(false);
    expect(row(wrapper, 'BIG5-O').find('.ohrm-duel__win').exists()).toBe(false);
    expect(row(wrapper, 'BIG5-O').find('.ohrm-duel__band').exists()).toBe(
      false,
    );
  });

  it('shows competencies as stars, no arrow without both ratings', () => {
    const wrapper = mountIt();
    const line = row(wrapper, 'comp-7');

    expect(line.findAll('.ohrm-duel__side--a .bi-star-fill')).toHaveLength(4);
    expect(line.findAll('.ohrm-duel__side--b .bi-star-fill')).toHaveLength(0);
    expect(line.find('.ohrm-duel__win').exists()).toBe(false);
  });

  it('swaps who is on each side', async () => {
    const wrapper = mountIt();
    await wrapper.find('select[data-side="a"]').setValue('e2');
    await wrapper.find('select[data-side="b"]').setValue('c1');

    expect(wrapper.findAll('.ohrm-duel__name').map((n) => n.text())).toEqual([
      'Bruno Lima',
      'Ana Souza',
    ]);
    expect(row(wrapper, 'BIG5-E').find('.ohrm-duel__win--b').exists()).toBe(
      true,
    );
  });
});
