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
  },
}));
jest.mock('@ohrm/core/util/helper/navigation', () => ({
  navigate: (...a) => mockNavigate(...a),
}));

import BrForms from '../BrForms.vue';

/**
 * HR's list of forms and the ready-made templates.
 */
describe('BrForms', () => {
  const forms = [
    {
      id: 4,
      title: 'Prova de seguranca',
      kind: 'QUIZ',
      anonymous: false,
      status: 'PUBLISHED',
      scope: 'SUBUNIT',
      subunitName: 'Acacia do Sul',
      employeeName: null,
      dueAt: '2026-10-27',
      respondedCount: 3,
      audienceCount: 12,
    },
    {
      id: 5,
      title: 'Rascunho',
      kind: 'SURVEY',
      anonymous: true,
      status: 'DRAFT',
      scope: 'NETWORK',
      subunitName: null,
      employeeName: null,
      dueAt: null,
      respondedCount: null,
      audienceCount: null,
    },
  ];
  const templates = [
    {
      id: 1,
      title: 'Pesquisa de clima — Posto',
      kind: 'SURVEY',
      anonymous: true,
      description: 'Pesquisa anonima',
    },
  ];

  const mountIt = async () => {
    window.appGlobal = {baseUrl: ''};
    const wrapper = mount(BrForms, {
      global: {
        mocks: {$t: (k) => k},
        stubs: {'oxd-text': {template: '<h6><slot /></h6>'}},
      },
    });
    await flushPromises();
    return wrapper;
  };

  beforeEach(() => {
    [mockRequest, mockCreate, mockUpdate, mockNavigate].forEach((m) =>
      m.mockReset(),
    );
    mockRequest.mockResolvedValue({data: {data: forms, meta: {templates}}});
    mockCreate.mockResolvedValue({data: {data: {id: 9}}});
    mockUpdate.mockResolvedValue({data: {data: {id: 4, status: 'CLOSED'}}});
  });

  it('shows who answered out of the audience', async () => {
    const wrapper = await mountIt();
    const row = wrapper.findAll('tbody tr')[0];

    expect(row.text()).toContain('3 / 12');
    expect(row.text()).toContain('Acacia do Sul');
  });

  it('a draft has no answers to count yet', async () => {
    const row = (await mountIt()).findAll('tbody tr')[1];

    expect(row.text()).toContain('—');
    expect(row.find('.ohrm-forms__results').exists()).toBe(false);
  });

  it('using a template copies it and opens the copy', async () => {
    const wrapper = await mountIt();

    await wrapper.find('.ohrm-forms__use-template').trigger('click');
    await flushPromises();

    expect(mockCreate).toHaveBeenCalledWith({sourceId: 1});
    expect(mockNavigate).toHaveBeenCalledWith(
      '/attendance/brFormBuilder/{id}',
      {id: 9},
    );
  });

  it('closing asks first', async () => {
    const wrapper = await mountIt();
    window.confirm = jest.fn(() => false);

    await wrapper.find('.ohrm-forms__close').trigger('click');
    expect(mockUpdate).not.toHaveBeenCalled();

    window.confirm = jest.fn(() => true);
    await wrapper.find('.ohrm-forms__close').trigger('click');
    await flushPromises();
    expect(mockUpdate).toHaveBeenCalledWith(4, {action: 'close'});
  });
});
