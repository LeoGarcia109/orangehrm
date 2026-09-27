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

const mockGet = jest.fn();
const mockCreate = jest.fn();
const mockUpdate = jest.fn();
const mockGetAll = jest.fn();

jest.mock('@ohrm/core/util/services/api.service', () => ({
  APIService: class {
    setIgnorePath() {
      return undefined;
    }
    get(...a) {
      return mockGet(...a);
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
jest.mock('@ohrm/core/util/helper/navigation', () => ({
  navigate: jest.fn(),
}));

import BrFormBuilder from '../BrFormBuilder.vue';

/**
 * Building a form block by block, as HR sees it.
 */
describe('BrFormBuilder', () => {
  const saved = (overrides = {}) => ({
    id: 4,
    title: 'Prova de seguranca',
    description: null,
    status: 'DRAFT',
    subunitName: null,
    employeeName: null,
    definition: {
      kind: 'QUIZ',
      anonymous: false,
      passPercent: 70,
      scope: 'NETWORK',
      subunitId: null,
      employeeId: null,
      dueAt: null,
      isTemplate: false,
      items: [
        {
          id: 11,
          type: 'YES_NO',
          prompt: 'Completar?',
          helpText: null,
          required: true,
          points: 1,
          imageId: null,
          youtubeId: null,
          correctYesNo: false,
          options: [],
        },
        {
          id: 12,
          type: 'SHORT_TEXT',
          prompt: 'Derramamento?',
          helpText: null,
          required: false,
          points: 2,
          imageId: null,
          youtubeId: null,
          correctYesNo: null,
          options: [],
        },
      ],
    },
    ...overrides,
  });

  const mountWith = async (props = {}) => {
    window.appGlobal = {baseUrl: ''};
    Element.prototype.scrollIntoView = jest.fn();
    const wrapper = mount(BrFormBuilder, {
      props,
      global: {
        mocks: {
          $t: (key) => key,
          $toast: {saveSuccess: () => Promise.resolve()},
        },
        stubs: {'oxd-text': {template: '<h6><slot /></h6>'}},
      },
    });
    await flushPromises();
    return wrapper;
  };

  const cards = (wrapper) => wrapper.findAll('.ohrm-builder__item');
  const add = (wrapper, type) =>
    wrapper
      .find(`.ohrm-builder__add-btn[data-type="${type}"]`)
      .trigger('click');

  beforeEach(() => {
    [mockGet, mockCreate, mockUpdate, mockGetAll].forEach((m) => m.mockReset());
    mockGet.mockResolvedValue({data: {data: saved()}});
    mockGetAll.mockResolvedValue({data: {data: []}});
    mockCreate.mockResolvedValue({data: {data: {id: 4}}});
    mockUpdate.mockResolvedValue({data: {data: {id: 4, status: 'DRAFT'}}});
  });

  it('adds a single choice block with two empty options', async () => {
    const wrapper = await mountWith();

    await add(wrapper, 'SINGLE');

    expect(cards(wrapper)).toHaveLength(1);
    expect(cards(wrapper)[0].findAll('.ohrm-builder__option')).toHaveLength(2);
  });

  it('moves a block up', async () => {
    const wrapper = await mountWith({formId: 4});

    await cards(wrapper)[1].find('.ohrm-builder__move-up').trigger('click');

    expect(cards(wrapper)[0].find('.ohrm-builder__prompt').element.value).toBe(
      'Derramamento?',
    );
  });

  it('on a single choice quiz question, marking one right unmarks the other', async () => {
    const wrapper = await mountWith();
    await add(wrapper, 'SINGLE');
    const marks = () => cards(wrapper)[0].findAll('.ohrm-builder__correct');

    await marks()[0].setValue(true);
    await marks()[1].setValue(true);

    expect(marks()[0].element.checked).toBe(false);
    expect(marks()[1].element.checked).toBe(true);
  });

  it('saves the blocks in order, without anything local to the screen', async () => {
    const wrapper = await mountWith({formId: 4});
    await cards(wrapper)[1].find('.ohrm-builder__move-up').trigger('click');

    await wrapper.find('.ohrm-builder__save').trigger('click');
    await flushPromises();

    const [id, body] = mockUpdate.mock.calls[0];
    expect(id).toBe(4);
    expect(body.action).toBe('save');
    expect(body.definition.items.map((i) => i.prompt)).toEqual([
      'Derramamento?',
      'Completar?',
    ]);
    expect(JSON.stringify(body)).not.toContain('"key"');
    expect(JSON.stringify(body)).not.toContain('youtubeUrl');
  });

  it('a new form is created on its first save', async () => {
    const wrapper = await mountWith();
    await wrapper.find('.ohrm-builder__title').setValue('Nova prova');
    await add(wrapper, 'YES_NO');

    await wrapper.find('.ohrm-builder__save').trigger('click');
    await flushPromises();

    expect(mockCreate).toHaveBeenCalledWith(
      expect.objectContaining({title: 'Nova prova'}),
    );
  });

  it('points at the block a publish refusal names', async () => {
    mockUpdate.mockImplementation((id, body) =>
      body.action === 'publish'
        ? Promise.reject({
            data: {
              error: {message: 'Bloco 2: marque exatamente uma opcao certa.'},
            },
          })
        : Promise.resolve({data: {data: {id: 4, status: 'DRAFT'}}}),
    );
    const wrapper = await mountWith({formId: 4});

    await wrapper.find('.ohrm-builder__publish').trigger('click');
    await flushPromises();

    expect(cards(wrapper)[1].classes()).toContain('is-error');
    expect(cards(wrapper)[1].text()).toContain(
      'marque exatamente uma opcao certa',
    );
    expect(cards(wrapper)[0].classes()).not.toContain('is-error');
  });

  it('turns a pasted YouTube link into the video id', async () => {
    const wrapper = await mountWith({formId: 4});
    await cards(wrapper)[0]
      .find('.ohrm-builder__youtube')
      .setValue('https://youtu.be/dQw4w9WgXcQ?si=x');

    await wrapper.find('.ohrm-builder__save').trigger('click');
    await flushPromises();

    expect(mockUpdate.mock.calls[0][1].definition.items[0].youtubeId).toBe(
      'dQw4w9WgXcQ',
    );
  });

  it('flags a link that is not YouTube', async () => {
    const wrapper = await mountWith({formId: 4});

    await cards(wrapper)[0]
      .find('.ohrm-builder__youtube')
      .setValue('https://vimeo.com/1');

    expect(cards(wrapper)[0].text()).toContain(
      'attendance.form_youtube_invalid',
    );
  });

  it('a published form is read-only', async () => {
    mockGet.mockResolvedValue({data: {data: saved({status: 'PUBLISHED'})}});
    const wrapper = await mountWith({formId: 4});

    expect(wrapper.find('.ohrm-builder__add').exists()).toBe(false);
    expect(wrapper.find('.ohrm-builder__save').exists()).toBe(false);
    expect(wrapper.find('.ohrm-builder__duplicate').exists()).toBe(true);
  });

  it('previews the form without its answer key', async () => {
    const wrapper = await mountWith({formId: 4});

    await wrapper.find('.ohrm-builder__preview').trigger('click');

    const filler = wrapper.findComponent({name: 'FormFiller'});
    expect(filler.props('preview')).toBe(true);
    expect(JSON.stringify(filler.props('form'))).not.toContain('correctYesNo');
  });
});
