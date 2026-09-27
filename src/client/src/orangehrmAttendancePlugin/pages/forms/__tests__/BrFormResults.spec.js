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
    setIgnorePath() {
      return undefined;
    }
    request(...a) {
      return mockRequest(...a);
    }
  },
}));
jest.mock('@ohrm/core/util/helper/navigation', () => ({navigate: jest.fn()}));

import BrFormResults from '../BrFormResults.vue';

/**
 * What came of a form: who is missing, how each question went, each
 * person's grade, and grading what only a person can grade.
 */
describe('BrFormResults', () => {
  const results = (overrides = {}) => ({
    formId: 4,
    title: 'Prova de seguranca',
    kind: 'QUIZ',
    anonymous: false,
    status: 'PUBLISHED',
    passPercent: 70,
    summary: {
      audienceCount: 3,
      respondedCount: 2,
      pendingPeople: [
        {employeeId: 9, name: 'Bia Teste', unit: 'Acacia do Sul'},
      ],
      average: 43.8,
      passedPercent: 50,
      pendingReviewCount: 1,
    },
    hidden: false,
    perItem: [
      {
        itemId: 1,
        type: 'SINGLE',
        prompt: 'Extintor?',
        answered: 2,
        correctPercent: 50,
        options: [
          {id: 11, label: 'Po', count: 1, percent: 50, isCorrect: true},
          {id: 12, label: 'Agua', count: 1, percent: 50, isCorrect: false},
        ],
      },
      {
        itemId: 3,
        type: 'SCALE',
        prompt: 'Treinamento',
        answered: 2,
        average: 4,
        distribution: {1: 0, 2: 0, 3: 1, 4: 0, 5: 1},
      },
      {
        itemId: 2,
        type: 'SHORT_TEXT',
        prompt: 'Derramou?',
        answered: 1,
        texts: ['Isolar e areia'],
      },
    ],
    people: [
      {
        submissionId: 'a'.repeat(32),
        employeeId: 1,
        name: 'Leo Garcia',
        unit: null,
        submittedAt: '27/09/2026 10:00',
        attempt: 1,
        status: 'PENDING_REVIEW',
        percent: null,
        passed: null,
      },
      {
        submissionId: 'b'.repeat(32),
        employeeId: 2,
        name: 'Ana Teste',
        unit: null,
        submittedAt: '27/09/2026 11:00',
        attempt: 1,
        status: 'GRADED',
        percent: 0,
        passed: false,
      },
    ],
    ...overrides,
  });
  const detail = {
    submissionId: 'a'.repeat(32),
    name: 'Leo Garcia',
    answers: [
      {
        itemId: 1,
        position: 1,
        type: 'SINGLE',
        prompt: 'Extintor?',
        points: 2,
        options: [
          {id: 11, label: 'Po', isCorrect: true},
          {id: 12, label: 'Agua', isCorrect: false},
        ],
        optionIds: [11],
        correctOptionIds: [11],
        text: null,
        scale: null,
        yesNo: null,
        correctYesNo: null,
        awarded: 2,
      },
      {
        itemId: 2,
        position: 2,
        type: 'SHORT_TEXT',
        prompt: 'Derramou?',
        points: 2,
        options: [],
        optionIds: [],
        correctOptionIds: [],
        text: 'Isolar e areia',
        scale: null,
        yesNo: null,
        correctYesNo: null,
        awarded: null,
      },
    ],
  };

  const mountIt = async (data = results()) => {
    window.appGlobal = {baseUrl: '/web/index.php'};
    mockRequest.mockImplementation(({method, params}) => {
      if (method === 'GET') {
        return Promise.resolve({
          data: {data: params.submissionId ? detail : data},
        });
      }
      return Promise.resolve({
        data: {data: {status: 'GRADED', percent: 87.5, passed: true}},
      });
    });
    const wrapper = mount(BrFormResults, {
      props: {formId: 4},
      global: {
        mocks: {$t: (k) => k, $toast: {saveSuccess: () => Promise.resolve()}},
        stubs: {'oxd-text': {template: '<h6><slot /></h6>'}},
      },
    });
    await flushPromises();
    return wrapper;
  };

  beforeEach(() => mockRequest.mockReset());

  it('shows the totals and who has not answered', async () => {
    const wrapper = await mountIt();

    expect(wrapper.find('.ohrm-results__summary').text()).toContain('2 / 3');
    expect(wrapper.find('.ohrm-results__summary').text()).toContain('43,8%');
    expect(wrapper.find('.ohrm-results__missing').text()).toContain(
      'Bia Teste',
    );
  });

  it('draws each option with its share and marks the right one', async () => {
    const wrapper = await mountIt();
    const bars = wrapper.findAll('.ohrm-results__bar');

    expect(
      bars[0].find('.ohrm-results__bar-fill').attributes('style'),
    ).toContain('width: 50%');
    expect(bars[0].classes()).toContain('is-correct');
  });

  it('an anonymous survey below three answers shows only the notice', async () => {
    const wrapper = await mountIt(
      results({
        kind: 'SURVEY',
        anonymous: true,
        hidden: true,
        perItem: [],
        people: [],
      }),
    );

    expect(wrapper.text()).toContain('attendance.form_hidden_anonymous');
    expect(wrapper.find('.ohrm-results__people').exists()).toBe(false);
  });

  it('grades a written answer', async () => {
    const wrapper = await mountIt();

    await wrapper.findAll('.ohrm-results__view')[0].trigger('click');
    await flushPromises();
    await wrapper.find('.ohrm-results__review-points').setValue('1.5');
    await wrapper.find('.ohrm-results__review-save').trigger('click');
    await flushPromises();

    expect(mockRequest).toHaveBeenCalledWith(
      expect.objectContaining({
        method: 'PUT',
        data: {submissionId: 'a'.repeat(32), points: {2: 1.5}},
      }),
    );
  });

  it('allows another attempt after asking', async () => {
    const wrapper = await mountIt();
    window.confirm = jest.fn(() => true);

    await wrapper.findAll('.ohrm-results__retake')[1].trigger('click');
    await flushPromises();

    expect(mockRequest).toHaveBeenCalledWith(
      expect.objectContaining({
        method: 'POST',
        data: {formId: 4, employeeId: 2},
      }),
    );
  });

  it('links the spreadsheet', async () => {
    const wrapper = await mountIt();

    expect(wrapper.find('.ohrm-results__csv').attributes('href')).toBe(
      '/web/index.php/attendance/brFormResultsCsv/4',
    );
  });
});
