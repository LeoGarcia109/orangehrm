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

/* eslint-disable @typescript-eslint/no-explicit-any */

// These pull ESM-only bundles (axios, @ohrm/oxd, leaflet) that Jest does not
// transform; the option shape under test does not depend on any of them.
jest.mock('@/core/util/services/api.service', () => ({
  APIService: class {},
}));
jest.mock('@ohrm/oxd', () => ({
  OxdSwitchInput: {},
  OxdIconButton: {},
}));
jest.mock('../GeofenceLocationMapModal.vue', () => ({}));

import GeofenceLocationsComponent from '../GeofenceLocations.vue';

const GeofenceLocations = GeofenceLocationsComponent as any;

/**
 * `oxd-input-field type="select"` renders each option as `option.label` and shows
 * the selected one via `modelValue.label`. Options keyed on `name` render blank
 * rows, which reads to the user as "the company is missing from the dropdown".
 */
describe('GeofenceLocations::scopeOptions', () => {
  const buildOptions = (units: unknown[]) => {
    return GeofenceLocations.computed.scopeOptions.call({
      units,
      $t: (key: string) => key,
    });
  };

  it('labels the default scope option', () => {
    const [defaultOption] = buildOptions([]);
    expect(defaultOption.id).toBeNull();
    expect(defaultOption.label).toBe('attendance.geofence_scope_default');
  });

  it('labels each company unit so it is visible in the dropdown', () => {
    const options = buildOptions([
      {id: 2, name: 'Acacia do Sul', level: 1},
      {id: 3, name: 'Filial Norte', level: 1},
    ]);

    expect(options).toHaveLength(3);
    expect(options[1]).toMatchObject({id: 2, label: 'Acacia do Sul'});
    expect(options[2]).toMatchObject({id: 3, label: 'Filial Norte'});
  });

  it('never emits an option without a label', () => {
    const options = buildOptions([{id: 2, name: 'Acacia do Sul', level: 1}]);
    options.forEach((option: {label?: string}) => {
      expect(typeof option.label).toBe('string');
      expect(option.label).not.toBe('');
    });
  });
});
