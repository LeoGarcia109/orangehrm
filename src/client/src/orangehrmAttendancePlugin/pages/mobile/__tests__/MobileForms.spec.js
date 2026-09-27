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
  },
}));

import MobileForms from '../MobileForms.vue';
import FormFiller from '@/orangehrmAttendancePlugin/components/forms/FormFiller.vue';

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
});
