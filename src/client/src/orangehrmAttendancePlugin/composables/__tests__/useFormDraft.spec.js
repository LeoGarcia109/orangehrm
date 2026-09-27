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

import useFormDraft from '../useFormDraft';

/**
 * An answer half-typed on a phone that locks, rings or loses signal must still
 * be there when the employee comes back -- and must be gone once sent.
 */
describe('useFormDraft', () => {
  beforeEach(() => {
    window.localStorage.clear();
    jest.restoreAllMocks();
  });

  it('keeps what was answered, per form', () => {
    useFormDraft(7).save({3: {text: 'meio caminho'}});

    expect(useFormDraft(7).load()).toEqual({3: {text: 'meio caminho'}});
    expect(useFormDraft(8).load()).toBeNull();
  });

  it('forgets the draft once cleared', () => {
    const draft = useFormDraft(7);
    draft.save({3: {scale: 4}});
    draft.clear();

    expect(draft.load()).toBeNull();
  });

  it('ignores a corrupted draft', () => {
    window.localStorage.setItem('ohrm-br-form-draft-7', '{nao e json');

    expect(useFormDraft(7).load()).toBeNull();
  });

  it('keeps working when storage is unavailable', () => {
    jest.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
      throw new Error('private mode');
    });
    jest.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('private mode');
    });
    const draft = useFormDraft(7);

    expect(() => draft.save({3: {scale: 1}})).not.toThrow();
    expect(draft.load()).toBeNull();
    expect(() => draft.clear()).not.toThrow();
  });
});
