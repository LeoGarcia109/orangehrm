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

/**
 * BR: answers to a form kept on the device until they are sent.
 *
 * A phone locks, rings and loses signal in the middle of a form; the answers
 * given so far should still be there. Storage can be missing (private mode) or
 * full -- then the draft is simply not kept, and the form still works.
 */

const PREFIX = 'ohrm-br-form-draft-';

export default function useFormDraft(formId) {
  const key = `${PREFIX}${formId}`;

  return {
    load() {
      try {
        const raw = window.localStorage.getItem(key);
        const parsed = raw ? JSON.parse(raw) : null;
        return parsed && typeof parsed === 'object' ? parsed : null;
      } catch (e) {
        return null;
      }
    },
    save(answers) {
      try {
        window.localStorage.setItem(key, JSON.stringify(answers));
      } catch (e) {
        // No room or no storage: the answers stay on screen, just not saved.
      }
    },
    clear() {
      try {
        window.localStorage.removeItem(key);
      } catch (e) {
        // Nothing to clear.
      }
    },
  };
}
