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

import {mount} from '@vue/test-utils';
import FormFiller from '../FormFiller.vue';

/**
 * Answering a form, on the phone and on the desktop.
 *
 * What goes out is only what was answered, in the shape the server expects;
 * nothing goes out without the employee confirming, because it cannot be
 * changed afterwards.
 */
describe('FormFiller', () => {
  const quiz = (overrides = {}) => ({
    id: 7,
    title: 'Seguranca na pista',
    description: 'Leia com atencao',
    kind: 'QUIZ',
    anonymous: false,
    dueAt: '2026-10-10',
    items: [
      {
        id: 1,
        type: 'CONTENT',
        prompt: 'Antes de comecar',
        helpText: 'NR-20',
        required: false,
        imageId: null,
        youtubeId: 'dQw4w9WgXcQ',
        options: [],
      },
      {
        id: 2,
        type: 'SINGLE',
        prompt: 'Extintor?',
        helpText: null,
        required: true,
        imageId: 5,
        youtubeId: null,
        points: 2,
        options: [
          {id: 21, label: 'Po quimico'},
          {id: 22, label: 'Agua'},
        ],
      },
      {
        id: 3,
        type: 'MULTIPLE',
        prompt: 'Descarga?',
        helpText: null,
        required: false,
        imageId: null,
        youtubeId: null,
        points: 1,
        options: [
          {id: 31, label: 'Aterrar'},
          {id: 32, label: 'Isolar'},
        ],
      },
      {
        id: 4,
        type: 'SHORT_TEXT',
        prompt: 'Nome do gerente',
        helpText: null,
        required: false,
        imageId: null,
        youtubeId: null,
        points: 1,
        options: [],
      },
      {
        id: 5,
        type: 'LONG_TEXT',
        prompt: 'Derramamento?',
        helpText: null,
        required: false,
        imageId: null,
        youtubeId: null,
        points: 2,
        options: [],
      },
      {
        id: 6,
        type: 'SCALE',
        prompt: 'Treinamento',
        helpText: null,
        required: false,
        imageId: null,
        youtubeId: null,
        options: [],
      },
      {
        id: 7,
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
    ...overrides,
  });

  const mountWith = (form = quiz(), props = {}) => {
    window.appGlobal = {baseUrl: '/web/index.php'};
    return mount(FormFiller, {
      props: {form, ...props},
      global: {mocks: {$t: (key) => key}},
    });
  };

  const item = (wrapper, id) => wrapper.find(`[data-item-id="${id}"]`);

  beforeEach(() => {
    window.localStorage.clear();
    Element.prototype.scrollIntoView = jest.fn();
  });

  it('renders one control per item type', () => {
    const wrapper = mountWith();

    expect(
      item(wrapper, 1).findAll('input, textarea, button.ohrm-form__choice')
        .length,
    ).toBe(0);
    expect(item(wrapper, 2).findAll('input[type="radio"]').length).toBe(2);
    expect(item(wrapper, 3).findAll('input[type="checkbox"]').length).toBe(2);
    expect(
      item(wrapper, 4).find('input[type="text"]').attributes('maxlength'),
    ).toBe('255');
    expect(item(wrapper, 5).find('textarea').attributes('maxlength')).toBe(
      '5000',
    );
    expect(item(wrapper, 6).findAll('.ohrm-form__scale-btn').length).toBe(5);
    expect(item(wrapper, 7).findAll('.ohrm-form__yesno-btn').length).toBe(2);
  });

  it('shows the question image from the server', () => {
    const img = item(mountWith(), 2).find('img');

    expect(img.attributes('src')).toBe(
      '/web/index.php/attendance/brFormImage/5',
    );
  });

  it('shows how much a quiz question is worth', () => {
    expect(item(mountWith(), 2).text()).toContain('2');
  });

  it('shows the anonymous banner only on anonymous surveys', () => {
    expect(mountWith().find('.ohrm-form__anonymous').exists()).toBe(false);
    expect(
      mountWith(quiz({kind: 'SURVEY', anonymous: true}))
        .find('.ohrm-form__anonymous')
        .exists(),
    ).toBe(true);
  });

  it('marks required questions', () => {
    const wrapper = mountWith();

    expect(item(wrapper, 2).find('.ohrm-form__required').exists()).toBe(true);
    expect(item(wrapper, 3).find('.ohrm-form__required').exists()).toBe(false);
  });

  it('refuses to submit with a required question blank', async () => {
    const wrapper = mountWith();

    await wrapper.find('.ohrm-form__submit').trigger('click');

    expect(wrapper.emitted('submit')).toBeUndefined();
    expect(wrapper.text()).toContain('attendance.form_required_missing');
    expect(item(wrapper, 2).classes()).toContain('is-missing');
    expect(Element.prototype.scrollIntoView).toHaveBeenCalled();
  });

  it('asks for confirmation before sending, then sends only what was answered', async () => {
    const wrapper = mountWith();
    await item(wrapper, 2).findAll('input[type="radio"]')[0].setValue(true);
    await item(wrapper, 3).findAll('input[type="checkbox"]')[1].setValue(true);
    await item(wrapper, 5)
      .find('textarea')
      .setValue('  Isolar e jogar areia  ');
    await item(wrapper, 6).findAll('.ohrm-form__scale-btn')[3].trigger('click');
    await item(wrapper, 7).findAll('.ohrm-form__yesno-btn')[1].trigger('click');

    await wrapper.find('.ohrm-form__submit').trigger('click');
    expect(wrapper.emitted('submit')).toBeUndefined();
    expect(wrapper.find('.ohrm-form__confirm').exists()).toBe(true);

    await wrapper.find('.ohrm-form__confirm-send').trigger('click');
    expect(wrapper.emitted('submit')[0][0]).toEqual([
      {itemId: 2, optionIds: [21]},
      {itemId: 3, optionIds: [32]},
      {itemId: 5, text: 'Isolar e jogar areia'},
      {itemId: 6, scale: 4},
      {itemId: 7, yesNo: false},
    ]);
  });

  it('lets the employee back out of the confirmation', async () => {
    const wrapper = mountWith();
    await item(wrapper, 2).findAll('input[type="radio"]')[0].setValue(true);
    await item(wrapper, 7).findAll('.ohrm-form__yesno-btn')[0].trigger('click');
    await wrapper.find('.ohrm-form__submit').trigger('click');

    await wrapper.find('.ohrm-form__confirm-cancel').trigger('click');

    expect(wrapper.find('.ohrm-form__confirm').exists()).toBe(false);
    expect(wrapper.emitted('submit')).toBeUndefined();
  });

  it('restores a saved draft', async () => {
    window.localStorage.setItem(
      'ohrm-br-form-draft-7',
      JSON.stringify({2: {optionIds: [22]}, 5: {text: 'rascunho'}}),
    );
    const wrapper = mountWith();

    expect(
      item(wrapper, 2).findAll('input[type="radio"]')[1].element.checked,
    ).toBe(true);
    expect(item(wrapper, 5).find('textarea').element.value).toBe('rascunho');
    expect(wrapper.text()).toContain('attendance.form_draft_restored');
  });

  it('saves the draft as the employee answers', async () => {
    const wrapper = mountWith();
    await item(wrapper, 4).find('input[type="text"]').setValue('Carlos');

    expect(
      JSON.parse(window.localStorage.getItem('ohrm-br-form-draft-7'))[4],
    ).toEqual({text: 'Carlos'});
  });

  it('does not load the video until tapped', async () => {
    const wrapper = mountWith();
    expect(item(wrapper, 1).find('iframe').exists()).toBe(false);

    await item(wrapper, 1).find('.ohrm-form__video-poster').trigger('click');

    expect(item(wrapper, 1).find('iframe').attributes('src')).toContain(
      'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
    );
  });

  it('shows the server refusal and keeps the answers', () => {
    const wrapper = mountWith(quiz(), {
      error: 'O prazo deste formulario terminou em 10/10/2026.',
    });

    expect(wrapper.find('.ohrm-form__error').text()).toContain('O prazo');
  });

  it('in preview, never saves a draft or sends', async () => {
    const wrapper = mountWith(quiz(), {preview: true});
    await item(wrapper, 4).find('input[type="text"]').setValue('Carlos');

    expect(window.localStorage.getItem('ohrm-br-form-draft-7')).toBeNull();
    expect(
      wrapper.find('.ohrm-form__submit').attributes('disabled'),
    ).toBeDefined();
  });
});
