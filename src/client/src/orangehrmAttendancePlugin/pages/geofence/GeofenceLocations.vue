<!--
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
 -->

<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('attendance.geofence_locations') }}
      </oxd-text>

      <oxd-divider />

      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-input-field
              v-model="scopeSelection"
              type="select"
              :label="$t('attendance.geofence_scope')"
              :options="scopeOptions"
              @update:model-value="onScopeChange"
            />
            <div class="orangehrm-geofence-field-row">
              <oxd-text tag="p" class="orangehrm-geofence-field-label">
                {{ $t('attendance.geofence_enforce') }}
              </oxd-text>
              <oxd-switch-input v-model="enabled" />
            </div>
          </oxd-grid>
        </oxd-form-row>

        <oxd-form-row v-if="scopeId !== null">
          <div class="orangehrm-geofence-field-row">
            <oxd-text tag="p" class="orangehrm-geofence-field-label">
              {{ $t('attendance.geofence_required_here') }}
            </oxd-text>
            <oxd-switch-input v-model="geofenceRequired" />
          </div>
          <oxd-text tag="p" class="orangehrm-geofence-hint">
            {{ $t('attendance.geofence_required_hint') }}
          </oxd-text>
        </oxd-form-row>

        <oxd-form-row v-if="showNoLocationWarning">
          <oxd-text tag="p" class="orangehrm-geofence-warning">
            {{ $t('attendance.geofence_no_location_warning') }}
          </oxd-text>
        </oxd-form-row>

        <oxd-divider />

        <oxd-text tag="p" class="orangehrm-geofence-hint">
          {{ $t('attendance.geofence_locations_hint') }}
        </oxd-text>

        <oxd-form-row
          v-for="(location, index) in locations"
          :key="index"
          class="orangehrm-geofence-location-row"
        >
          <oxd-grid :cols="12" class="orangehrm-full-width-grid">
            <oxd-grid-item :offset="0" :span="4">
              <oxd-input-field
                v-model="location.name"
                :label="$t('attendance.location_name')"
                :rules="locationRules.name"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item :span="2">
              <oxd-input-field
                v-model="location.latitude"
                :label="$t('attendance.latitude')"
                :rules="locationRules.latitude"
                :placeholder="'-23.550520'"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item :span="2">
              <oxd-input-field
                v-model="location.longitude"
                :label="$t('attendance.longitude')"
                :rules="locationRules.longitude"
                :placeholder="'-46.633308'"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item :span="2">
              <oxd-input-field
                v-model="location.radius"
                :label="$t('attendance.radius_meters')"
                :rules="locationRules.radius"
                :placeholder="'300'"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item :span="2" class="orangehrm-geofence-actions">
              <oxd-icon-button
                class="orangehrm-geofence-pick"
                name="geo-alt"
                role="none"
                :aria-label="$t('attendance.geofence_pick_on_map')"
                :title="$t('attendance.geofence_pick_on_map')"
                @click="onOpenMap(index)"
              />
              <oxd-icon-button
                class="orangehrm-geofence-remove"
                name="trash-fill"
                role="none"
                :aria-label="$t('general.delete')"
                @click="onRemoveLocation(index)"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-form-row>
          <oxd-button
            type="button"
            display-type="ghost"
            icon-name="plus-lg"
            :label="$t('admin.add_location')"
            @click="onAddLocation"
          />
        </oxd-form-row>

        <oxd-divider />

        <oxd-form-actions>
          <submit-button />
        </oxd-form-actions>
      </oxd-form>

      <geofence-location-map-modal
        v-if="mapModalLocation !== null"
        :latitude="locations[mapModalLocation]?.latitude"
        :longitude="locations[mapModalLocation]?.longitude"
        :radius="locations[mapModalLocation]?.radius"
        @apply="onMapApply"
        @close="mapModalLocation = null"
      />
    </div>
  </div>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';
import {
  required,
  shouldNotExceedCharLength,
  numberShouldBeBetweenMinAndMaxValue,
  digitsOnlyWithDecimalPointAndMinusSign,
} from '@ohrm/core/util/validation/rules';
import {OxdSwitchInput, OxdIconButton} from '@ohrm/oxd';
import {translate as translatorFactory} from '@/core/plugins/i18n/translate';
import GeofenceLocationMapModal from './GeofenceLocationMapModal.vue';

const translate = translatorFactory();

const emptyLocation = {
  id: null,
  name: '',
  latitude: '',
  longitude: '',
  radius: '',
};

// Coordinates may be negative; the stock "between" helper only accepts digits.
const signedNumberBetween = (min, max) => (value) => {
  if (value === '' || value == null) {
    return true; // required rule handles empties
  }
  const num = parseFloat(value);
  return (
    (!Number.isNaN(num) && num >= min && num <= max) ||
    translate('general.should_be_a_number_between_min_and_max', {
      min: min,
      max: max,
    })
  );
};

export default {
  name: 'GeofenceLocations',
  components: {
    'oxd-switch-input': OxdSwitchInput,
    'oxd-icon-button': OxdIconButton,
    'geofence-location-map-modal': GeofenceLocationMapModal,
  },
  setup() {
    const geofenceHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/geofence',
    );
    const subunitHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/admin/subunits',
    );
    return {
      geofenceHttp,
      subunitHttp,
    };
  },
  data() {
    return {
      isLoading: false,
      enabled: false,
      geofenceRequired: false,
      scopeSelection: null, // oxd-select model: {id, label} object or null
      units: [],
      locations: [],
      mapModalLocation: null, // index of the location being picked on the map
      locationRules: {
        name: [required, shouldNotExceedCharLength(100)],
        latitude: [
          required,
          digitsOnlyWithDecimalPointAndMinusSign,
          signedNumberBetween(-90, 90),
        ],
        longitude: [
          required,
          digitsOnlyWithDecimalPointAndMinusSign,
          signedNumberBetween(-180, 180),
        ],
        radius: [required, numberShouldBeBetweenMinAndMaxValue(1, 50000)],
      },
    };
  },
  computed: {
    scopeId() {
      // null = default location set; a number selects a company unit
      return this.scopeSelection?.id ?? null;
    },
    showNoLocationWarning() {
      // A company that enforces geofence with no location registered refuses
      // every punch, so say it here rather than let it surface at 8am Monday.
      return (
        this.scopeId !== null &&
        this.geofenceRequired &&
        this.locations.length === 0
      );
    },
    scopeOptions() {
      // oxd-select renders `option.label`; anything else shows up as a blank row
      const options = [
        {
          id: null,
          label: this.$t('attendance.geofence_scope_default'),
        },
      ];
      this.units.forEach((unit) => {
        options.push({
          id: unit.id,
          label: unit.name,
          _indent: unit.level,
        });
      });
      return options;
    },
  },
  beforeMount() {
    this.isLoading = true;
    Promise.all([this.loadUnits(), this.loadConfiguration()])
      .then(() => {
        // Pre-select the default scope option
        this.scopeSelection = this.scopeOptions[0];
      })
      .finally(() => {
        this.isLoading = false;
      });
  },
  methods: {
    loadUnits() {
      return this.subunitHttp
        .getAll()
        .then((response) => {
          const {data} = response.data;
          // The root node is the organization itself; keep only real units
          this.units = (data || []).filter((unit) => unit.level > 0);
        })
        .catch(() => {
          this.units = [];
        });
    },
    loadConfiguration() {
      const params = {};
      if (this.scopeId !== null) {
        params.subunitId = this.scopeId;
      }
      return this.geofenceHttp.getAll(params).then((response) => {
        const {data} = response.data;
        this.enabled = Boolean(data.enabled);
        this.geofenceRequired = Boolean(data.geofenceRequired);
        this.locations = (data.locations || []).map((location) => ({
          id: location.id ?? null,
          name: location.name,
          latitude: String(location.latitude),
          longitude: String(location.longitude),
          radius: String(location.radius),
        }));
      });
    },
    onScopeChange() {
      this.locations = [];
      this.isLoading = true;
      this.loadConfiguration().finally(() => {
        this.isLoading = false;
      });
    },
    onAddLocation() {
      this.locations.push({...emptyLocation});
    },
    onOpenMap(index) {
      this.mapModalLocation = index;
    },
    onMapApply({latitude, longitude, radius}) {
      if (this.mapModalLocation === null) {
        return;
      }
      this.locations[this.mapModalLocation].latitude = latitude.toFixed(6);
      this.locations[this.mapModalLocation].longitude = longitude.toFixed(6);
      if (radius) {
        this.locations[this.mapModalLocation].radius = String(radius);
      }
      this.mapModalLocation = null;
    },
    onRemoveLocation(index) {
      this.locations.splice(index, 1);
    },
    onSave() {
      this.isLoading = true;
      const payload = {
        enabled: this.enabled,
        subunitId: this.scopeId,
        geofenceRequired: this.geofenceRequired,
        locations: this.locations.map((location) => ({
          id: location.id,
          name: location.name,
          latitude: parseFloat(location.latitude),
          longitude: parseFloat(location.longitude),
          radius: parseInt(location.radius, 10),
        })),
      };
      this.geofenceHttp
        .request({
          method: 'PUT',
          data: payload,
        })
        .then((response) => {
          const {data} = response.data;
          this.geofenceRequired = Boolean(data.geofenceRequired);
          this.locations = (data.locations || []).map((location) => ({
            id: location.id ?? null,
            name: location.name,
            latitude: String(location.latitude),
            longitude: String(location.longitude),
            radius: String(location.radius),
          }));
          return this.$toast.saveSuccess();
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-geofence-field-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0;
}
.orangehrm-geofence-field-label {
  margin: 0;
  font-size: 0.8rem;
}
.orangehrm-geofence-hint {
  margin: 0 0 1rem;
  font-size: 0.75rem;
  color: #6c757d;
}
.orangehrm-geofence-actions {
  display: flex;
  align-items: flex-end;
  justify-content: flex-end;
  padding-bottom: 0.75rem;
}
.orangehrm-geofence-warning {
  margin: 0 0 1rem;
  padding: 0.5rem 0.75rem;
  border-left: 3px solid #ffa62f;
  background-color: #fff6e6;
  font-size: 0.8rem;
  color: #8a5300;
}
</style>
