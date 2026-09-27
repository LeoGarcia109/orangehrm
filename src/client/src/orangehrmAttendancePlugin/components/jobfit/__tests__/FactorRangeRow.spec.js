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
import FactorRangeRow from '../FactorRangeRow.vue';

/**
 * One factor of a job profile: its range on 0-100 and its importance.
 */
describe('FactorRangeRow', () => {
  const mountIt = (value) =>
    mount(FactorRangeRow, {
      props: {label: 'Conscienciosidade', modelValue: value},
      global: {mocks: {$t: (k) => k}},
    });
  const last = (wrapper) =>
    wrapper.emitted('update:modelValue').slice(-1)[0][0];

  it('paints the range on the track', () => {
    const wrapper = mountIt({min: 60, max: 90, weight: 2});
    const style = wrapper.find('.ohrm-jobfit__range').attributes('style');

    expect(style).toContain('left: 60%');
    expect(style).toContain('width: 30%');
  });

  it('snaps an edge to fives', async () => {
    const wrapper = mountIt({min: 60, max: 90, weight: 1});
    const input = wrapper.find('input[data-edge="min"]');
    input.element.value = '52';
    await input.trigger('change');

    expect(last(wrapper)).toEqual({min: 50, max: 90, weight: 1});
  });

  it('swaps the edges when the minimum passes the maximum', async () => {
    const wrapper = mountIt({min: 60, max: 70, weight: 1});
    const input = wrapper.find('input[data-edge="min"]');
    input.element.value = '85';
    await input.trigger('change');

    expect(last(wrapper)).toEqual({min: 70, max: 85, weight: 1});
  });

  it('sets the importance and fades an ignored factor', async () => {
    const wrapper = mountIt({min: 0, max: 100, weight: 0});
    expect(wrapper.classes()).toContain('is-muted');

    await wrapper.find('button[data-weight="2"]').trigger('click');
    expect(last(wrapper)).toEqual({min: 0, max: 100, weight: 2});
  });
});
