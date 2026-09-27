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
import AssessmentPublic from '../AssessmentPublic.vue';

/**
 * The candidate's page, reached by a link and nothing else: it must work
 * without a login, ask for consent before anything is stored, and say the
 * same thing for every link that no longer works.
 */
describe('AssessmentPublic', () => {
  const state = (overrides = {}) => ({
    subjectType: 'CANDIDATE',
    firstName: 'Maria',
    vacancyName: 'Frentista - Acacia do Sul',
    consentGiven: false,
    pages: [[{code: 'B5-E01', text: 'Eu sou a alma da festa.'}]],
    answers: [],
    nextPage: 0,
    ...overrides,
  });

  const reply = (status, body) =>
    Promise.resolve({
      ok: status < 400,
      status,
      json: () => Promise.resolve(body),
    });

  const mountWith = async (props = {}) => {
    window.appGlobal = {baseUrl: '/web/index.php'};
    const wrapper = mount(AssessmentPublic, {
      props: {
        token: 'T'.repeat(43),
        applied: false,
        bannerSrc: '/banner.png',
        ...props,
      },
      global: {mocks: {$t: (k) => k}},
    });
    await flushPromises();
    return wrapper;
  };

  beforeEach(() => {
    global.fetch = jest.fn((url, options = {}) => {
      if (!options.method || options.method === 'GET')
        return reply(200, {data: state()});
      return reply(200, {data: state({consentGiven: true})});
    });
  });

  it("calls the link's own API, not a logged-in one", async () => {
    await mountWith();

    expect(global.fetch.mock.calls[0][0]).toBe(
      `/web/index.php/recruitmentApply/assessment/${'T'.repeat(43)}/api`,
    );
  });

  it('says the link is no longer active, whatever the reason', async () => {
    global.fetch = jest.fn(() => reply(404, {error: {message: 'x'}}));
    const wrapper = await mountWith();

    expect(wrapper.text()).toContain('attendance.assessment_inactive');
    expect(wrapper.find('.ohrm-assessment-public__start').exists()).toBe(false);
  });

  it('greets by first name and names the vacancy', async () => {
    const wrapper = await mountWith();

    expect(wrapper.text()).toContain('Maria');
    expect(wrapper.text()).toContain('Frentista - Acacia do Sul');
  });

  it('shows the application was received when coming from the job page', async () => {
    const wrapper = await mountWith({applied: true});

    expect(wrapper.text()).toContain('attendance.assessment_applied');
  });

  it('does not start without consent', async () => {
    const wrapper = await mountWith();
    const start = wrapper.find('.ohrm-assessment-public__start');

    expect(start.attributes('disabled')).toBeDefined();
    await wrapper.find('.ohrm-assessment-public__consent input').setValue(true);
    expect(start.attributes('disabled')).toBeUndefined();
  });

  it('records the consent, then shows the questions', async () => {
    const wrapper = await mountWith();
    await wrapper.find('.ohrm-assessment-public__consent input').setValue(true);

    await wrapper.find('.ohrm-assessment-public__start').trigger('click');
    await flushPromises();

    const [, options] = global.fetch.mock.calls[1];
    expect(options.method).toBe('PUT');
    expect(JSON.parse(options.body)).toEqual({consent: true});
    expect(wrapper.text()).toContain('Eu sou a alma da festa.');
  });

  it('resumes without asking for consent again', async () => {
    global.fetch = jest.fn(() =>
      reply(200, {data: state({consentGiven: true})}),
    );
    const wrapper = await mountWith();

    expect(wrapper.find('.ohrm-assessment-public__consent').exists()).toBe(
      false,
    );
    await wrapper.find('.ohrm-assessment-public__start').trigger('click');
    expect(wrapper.text()).toContain('Eu sou a alma da festa.');
  });

  it('thanks at the end, and shows nothing of the result', async () => {
    global.fetch = jest.fn(() =>
      reply(200, {data: state({consentGiven: true})}),
    );
    const wrapper = await mountWith();
    await wrapper.find('.ohrm-assessment-public__start').trigger('click');

    wrapper.findComponent({name: 'AssessmentRunner'}).vm.$emit('done');
    await flushPromises();

    expect(wrapper.text()).toContain('attendance.assessment_thanks');
  });
});
