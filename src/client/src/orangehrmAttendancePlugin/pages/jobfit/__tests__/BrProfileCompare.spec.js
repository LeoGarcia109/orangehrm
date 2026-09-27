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

import BrProfileCompare from '../BrProfileCompare.vue';
import {many, result} from '../../../components/jobfit/__fixtures__/comparison';

/**
 * The comparison page: job title, people, and the three views over them.
 */
describe('BrProfileCompare', () => {
  const jobs = [
    {id: 5, name: 'Frentista', hasProfile: true},
    {id: 6, name: 'Caixa', hasProfile: false},
  ];
  const mountIt = async (props, comparison = result) => {
    mockHttp.mockReset();
    mockHttp.mockImplementation((path) => {
      if (path.endsWith('job-profiles')) {
        return Promise.resolve({data: {data: jobs}});
      }
      if (path.endsWith('profile-comparison')) {
        return Promise.resolve({data: {data: comparison}});
      }
      return Promise.resolve({data: {data: []}});
    });
    window.appGlobal = {baseUrl: '/web/index.php'};
    window.history.replaceState = jest.fn();
    const wrapper = mount(BrProfileCompare, {
      props: {jobTitleId: null, subjects: '', vacancyId: null, ...props},
      global: {
        mocks: {$t: (k) => k},
        stubs: {
          'oxd-text': {template: '<h6><slot /></h6>'},
          'people-picker': {
            name: 'PeoplePicker',
            props: ['modelValue', 'vacancyId'],
            template: '<div class="picker-stub" />',
          },
          'compare-table': {
            name: 'CompareTable',
            props: ['result', 'shown', 'colors'],
            template: '<div class="table-stub" />',
          },
          'duel-view': {
            name: 'DuelView',
            props: ['result'],
            template: '<div class="duel-stub" />',
          },
        },
      },
    });
    await flushPromises();
    return wrapper;
  };
  const comparisonCalls = () =>
    mockHttp.mock.calls.filter(([p]) => p.endsWith('profile-comparison'));

  it('compares what the address asks for and keeps it in the address', async () => {
    await mountIt({jobTitleId: 5, subjects: 'c1,e2'});

    expect(comparisonCalls()[0][1].params).toEqual({
      jobTitleId: 5,
      subjects: 'c1,e2',
    });
    const url = window.history.replaceState.mock.calls.slice(-1)[0][2];
    expect(url).toBe(
      '/web/index.php/recruitment/brProfileCompare?jobTitleId=5&subjects=c1%2Ce2',
    );
  });

  it('draws both radars with everyone and the job range', async () => {
    const wrapper = await mountIt({jobTitleId: 5, subjects: 'c1,e2'});
    const radars = wrapper.findAllComponents({name: 'RadarChart'});

    expect(radars).toHaveLength(2);
    expect(radars[0].props('series')).toHaveLength(2);
    expect(radars[0].props('band')[2]).toEqual({min: 60, max: 90});
    expect(radars[0].props('band')[4]).toBeNull();
    expect(radars[0].props('series')[0].values).toEqual([70, 78, 82, 66, 55]);
  });

  it('puts the first six of the ranking on the chart', async () => {
    const people = many(7);
    const wrapper = await mountIt(
      {jobTitleId: 5, subjects: people.map((p) => p.key).join(',')},
      {...result, people},
    );

    expect(
      wrapper.findAllComponents({name: 'RadarChart'})[0].props('series'),
    ).toHaveLength(6);
  });

  it('asks for the profile of a job title that has none', async () => {
    const wrapper = await mountIt({jobTitleId: 6, subjects: 'c1'});

    expect(comparisonCalls()).toHaveLength(0);
    expect(wrapper.find('.ohrm-compare__define').exists()).toBe(true);
  });

  it('switches to the duel', async () => {
    const wrapper = await mountIt({jobTitleId: 5, subjects: 'c1,e2'});
    await wrapper.find('button[data-tab="duel"]').trigger('click');

    expect(wrapper.find('.duel-stub').exists()).toBe(true);
    expect(wrapper.findAllComponents({name: 'RadarChart'})).toHaveLength(0);
  });

  it('prints with the job title in the file name', async () => {
    const wrapper = await mountIt({jobTitleId: 5, subjects: 'c1,e2'});
    window.print = jest.fn();
    await wrapper.find('.ohrm-compare__pdf').trigger('click');

    expect(window.print).toHaveBeenCalled();
    expect(document.title).toBe('attendance.jobfit_comparison - Frentista');
  });
});
