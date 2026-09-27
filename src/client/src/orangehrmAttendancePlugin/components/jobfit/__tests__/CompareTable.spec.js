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

import {flushPromises, mount} from '@vue/test-utils';

// Every call lands here as (path, {method, params, data})
const mockHttp = jest.fn();
const mockNavigate = jest.fn();
jest.mock('@ohrm/core/util/services/api.service', () => ({
  APIService: class {
    constructor(base, path) {
      this.path = path;
    }
    getAll(params) {
      return mockHttp(this.path, {method: 'GET', params});
    }
    get(id, params) {
      return mockHttp(`${this.path}/${id}`, {method: 'GET', params});
    }
    update(id, data) {
      return mockHttp(`${this.path}/${id}`, {method: 'PUT', data});
    }
    request(options) {
      return mockHttp(options.url ?? this.path, options);
    }
  },
}));
jest.mock('@ohrm/core/util/helper/navigation', () => ({
  navigate: (...a) => mockNavigate(...a),
}));

import CompareTable from '../CompareTable.vue';
import {many, result} from '../__fixtures__/comparison';

/**
 * The ranking and the factor-by-factor table, where HR also rates the
 * competencies.
 */
describe('CompareTable', () => {
  const mountIt = (props = {}) => {
    mockHttp.mockReset();
    mockHttp.mockResolvedValue({data: {data: {}}});
    window.appGlobal = {baseUrl: ''};
    return mount(CompareTable, {
      props: {
        result,
        shown: ['c1', 'e2'],
        colors: {c1: '#1D9E75', e2: '#D85A30'},
        ...props,
      },
      global: {mocks: {$t: (k) => k}},
    });
  };

  it('ranks people as the server ordered them, with their flags', () => {
    const rows = mountIt().findAll('.ohrm-compare__rank-row');

    expect(rows.map((r) => r.find('.ohrm-compare__name').text())).toEqual([
      'Ana Souza',
      'Bruno Lima',
    ]);
    expect(rows[0].find('.ohrm-compare__overall').text()).toBe('88%');
    expect(rows[1].find('.ohrm-jobfit__chip.is-partial').exists()).toBe(true);
    expect(rows[1].find('.ohrm-jobfit__chip.is-alert').text()).toBe(
      'attendance.jobfit_alert_factor',
    );
  });

  it('colours each score by its distance to the range', () => {
    const wrapper = mountIt();

    expect(wrapper.find('[data-cell="e2-BIG5-E"]').classes()).toContain(
      'is-near',
    );
    expect(wrapper.find('[data-cell="c1-BIG5-C"]').classes()).toContain(
      'is-in',
    );
    expect(wrapper.find('[data-cell="c1-BIG5-O"]').classes()).toContain(
      'is-ignored',
    );
    expect(wrapper.find('[data-factor="BIG5-C"]').text()).toContain('★');
  });

  it('keeps at most six people on the chart', () => {
    const people = many(7);
    const wrapper = mountIt({
      result: {...result, people},
      shown: people.slice(0, 6).map((p) => p.key),
      colors: {},
    });
    const boxes = wrapper.findAll('.ohrm-compare__show input');

    expect(boxes[5].element.disabled).toBe(false);
    expect(boxes[6].element.disabled).toBe(true);
  });

  it('asks the page to show or hide someone', async () => {
    const wrapper = mountIt();
    await wrapper.findAll('.ohrm-compare__show input')[1].trigger('change');

    expect(wrapper.emitted('toggle-shown')[0]).toEqual(['e2']);
  });

  it('saves a rating and tells the page', async () => {
    const wrapper = mountIt();
    await wrapper.find('select[data-rating="e2-7"]').setValue('3');
    await flushPromises();

    expect(mockHttp).toHaveBeenCalledWith(
      '/api/v2/attendance/br/competency-ratings/7',
      {method: 'PUT', data: {subject: 'e2', rating: 3}},
    );
    expect(wrapper.emitted('rated')).toHaveLength(1);
  });

  it('clears a rating', async () => {
    const wrapper = mountIt();
    await wrapper.find('select[data-rating="c1-7"]').setValue('');
    await flushPromises();

    expect(mockHttp).toHaveBeenCalledWith(
      '/api/v2/attendance/br/competency-ratings/7',
      {method: 'PUT', data: {subject: 'c1'}},
    );
    expect(wrapper.find('select[data-rating="c1-7"]').exists()).toBe(true);
  });
});
