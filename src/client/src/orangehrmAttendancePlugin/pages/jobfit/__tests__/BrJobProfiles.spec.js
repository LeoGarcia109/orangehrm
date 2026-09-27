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

import BrJobProfiles from '../BrJobProfiles.vue';

/**
 * The job titles and whether each already has its ideal profile.
 */
describe('BrJobProfiles', () => {
  const rows = [
    {id: 3, name: 'Caixa', hasProfile: false, competencies: 0, updatedAt: null},
    {
      id: 5,
      name: 'Frentista',
      hasProfile: true,
      competencies: 2,
      updatedAt: '2026-09-27 10:00',
    },
  ];

  const mountIt = async (data = rows) => {
    mockHttp.mockReset();
    mockHttp.mockResolvedValue({data: {data}});
    window.appGlobal = {baseUrl: '/web/index.php'};
    const wrapper = mount(BrJobProfiles, {
      global: {
        mocks: {$t: (k) => k},
        stubs: {'oxd-text': {template: '<h6><slot /></h6>'}},
      },
    });
    await flushPromises();
    return wrapper;
  };

  it('lists the job titles with their state', async () => {
    const wrapper = await mountIt();
    const lines = wrapper.findAll('tbody tr');

    expect(mockHttp).toHaveBeenCalledWith(
      '/api/v2/attendance/br/job-profiles',
      expect.objectContaining({method: 'GET'}),
    );
    expect(lines).toHaveLength(2);
    expect(lines[0].find('.ohrm-jobfit__chip').classes()).toContain('is-off');
    expect(lines[1].find('.ohrm-jobfit__chip').classes()).toContain('is-on');
    expect(lines[1].text()).toContain('27/09/2026 10:00');
  });

  it('opens the editor of a job title', async () => {
    const wrapper = await mountIt();
    await wrapper.findAll('.ohrm-jobfit__edit')[1].trigger('click');

    expect(mockNavigate).toHaveBeenCalledWith('/recruitment/brJobProfile/5');
  });

  it('sends HR to Admin to create job titles', async () => {
    const wrapper = await mountIt([]);
    window.open = jest.fn();

    expect(wrapper.find('.ohrm-forms__empty').text()).toBe(
      'attendance.jobfit_no_job_titles',
    );
    await wrapper.find('.ohrm-jobfit__new').trigger('click');
    expect(window.open).toHaveBeenCalledWith(
      '/web/index.php/admin/viewJobTitleList',
      '_blank',
      'noopener',
    );
  });
});
