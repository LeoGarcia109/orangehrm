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
import AssessmentRunner from '../AssessmentRunner.vue';

/**
 * Answering the behavioural questionnaire, page by page. Each page is saved
 * on the server before moving on, so a closed browser loses nothing, and the
 * same link or tab resumes where it stopped.
 */
describe('AssessmentRunner', () => {
  const page = (n, count = 3) =>
    Array.from({length: count}, (_, i) => ({
      code: `P${n}-${i + 1}`,
      text: `Frase ${n}.${i + 1}`,
    }));
  const state = (overrides = {}) => ({
    pages: [page(1), page(2), page(3, 2)],
    answers: {},
    nextPage: 0,
    ...overrides,
  });

  const mountWith = (s = state(), props = {}) => {
    const save = jest.fn((answers) =>
      Promise.resolve({...s, answers: {...s.answers, ...answers}}),
    );
    const complete = jest.fn(() => Promise.resolve());
    const wrapper = mount(AssessmentRunner, {
      props: {state: s, save, complete, ...props},
      global: {mocks: {$t: (k) => k}},
    });
    return {wrapper, save, complete};
  };

  const answerPage = async (wrapper, value = 4) => {
    for (const statement of wrapper.findAll('.ohrm-assessment__statement')) {
      await statement
        .findAll('.ohrm-assessment__option')
        [value - 1].trigger('click');
    }
  };

  it('starts where the person stopped', () => {
    const {wrapper} = mountWith(
      state({nextPage: 1, answers: {'P1-1': 3, 'P1-2': 3, 'P1-3': 3}}),
    );

    expect(wrapper.text()).toContain('Frase 2.1');
    expect(wrapper.find('.ohrm-assessment__progress').text()).toContain('2');
    expect(wrapper.find('.ohrm-assessment__progress').text()).toContain('3');
  });

  it('shows the five points of the scale for every statement', () => {
    const {wrapper} = mountWith();

    expect(wrapper.findAll('.ohrm-assessment__statement')).toHaveLength(3);
    expect(
      wrapper
        .findAll('.ohrm-assessment__statement')[0]
        .findAll('.ohrm-assessment__option'),
    ).toHaveLength(5);
  });

  it('asks for every statement before moving on', async () => {
    const {wrapper, save} = mountWith();
    await wrapper
      .findAll('.ohrm-assessment__statement')[0]
      .findAll('.ohrm-assessment__option')[1]
      .trigger('click');

    await wrapper.find('.ohrm-assessment__next').trigger('click');

    expect(save).not.toHaveBeenCalled();
    expect(wrapper.text()).toContain('attendance.assessment_answer_all');
    expect(
      wrapper.findAll('.ohrm-assessment__statement')[1].classes(),
    ).toContain('is-missing');
  });

  it('saves only the page it shows, then moves on', async () => {
    const {wrapper, save} = mountWith();
    await answerPage(wrapper, 2);

    await wrapper.find('.ohrm-assessment__next').trigger('click');
    await flushPromises();

    expect(save).toHaveBeenCalledWith({'P1-1': 2, 'P1-2': 2, 'P1-3': 2});
    expect(wrapper.text()).toContain('Frase 2.1');
  });

  it('goes back without saving and keeps the earlier answers', async () => {
    const {wrapper, save} = mountWith(
      state({nextPage: 1, answers: {'P1-1': 5, 'P1-2': 1, 'P1-3': 3}}),
    );

    await wrapper.find('.ohrm-assessment__back').trigger('click');

    expect(save).not.toHaveBeenCalled();
    const first = wrapper.findAll('.ohrm-assessment__statement')[0];
    expect(first.findAll('.ohrm-assessment__option')[4].classes()).toContain(
      'is-selected',
    );
  });

  it('finishes on the last page', async () => {
    const {wrapper, save, complete} = mountWith(state({nextPage: 2}));
    await answerPage(wrapper, 5);

    await wrapper.find('.ohrm-assessment__next').trigger('click');
    await flushPromises();

    expect(save).toHaveBeenCalledWith({'P3-1': 5, 'P3-2': 5});
    expect(complete).toHaveBeenCalled();
    expect(wrapper.emitted('done')).toBeTruthy();
  });

  it('shows the server refusal and stays on the page', async () => {
    const {wrapper} = mountWith(state(), {
      save: jest.fn(() =>
        Promise.reject({
          response: {
            data: {
              error: {message: 'Este link nao esta mais ativo. Fale com o RH.'},
            },
          },
        }),
      ),
    });
    await answerPage(wrapper);

    await wrapper.find('.ohrm-assessment__next').trigger('click');
    await flushPromises();

    expect(wrapper.find('.ohrm-assessment__error').text()).toContain(
      'Este link nao esta mais ativo',
    );
    expect(wrapper.text()).toContain('Frase 1.1');
  });
});
