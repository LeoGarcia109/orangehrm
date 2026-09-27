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

import BrJobProfile from '../BrJobProfile.vue';

/**
 * The editor of a job title's ideal profile.
 */
describe('BrJobProfile', () => {
  const factors = [
    ...['E', 'A', 'C', 'N', 'O'].map((f) => ({
      instrument: 'BIG5',
      factor: f,
      min: 0,
      max: 100,
      weight: 1,
    })),
    ...['D', 'I', 'S', 'C'].map((f) => ({
      instrument: 'DISC',
      factor: f,
      min: 0,
      max: 100,
      weight: 1,
    })),
  ];
  const profile = {
    behaviorWeight: 50,
    factors,
    competencies: [
      {id: 7, name: 'Atendimento', weight: 2, minLevel: 4},
      {id: 8, name: 'Caixa', weight: 1, minLevel: 3},
    ],
    exists: true,
    jobTitle: {id: 5, name: 'Frentista'},
  };

  const toast = {saveSuccess: jest.fn()};
  const mountIt = async () => {
    mockHttp.mockReset();
    mockHttp.mockImplementation((path, options) => {
      if (path === '/api/v2/attendance/br/job-profiles/5') {
        return Promise.resolve({
          data: {
            data:
              options.method === 'PUT'
                ? {...profile, ...options.data}
                : profile,
          },
        });
      }
      if (path === '/api/v2/attendance/br/profile-people') {
        return Promise.resolve({
          data: {
            data: [
              {key: 'e2', type: 'e', id: 2, name: 'Bruno', subunit: 'Posto'},
            ],
          },
        });
      }
      if (path === '/api/v2/attendance/br/job-profile-suggestion') {
        return Promise.resolve({
          data: {
            data: factors.map((f) => ({...f, min: 40, max: 70})),
          },
        });
      }
      return Promise.resolve({data: {data: []}});
    });
    window.appGlobal = {baseUrl: ''};
    const wrapper = mount(BrJobProfile, {
      props: {jobTitleId: 5},
      global: {
        mocks: {$t: (k) => k, $toast: toast},
        stubs: {'oxd-text': {template: '<h6><slot /></h6>'}},
      },
    });
    await flushPromises();
    return wrapper;
  };
  const puts = () =>
    mockHttp.mock.calls.filter(([, o]) => o && o.method === 'PUT');

  it('loads the nine factors and the competencies', async () => {
    const wrapper = await mountIt();

    expect(wrapper.find('.ohrm-jobfit__job-name').text()).toBe('Frentista');
    expect(wrapper.findAll('.ohrm-jobfit__factor')).toHaveLength(9);
    expect(wrapper.findAll('.ohrm-jobfit__competency')).toHaveLength(2);
  });

  it('saves the importance and keeps competency ids', async () => {
    const wrapper = await mountIt();
    await wrapper
      .findAll('.ohrm-jobfit__factor')[2]
      .find('button[data-weight="2"]')
      .trigger('click');
    await wrapper.find('.ohrm-jobfit__save').trigger('click');
    await flushPromises();

    const [[, {data}]] = puts();
    expect(data.factors[2]).toEqual({
      instrument: 'BIG5',
      factor: 'C',
      min: 0,
      max: 100,
      weight: 2,
    });
    expect(data.competencies[0]).toEqual({
      id: 7,
      name: 'Atendimento',
      weight: 2,
      minLevel: 4,
    });
    expect(toast.saveSuccess).toHaveBeenCalled();
  });

  it('drops a removed competency from what is saved', async () => {
    const wrapper = await mountIt();
    await wrapper
      .findAll('.ohrm-jobfit__competency')[0]
      .find('.ohrm-jobfit__remove')
      .trigger('click');
    await wrapper.find('.ohrm-jobfit__save').trigger('click');
    await flushPromises();

    expect(puts()[0][1].data.competencies.map((c) => c.id)).toEqual([8]);
  });

  it('applies suggested ranges without saving', async () => {
    const wrapper = await mountIt();
    await wrapper.find('.ohrm-jobfit__suggest').trigger('click');
    await flushPromises();
    await wrapper.find('.ohrm-jobfit__ref input').setValue(true);
    await wrapper.find('.ohrm-jobfit__apply').trigger('click');
    await flushPromises();

    expect(mockHttp).toHaveBeenCalledWith(
      '/api/v2/attendance/br/job-profile-suggestion',
      expect.objectContaining({params: {empNumbers: '2'}}),
    );
    expect(wrapper.vm.profile.factors[0]).toEqual({
      instrument: 'BIG5',
      factor: 'E',
      min: 40,
      max: 70,
      weight: 1,
    });
    expect(puts()).toHaveLength(0);
    expect(wrapper.find('.ohrm-jobfit__notice').text()).toBe(
      'attendance.jobfit_applied',
    );
  });

  it('asks for a reference before suggesting', async () => {
    const wrapper = await mountIt();
    await wrapper.find('.ohrm-jobfit__suggest').trigger('click');
    await flushPromises();
    await wrapper.find('.ohrm-jobfit__apply').trigger('click');

    expect(wrapper.find('.ohrm-jobfit__suggest-error').text()).toBe(
      'attendance.jobfit_no_reference',
    );
  });

  it('shows the reason when saving is refused', async () => {
    const wrapper = await mountIt();
    mockHttp.mockImplementationOnce(() =>
      Promise.reject({
        response: {data: {error: {message: 'Competencia repetida: Caixa.'}}},
      }),
    );
    await wrapper.find('.ohrm-jobfit__save').trigger('click');
    await flushPromises();

    expect(wrapper.find('.ohrm-builder__error').text()).toBe(
      'Competencia repetida: Caixa.',
    );
  });
});
