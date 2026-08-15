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
  <oxd-dialog
    class="orangehrm-dialog-modal orangehrm-geofence-map-dialog"
    @update:show="onCancel"
  >
    <div class="orangehrm-modal-header">
      <oxd-text type="card-title">
        {{ $t('attendance.geofence_map_title') }}
      </oxd-text>
    </div>

    <div class="orangehrm-geofence-map-search">
      <oxd-input-field
        v-model="searchQuery"
        :label="$t('attendance.geofence_search_address')"
        :placeholder="'Av. Paulista, 1000, Sao Paulo'"
        @keydown.enter.prevent="onSearch"
      />
      <oxd-button
        type="button"
        display-type="secondary"
        :label="$t('attendance.geofence_search')"
        :loading="isSearching"
        @click="onSearch"
      />
    </div>

    <oxd-text v-if="searchError" tag="p" class="orangehrm-geofence-map-error">
      {{ searchError }}
    </oxd-text>

    <ul v-if="searchResults.length" class="orangehrm-geofence-map-results">
      <li
        v-for="(result, index) in searchResults"
        :key="index"
        @click="onSelectResult(result)"
      >
        {{ result.display_name }}
      </li>
    </ul>

    <div ref="mapContainer" class="orangehrm-geofence-map"></div>

    <oxd-text tag="p" class="orangehrm-geofence-map-hint">
      {{ $t('attendance.geofence_map_hint') }}
    </oxd-text>

    <oxd-grid
      :cols="3"
      class="orangehrm-full-width-grid orangehrm-geofence-map-fields"
    >
      <oxd-input-field
        v-model="latInput"
        :label="$t('attendance.latitude')"
        :placeholder="'-23.550520'"
        @update:model-value="onManualCoords"
      />
      <oxd-input-field
        v-model="lngInput"
        :label="$t('attendance.longitude')"
        :placeholder="'-46.633308'"
        @update:model-value="onManualCoords"
      />
      <oxd-input-field
        v-model="radiusInput"
        :label="$t('attendance.radius_meters')"
        :placeholder="'300'"
        @update:model-value="onRadiusChange"
      />
    </oxd-grid>

    <div class="orangehrm-geofence-map-actions">
      <oxd-button
        type="button"
        display-type="ghost"
        icon-name="geo-alt"
        :label="$t('attendance.geofence_use_my_location')"
        @click="onUseMyLocation"
      />
      <oxd-button
        type="button"
        display-type="ghost"
        :label="$t('general.cancel')"
        @click="onCancel"
      />
      <oxd-button
        type="button"
        :label="$t('general.save')"
        :disabled="!selection"
        @click="onConfirm"
      />
    </div>
  </oxd-dialog>
</template>

<script>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import {OxdDialog} from '@ohrm/oxd';

// Leaflet's default icon URLs are not bundled automatically; fix them here.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
});

// Default center: Sao Paulo (most common deployment)
const DEFAULT_CENTER = {lat: -23.55052, lng: -46.633308};
const DEFAULT_ZOOM = 13;

export default {
  name: 'GeofenceLocationMapModal',
  components: {
    'oxd-dialog': OxdDialog,
  },
  props: {
    latitude: {
      type: [Number, String],
      default: null,
    },
    longitude: {
      type: [Number, String],
      default: null,
    },
    radius: {
      type: [Number, String],
      default: 300,
    },
  },
  emits: ['apply', 'close'],
  data() {
    return {
      map: null,
      marker: null,
      circle: null,
      selection: null,
      latInput: '',
      lngInput: '',
      radiusInput: String(this.radius ?? 300),
      searchQuery: '',
      searchResults: [],
      searchError: null,
      isSearching: false,
    };
  },
  computed: {
    initialCenter() {
      const lat = parseFloat(this.latitude);
      const lng = parseFloat(this.longitude);
      if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
        return {lat, lng};
      }
      return DEFAULT_CENTER;
    },
    radiusMeters() {
      const radius = parseInt(this.radiusInput, 10);
      return Number.isNaN(radius) || radius < 1 ? 300 : radius;
    },
  },
  mounted() {
    this.initMap();
  },
  beforeUnmount() {
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  },
  methods: {
    initMap() {
      this.map = L.map(this.$refs.mapContainer, {
        center: [this.initialCenter.lat, this.initialCenter.lng],
        zoom: DEFAULT_ZOOM,
      });
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
      }).addTo(this.map);

      this.map.on('click', (event) => {
        this.setSelection(event.latlng.lat, event.latlng.lng);
      });

      const hasCoords =
        !Number.isNaN(parseFloat(this.latitude)) &&
        !Number.isNaN(parseFloat(this.longitude));
      if (hasCoords) {
        this.setSelection(this.initialCenter.lat, this.initialCenter.lng, true);
      }

      // The dialog animates in; let Leaflet recalculate its size afterwards.
      setTimeout(() => {
        if (this.map) {
          this.map.invalidateSize();
        }
      }, 250);
    },
    setSelection(lat, lng, silentZoom = false) {
      this.selection = {lat, lng};
      this.latInput = lat.toFixed(6);
      this.lngInput = lng.toFixed(6);
      const latLng = L.latLng(lat, lng);

      if (!this.marker) {
        this.marker = L.marker(latLng).addTo(this.map);
      } else {
        this.marker.setLatLng(latLng);
      }

      if (!this.circle) {
        this.circle = L.circle(latLng, {
          radius: this.radiusMeters,
          color: '#ff7300',
          fillColor: '#ff7300',
          fillOpacity: 0.15,
        }).addTo(this.map);
      } else {
        this.circle.setLatLng(latLng);
        this.circle.setRadius(this.radiusMeters);
      }

      if (!silentZoom) {
        this.map.setView(latLng, Math.max(this.map.getZoom(), DEFAULT_ZOOM));
      }
    },
    onSearch() {
      const query = this.searchQuery.trim();
      if (!query) {
        return;
      }
      this.isSearching = true;
      this.searchError = null;
      this.searchResults = [];

      const url =
        'https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' +
        encodeURIComponent(query);
      fetch(url, {headers: {'Accept-Language': 'pt-BR'}})
        .then((response) => {
          if (!response.ok) {
            throw new Error('search failed');
          }
          return response.json();
        })
        .then((results) => {
          if (!results.length) {
            this.searchError = this.$t('attendance.geofence_no_results');
            return;
          }
          this.searchResults = results;
        })
        .catch(() => {
          this.searchError = this.$t('attendance.geofence_no_results');
        })
        .finally(() => {
          this.isSearching = false;
        });
    },
    onSelectResult(result) {
      const lat = parseFloat(result.lat);
      const lng = parseFloat(result.lon);
      this.searchResults = [];
      this.setSelection(lat, lng);
      this.map.setView(L.latLng(lat, lng), 17);
    },
    onManualCoords() {
      const lat = parseFloat(this.latInput);
      const lng = parseFloat(this.lngInput);
      if (Number.isNaN(lat) || Number.isNaN(lng)) {
        return;
      }
      if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
        return;
      }
      this.setSelection(lat, lng, true);
    },
    onRadiusChange() {
      if (this.circle) {
        this.circle.setRadius(this.radiusMeters);
      }
    },
    onUseMyLocation() {
      if (!navigator.geolocation) {
        this.searchError = this.$t('attendance.geofence_no_results');
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          this.setSelection(lat, lng);
          this.map.setView(L.latLng(lat, lng), 17);
        },
        () => {
          this.searchError = this.$t('attendance.geofence_no_results');
        },
        {enableHighAccuracy: true, timeout: 10000},
      );
    },
    onConfirm() {
      if (!this.selection) {
        return;
      }
      this.$emit('apply', {
        latitude: this.selection.lat,
        longitude: this.selection.lng,
        radius: this.radiusMeters,
      });
      this.$emit('close');
    },
    onCancel() {
      this.$emit('close');
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-geofence-map-dialog {
  max-width: 720px;
  width: 95%;
}
.orangehrm-geofence-map {
  height: 380px;
  width: 100%;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
  z-index: 0;
}
.orangehrm-geofence-map-search {
  display: flex;
  gap: 0.5rem;
  align-items: flex-end;
  margin-bottom: 0.5rem;
}
.orangehrm-geofence-map-hint,
.orangehrm-geofence-map-fields {
  margin: 0.5rem 0 0;
}
.orangehrm-geofence-map-fields {
  margin-top: 0.75rem;
}
.orangehrm-geofence-map-error {
  margin: 0 0 0.5rem;
  font-size: 0.75rem;
  color: #dc3545;
}
.orangehrm-geofence-map-results {
  list-style: none;
  margin: 0 0 0.5rem;
  padding: 0;
  max-height: 120px;
  overflow-y: auto;
  border: 1px solid #e0e0e0;
  border-radius: 6px;

  li {
    padding: 0.4rem 0.6rem;
    font-size: 0.8rem;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;

    &:hover {
      background-color: #f7f7f7;
    }

    &:last-child {
      border-bottom: none;
    }
  }
}

.orangehrm-geofence-map-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
}
</style>
