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
const mockCreate = jest.fn();
const mockUpdate = jest.fn();
const mockGetAll = jest.fn();
const mockNavigate = jest.fn();

jest.mock('@ohrm/core/util/services/api.service', () => ({
  APIService: class {
    setIgnorePath() {
      return undefined;
    }
    request(...a) {
      return mockRequest(...a);
    }
    create(...a) {
      return mockCreate(...a);
    }
    update(...a) {
      return mockUpdate(...a);
    }
    getAll(...a) {
      return mockGetAll(...a);
    }
  },
}));
jest.mock('@/core/components/inputs/EmployeeAutocomplete', () => ({
  name: 'EmployeeAutocomplete',
  template: '<div />',
}));
jest.mock(
  '@/orangehrmRecruitmentPlugin/components/CandidateAutocomplete.vue',
  () => ({
    name: 'CandidateAutocomplete',
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<div class="candidate-stub" />',
  }),
);
jest.mock('@ohrm/core/util/helper/navigation', () => ({
  navigate: (...a) => mockNavigate(...a),
}));

import BrAssessments from '../BrAssessments.vue';

/**
 * HR's list of behavioural-profile invites: sending one, getting the link to
 * the candidate, and reaching the profile once it is answered.
 */
describe('BrAssessments', () => {
  const items = [
    {
      id: 1,
      subjectType: 'CANDIDATE',
      name: 'Maria Souza',
      phone: '(73) 98888-7777',
      vacancyName: 'Frentista',
      status: 'PENDING',
      createdAt: '2026-09-27 10:00',
      completedAt: null,
      hasLink: true,
    },
    {
      id: 2,
      subjectType: 'EMPLOYEE',
      name: 'Leo Garcia',
      phone: null,
      vacancyName: null,
      status: 'COMPLETED',
      createdAt: '2026-09-26 10:00',
      completedAt: '2026-09-26 11:00',
      hasLink: false,
    },
  ];
  const linkReply = {
    data: {
      data: {
        id: 1,
        path: '/recruitmentApply/assessment/TOKEN',
        phone: '(73) 98888-7777',
        firstName: 'Maria',
      },
    },
  };

  const mountIt = async () => {
    window.appGlobal = {baseUrl: '/web/index.php'};
    const wrapper = mount(BrAssessments, {
      global: {
        mocks: {$t: (k) => k, $toast: {saveSuccess: () => Promise.resolve()}},
        stubs: {'oxd-text': {template: '<h6><slot /></h6>'}},
      },
    });
    await flushPromises();
    return wrapper;
  };

  beforeEach(() => {
    [mockRequest, mockCreate, mockUpdate, mockGetAll, mockNavigate].forEach(
      (m) => m.mockReset(),
    );
    mockRequest.mockResolvedValue({data: {data: items}});
    mockGetAll.mockResolvedValue({data: {data: []}});
    mockCreate.mockResolvedValue(linkReply);
    mockUpdate.mockResolvedValue(linkReply);
  });

  it('lists invites with their status', async () => {
    const rows = (await mountIt()).findAll('tbody tr');

    expect(rows[0].text()).toContain('Maria Souza');
    expect(rows[0].text()).toContain('attendance.assessment_status_pending');
    expect(rows[1].text()).toContain('attendance.assessment_status_completed');
  });

  it('opens the profile of an answered invite', async () => {
    const wrapper = await mountIt();

    await wrapper
      .findAll('tbody tr')[1]
      .find('.ohrm-assessments__view')
      .trigger('click');

    expect(mockNavigate).toHaveBeenCalledWith(
      '/recruitment/brAssessmentProfile/{id}',
      {id: 2},
    );
  });

  it('invites a candidate and hands over the link, with WhatsApp ready', async () => {
    const wrapper = await mountIt();
    await wrapper.find('.ohrm-assessments__new').trigger('click');
    wrapper
      .findComponent({name: 'CandidateAutocomplete'})
      .vm.$emit('update:modelValue', {id: 5, label: 'Maria Souza'});
    await flushPromises();

    await wrapper.find('.ohrm-assessments__send').trigger('click');
    await flushPromises();

    expect(mockCreate).toHaveBeenCalledWith({candidateId: 5});
    const link =
      'http://localhost/web/index.php/recruitmentApply/assessment/TOKEN';
    expect(wrapper.find('.ohrm-assessments__link input').element.value).toBe(
      link,
    );
    const whatsapp = wrapper
      .find('.ohrm-assessments__whatsapp')
      .attributes('href');
    expect(whatsapp.startsWith('https://wa.me/5573988887777?text=')).toBe(true);
    expect(decodeURIComponent(whatsapp.split('text=')[1])).toContain(link);
  });

  it('invites employees by audience', async () => {
    mockCreate.mockResolvedValue({data: {data: {created: 3}}});
    const wrapper = await mountIt();
    await wrapper.find('.ohrm-assessments__new').trigger('click');
    await wrapper.find('[data-subject="EMPLOYEE"]').trigger('click');

    await wrapper.find('.ohrm-assessments__send').trigger('click');
    await flushPromises();

    expect(mockCreate).toHaveBeenCalledWith({
      scope: 'NETWORK',
      subunitId: null,
      employeeId: null,
    });
    expect(wrapper.text()).toContain('3');
  });

  it('issues a new link for a pending candidate', async () => {
    const wrapper = await mountIt();

    await wrapper
      .findAll('tbody tr')[0]
      .find('.ohrm-assessments__resend')
      .trigger('click');
    await flushPromises();

    expect(mockUpdate).toHaveBeenCalledWith(1, {action: 'resend'});
    expect(
      wrapper.find('.ohrm-assessments__link input').element.value,
    ).toContain('/recruitmentApply/assessment/TOKEN');
  });

  it('cancels only after asking', async () => {
    const wrapper = await mountIt();
    window.confirm = jest.fn(() => false);
    await wrapper
      .findAll('tbody tr')[0]
      .find('.ohrm-assessments__cancel')
      .trigger('click');
    expect(mockUpdate).not.toHaveBeenCalled();

    window.confirm = jest.fn(() => true);
    await wrapper
      .findAll('tbody tr')[0]
      .find('.ohrm-assessments__cancel')
      .trigger('click');
    await flushPromises();
    expect(mockUpdate).toHaveBeenCalledWith(1, {action: 'cancel'});
  });

  it('compares the answered profiles that are ticked', async () => {
    mockRequest.mockResolvedValue({
      data: {
        data: [
          {
            ...items[1],
            id: 3,
            candidateId: 1,
            employeeId: null,
            subjectType: 'CANDIDATE',
          },
          {...items[1], id: 4, candidateId: null, employeeId: 2},
          items[0],
        ],
      },
    });
    const wrapper = await mountIt();
    const boxes = wrapper.findAll('.ohrm-assessments__pick input');

    expect(boxes).toHaveLength(2);
    await boxes[0].setValue(true);
    await boxes[1].setValue(true);
    await wrapper.find('.ohrm-assessments__compare').trigger('click');

    expect(mockNavigate).toHaveBeenCalledWith(
      '/recruitment/brProfileCompare',
      {},
      {subjects: 'c1,e2'},
    );
  });
});
