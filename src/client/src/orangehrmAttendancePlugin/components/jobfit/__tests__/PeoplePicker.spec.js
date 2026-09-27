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

import PeoplePicker from '../PeoplePicker.vue';

/**
 * Choosing who to compare: filters, select all up to twenty, and the job
 * title of a vacancy handed to the page.
 */
describe('PeoplePicker', () => {
  const person = (i, type = 'c') => ({
    key: `${type}${i}`,
    type,
    id: i,
    name: `Pessoa ${i}`,
    subunit: type === 'e' ? 'Posto' : null,
    vacancies: [],
  });
  const mountIt = async (props = {}, people = [person(1), person(2, 'e')]) => {
    mockHttp.mockReset();
    mockHttp.mockImplementation((path) =>
      Promise.resolve({
        data: {
          data: path.endsWith('profile-people') ? people : [],
          meta: {jobTitleId: path.endsWith('profile-people') ? 5 : null},
        },
      }),
    );
    window.appGlobal = {baseUrl: ''};
    const wrapper = mount(PeoplePicker, {
      props: {modelValue: [], vacancyId: null, ...props},
      global: {mocks: {$t: (k) => k}},
    });
    await flushPromises();
    return wrapper;
  };
  const peopleCalls = () =>
    mockHttp.mock.calls.filter(([p]) => p.endsWith('profile-people'));

  it('lists who can be compared', async () => {
    const wrapper = await mountIt();

    expect(peopleCalls()[0][1].params).toEqual({});
    expect(wrapper.findAll('.ohrm-picker__person')).toHaveLength(2);
  });

  it('filters by vacancy and hands the job title over', async () => {
    const wrapper = await mountIt({vacancyId: 3});

    expect(peopleCalls()[0][1].params).toEqual({vacancyId: 3});
    expect(wrapper.emitted('vacancy-job-title')[0]).toEqual([5]);
  });

  it('marks a person', async () => {
    const wrapper = await mountIt();
    await wrapper.findAll('.ohrm-picker__person input')[1].setValue(true);

    expect(wrapper.emitted('update:modelValue')[0]).toEqual([['e2']]);
  });

  it('selects everyone shown, up to twenty', async () => {
    const people = Array.from({length: 25}, (_, i) => person(i + 1));
    const wrapper = await mountIt({modelValue: ['e99']}, people);
    await wrapper.find('.ohrm-picker__all').trigger('click');

    const [selected] = wrapper.emitted('update:modelValue')[0];
    expect(selected).toHaveLength(20);
    expect(selected[0]).toBe('e99');
  });

  it('searches by name after a pause', async () => {
    jest.useFakeTimers();
    const wrapper = await mountIt();
    await wrapper.find('.ohrm-picker__name').setValue('ana');
    expect(peopleCalls()).toHaveLength(1);

    jest.advanceTimersByTime(300);
    await flushPromises();
    expect(peopleCalls()[1][1].params).toEqual({name: 'ana'});
    jest.useRealTimers();
  });

  it('clears the selection', async () => {
    const wrapper = await mountIt({modelValue: ['c1']});
    await wrapper.find('.ohrm-picker__clear').trigger('click');

    expect(wrapper.emitted('update:modelValue')[0]).toEqual([[]]);
  });
});
