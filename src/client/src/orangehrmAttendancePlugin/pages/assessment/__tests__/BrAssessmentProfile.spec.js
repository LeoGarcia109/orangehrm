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

const mockRequest = jest.fn();
jest.mock('@ohrm/core/util/services/api.service', () => ({
  APIService: class {
    request(...a) {
      return mockRequest(...a);
    }
  },
}));
jest.mock('@ohrm/core/util/helper/navigation', () => ({navigate: jest.fn()}));

import BrAssessmentProfile from '../BrAssessmentProfile.vue';

/**
 * The profile HR reads.
 */
describe('BrAssessmentProfile', () => {
  const profile = {
    id: 2,
    subjectType: 'CANDIDATE',
    name: 'Maria Souza',
    vacancyName: 'Frentista',
    completedAt: '2026-09-27 11:00',
    instruments: {
      BIG5: {
        version: 'IPIP50-PT-v1',
        factors: [
          {factor: 'E', raw: 40, score: 75.0, band: 'HIGH'},
          {factor: 'A', raw: 30, score: 50.0, band: 'MID'},
          {factor: 'C', raw: 45, score: 87.5, band: 'HIGH'},
          {factor: 'N', raw: 18, score: 20.0, band: 'LOW'},
          {factor: 'O', raw: 30, score: 50.0, band: 'MID'},
        ],
      },
      DISC: {
        version: 'DISC-HRR-v1',
        factors: [
          {factor: 'D', raw: 12, score: 25.0, band: 'LOW'},
          {factor: 'I', raw: 20, score: 58.3, band: 'MID'},
          {factor: 'S', raw: 27, score: 87.5, band: 'HIGH'},
          {factor: 'C', raw: 24, score: 75.0, band: 'HIGH'},
        ],
        styles: {primary: 'S', secondary: 'C'},
      },
    },
  };

  const mountIt = async () => {
    mockRequest.mockResolvedValue({data: {data: profile}});
    window.appGlobal = {baseUrl: ''};
    const wrapper = mount(BrAssessmentProfile, {
      props: {assessmentId: 2},
      global: {
        mocks: {$t: (k) => k},
        stubs: {'oxd-text': {template: '<h6><slot /></h6>'}},
      },
    });
    await flushPromises();
    return wrapper;
  };

  it('asks for the right profile', async () => {
    await mountIt();

    expect(mockRequest).toHaveBeenCalledWith(
      expect.objectContaining({params: {assessmentId: 2}}),
    );
  });

  it('draws the five Big Five factors and the four DISC styles', async () => {
    const wrapper = await mountIt();

    expect(
      wrapper.findAll('.ohrm-profile__big5 .ohrm-profile__bar'),
    ).toHaveLength(5);
    expect(
      wrapper.findAll('.ohrm-profile__disc .ohrm-profile__bar'),
    ).toHaveLength(4);
    expect(
      wrapper
        .find('.ohrm-profile__big5 .ohrm-profile__bar-fill')
        .attributes('style'),
    ).toContain('width: 75%');
  });

  it('describes each factor from the side the score leans to', async () => {
    const text = (await mountIt()).text();

    expect(text).toContain('attendance.assessment_f_big5_e_high');
    expect(text).toContain('attendance.assessment_f_big5_n_low');
  });

  it('names the predominant and secondary DISC styles', async () => {
    const styles = (await mountIt()).find('.ohrm-profile__styles').text();

    expect(styles).toContain('attendance.assessment_f_disc_s');
    expect(styles).toContain('attendance.assessment_f_disc_c');
  });

});
