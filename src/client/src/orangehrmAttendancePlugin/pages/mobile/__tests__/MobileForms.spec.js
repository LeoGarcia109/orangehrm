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
const mockGet = jest.fn();
const mockUpdate = jest.fn();

jest.mock('@ohrm/core/util/services/api.service', () => ({
  APIService: class {
    setIgnorePath() {
      return undefined;
    }
    request(...args) {
      return mockRequest(...args);
    }
    get(...args) {
      return mockGet(...args);
    }
    update(...args) {
      return mockUpdate(...args);
    }
  },
}));

import MobileForms from '../MobileForms.vue';
import FormFiller from '@/orangehrmAttendancePlugin/components/forms/FormFiller.vue';
import AssessmentRunner from '@/orangehrmAttendancePlugin/components/assessment/AssessmentRunner.vue';

/**
 * The "Provas" tab: what is waiting, answering it, and what came of it.
 */
describe('MobileForms', () => {
  const pending = {
    id: 7,
    title: 'Prova de seguranca',
    description: null,
    kind: 'QUIZ',
    anonymous: false,
    dueAt: '2026-10-10',
    section: 'pending',
    attemptsUsed: 0,
    attemptsAllowed: 1,
    result: null,
  };
  const answered = {
    ...pending,
    id: 8,
    title: 'Pesquisa de clima',
    kind: 'SURVEY',
    anonymous: true,
    dueAt: null,
    section: 'answered',
    attemptsUsed: 1,
    result: {status: 'RECORDED', percent: null, passed: null},
  };
  const fillView = {
    id: 7,
    title: 'Prova de seguranca',
    description: null,
    dueAt: '2026-10-10',
    kind: 'QUIZ',
    anonymous: false,
    items: [
      {
        id: 2,
        type: 'YES_NO',
        prompt: 'Completar?',
        helpText: null,
        required: true,
        imageId: null,
        youtubeId: null,
        points: 1,
        options: [],
      },
    ],
  };

  const mountWith = async (props = {}) => {
    window.appGlobal = {baseUrl: ''};
    const wrapper = mount(MobileForms, {
      props,
      global: {mocks: {$t: (key) => key}},
    });
    await flushPromises();
    return wrapper;
  };

  beforeEach(() => {
    window.localStorage.clear();
    mockRequest.mockReset();
    mockGet.mockReset();
    mockRequest.mockImplementation(({method}) =>
      method === 'POST'
        ? Promise.resolve({
            data: {data: {status: 'GRADED', percent: 87.5, passed: true}},
          })
        : Promise.resolve({
            data: {data: [pending, answered], meta: {pendingCount: 1}},
          }),
    );
    mockGet.mockResolvedValue({data: {data: fillView}});
  });

  it('lists what is waiting and what was answered', async () => {
    const wrapper = await mountWith();

    const sections = wrapper.findAll('.ohrm-mobile__forms-section');
    expect(sections[0].text()).toContain('Prova de seguranca');
    expect(sections[0].text()).toContain('10/10/2026');
    expect(sections[1].text()).toContain('Pesquisa de clima');
    expect(sections[1].text()).toContain('attendance.form_done');
  });

  it('tells the dock how many are waiting', async () => {
    const wrapper = await mountWith();

    expect(wrapper.emitted('pending-changed')[0]).toEqual([1]);
  });

  it('opens a form from the list', async () => {
    const wrapper = await mountWith();

    await wrapper.find('.ohrm-mobile__form-card--pending').trigger('click');
    await flushPromises();

    expect(mockGet).toHaveBeenCalledWith(7);
    expect(wrapper.findComponent(FormFiller).exists()).toBe(true);
  });

  it('opens the form a notice pointed at', async () => {
    const wrapper = await mountWith({openFormId: 7});

    expect(mockGet).toHaveBeenCalledWith(7);
    expect(wrapper.findComponent(FormFiller).exists()).toBe(true);
  });

  it('sends the answers and shows the grade', async () => {
    const wrapper = await mountWith({openFormId: 7});

    wrapper
      .findComponent(FormFiller)
      .vm.$emit('submit', [{itemId: 2, yesNo: false}]);
    await flushPromises();

    expect(mockRequest).toHaveBeenCalledWith(
      expect.objectContaining({
        method: 'POST',
        data: {formId: 7, answers: [{itemId: 2, yesNo: false}]},
      }),
    );
    expect(wrapper.find('.ohrm-mobile__form-result').text()).toContain('87,5%');
    expect(wrapper.text()).toContain('attendance.form_result_passed');
  });

  it('says when the grade waits for review', async () => {
    mockRequest.mockImplementation(({method}) =>
      method === 'POST'
        ? Promise.resolve({
            data: {
              data: {status: 'PENDING_REVIEW', percent: null, passed: null},
            },
          })
        : Promise.resolve({data: {data: [pending], meta: {pendingCount: 1}}}),
    );
    const wrapper = await mountWith({openFormId: 7});

    wrapper.findComponent(FormFiller).vm.$emit('submit', []);
    await flushPromises();

    expect(wrapper.find('.ohrm-mobile__form-result').text()).toContain(
      'attendance.form_pending_review',
    );
  });

  it('shows why the server refused, and stays on the form', async () => {
    mockRequest.mockImplementation(({method}) =>
      method === 'POST'
        ? Promise.reject({
            data: {error: {message: 'O prazo deste formulario terminou.'}},
          })
        : Promise.resolve({data: {data: [pending], meta: {pendingCount: 1}}}),
    );
    const wrapper = await mountWith({openFormId: 7});

    wrapper.findComponent(FormFiller).vm.$emit('submit', []);
    await flushPromises();

    expect(wrapper.findComponent(FormFiller).props('error')).toBe(
      'O prazo deste formulario terminou.',
    );
  });

  describe('behavioural profile', () => {
    const assessmentState = {
      subjectType: 'EMPLOYEE',
      firstName: 'Leo',
      consentGiven: false,
      pages: [[{code: 'B5-E01', text: 'Eu sou a alma da festa.'}]],
      answers: [],
      nextPage: 0,
    };

    beforeEach(() => {
      mockRequest.mockImplementation(() =>
        Promise.resolve({
          data: {
            data: [],
            meta: {
              pendingCount: 1,
              assessments: [{id: 9, createdAt: '2026-09-27'}],
            },
          },
        }),
      );
      mockGet.mockResolvedValue({data: {data: assessmentState}});
      mockUpdate.mockResolvedValue({data: {data: assessmentState}});
    });

    it('lists the pending profile with the forms', async () => {
      const wrapper = await mountWith();

      const card = wrapper.find('.ohrm-mobile__form-card--assessment');
      expect(card.exists()).toBe(true);
      expect(card.text()).toContain('attendance.assessment_title');
    });

    it('explains the purpose, then runs the questionnaire through the employee API', async () => {
      const wrapper = await mountWith();
      await wrapper
        .find('.ohrm-mobile__form-card--assessment')
        .trigger('click');
      await flushPromises();

      expect(mockGet).toHaveBeenCalledWith(9);
      expect(wrapper.text()).toContain('attendance.assessment_employee_notice');

      await wrapper.find('.ohrm-mobile__assessment-start').trigger('click');
      const runner = wrapper.findComponent(AssessmentRunner);
      expect(runner.exists()).toBe(true);

      await runner.props('save')({'B5-E01': 4});
      expect(mockUpdate).toHaveBeenCalledWith(9, {answers: {'B5-E01': 4}});
      await runner.props('complete')();
      expect(mockUpdate).toHaveBeenCalledWith(9, {complete: true});
    });

    it('thanks when done, without any result', async () => {
      const wrapper = await mountWith();
      await wrapper
        .find('.ohrm-mobile__form-card--assessment')
        .trigger('click');
      await flushPromises();
      await wrapper.find('.ohrm-mobile__assessment-start').trigger('click');

      wrapper.findComponent(AssessmentRunner).vm.$emit('done');
      await flushPromises();

      expect(wrapper.text()).toContain('attendance.assessment_thanks');
    });
  });
});
