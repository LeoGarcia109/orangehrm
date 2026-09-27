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

// jest.mock factories may only reach variables prefixed with `mock`
let mockSheet;
const mockRequest = jest.fn();

jest.mock('@ohrm/core/util/services/api.service', () => ({
  APIService: class {
    setIgnorePath() {
      return undefined;
    }
    request(...args) {
      return mockRequest(...args);
    }
  },
}));

import MobileTimesheet from '../MobileTimesheet.vue';

/**
 * Signing the month is meant to stand up as evidence, so it has to read as a
 * deliberate act: the card shows what is being signed, and the password is
 * asked for only after the employee chooses to sign -- not left sitting open
 * on the history screen.
 */
describe('MobileTimesheet', () => {
  const sheet = (overrides = {}) => ({
    month: '2026-08',
    recordCount: 4,
    totalSeconds: 51480,
    signedAt: null,
    intact: null,
    canSign: true,
    blocker: null,
    ...overrides,
  });

  const mountWith = async (data) => {
    mockSheet = data;
    mockRequest.mockImplementation(() =>
      Promise.resolve({data: {data: mockSheet}}),
    );
    window.appGlobal = {baseUrl: ''};
    const wrapper = mount(MobileTimesheet, {
      global: {
        mocks: {
          $t: (key) => key,
          $toast: {saveSuccess: () => Promise.resolve()},
        },
      },
    });
    await flushPromises();
    return wrapper;
  };

  beforeEach(() => {
    mockRequest.mockReset();
  });

  it('shows the month and its totals', async () => {
    const wrapper = await mountWith(sheet());

    expect(wrapper.find('.ohrm-mobile__sheet-stat-value').text()).toBe('4');
    expect(wrapper.text()).toContain('14h 18m');
  });

  it('does not ask for the password until the employee chooses to sign', async () => {
    const wrapper = await mountWith(sheet());

    expect(wrapper.find('input[type="password"]').exists()).toBe(false);
    expect(wrapper.find('.ohrm-mobile__sheet-cta').exists()).toBe(true);
  });

  it('opens a confirmation step with the password when signing', async () => {
    const wrapper = await mountWith(sheet());

    await wrapper.find('.ohrm-mobile__sheet-cta').trigger('click');

    expect(wrapper.find('.ohrm-mobile__sheet-confirm').exists()).toBe(true);
    expect(wrapper.find('input[type="password"]').exists()).toBe(true);
    expect(wrapper.find('.ohrm-mobile__sheet-cta').exists()).toBe(false);
  });

  it('keeps the confirm button disabled until a password is typed', async () => {
    const wrapper = await mountWith(sheet());
    await wrapper.find('.ohrm-mobile__sheet-cta').trigger('click');

    const confirm = () => wrapper.find('.ohrm-mobile__sheet-btn--primary');
    expect(confirm().attributes('disabled')).toBeDefined();

    await wrapper.find('input[type="password"]').setValue('segredo');
    expect(confirm().attributes('disabled')).toBeUndefined();
  });

  it('forgets the password when the employee backs out', async () => {
    const wrapper = await mountWith(sheet());
    await wrapper.find('.ohrm-mobile__sheet-cta').trigger('click');
    await wrapper.find('input[type="password"]').setValue('segredo');

    await wrapper.find('.ohrm-mobile__sheet-btn--secondary').trigger('click');

    expect(wrapper.find('.ohrm-mobile__sheet-confirm').exists()).toBe(false);
    await wrapper.find('.ohrm-mobile__sheet-cta').trigger('click');
    expect(wrapper.find('input[type="password"]').element.value).toBe('');
  });

  it('sends the month and the password when confirmed', async () => {
    const wrapper = await mountWith(sheet());
    await wrapper.find('.ohrm-mobile__sheet-cta').trigger('click');
    await wrapper.find('input[type="password"]').setValue('segredo');

    await wrapper.find('.ohrm-mobile__sheet-btn--primary').trigger('click');
    await flushPromises();

    expect(mockRequest).toHaveBeenCalledWith(
      expect.objectContaining({
        method: 'POST',
        data: {month: '2026-08', password: 'segredo'},
      }),
    );
  });

  it('shows when a signed sheet was signed, with no way to sign again', async () => {
    const wrapper = await mountWith(
      sheet({signedAt: '01/09/2026 17:51', intact: true, canSign: false}),
    );

    expect(wrapper.text()).toContain('01/09/2026 17:51');
    expect(wrapper.find('.ohrm-mobile__sheet-cta').exists()).toBe(false);
    expect(wrapper.find('.ohrm-mobile__sheet-chip').classes()).toContain(
      'is-signed',
    );
  });

  it('warns loudly when the records changed after signing', async () => {
    const wrapper = await mountWith(
      sheet({signedAt: '01/09/2026 17:51', intact: false, canSign: false}),
    );

    expect(wrapper.find('.ohrm-mobile__sheet-chip').classes()).toContain(
      'is-broken',
    );
  });

  it('explains why a sheet cannot be signed yet', async () => {
    const wrapper = await mountWith(
      sheet({canSign: false, blocker: 'Ha batida em aberto no periodo'}),
    );

    expect(wrapper.text()).toContain('Ha batida em aberto no periodo');
    expect(wrapper.find('.ohrm-mobile__sheet-cta').exists()).toBe(false);
  });
});
