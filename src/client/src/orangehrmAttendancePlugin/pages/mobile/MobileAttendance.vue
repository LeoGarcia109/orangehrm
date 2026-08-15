<template>
  <div class="ohrm-mobile">
    <header class="ohrm-mobile__header">
      <div class="ohrm-mobile__header-info">
        <span class="ohrm-mobile__greeting">{{ employeeName }}</span>
        <span class="ohrm-mobile__date">{{ formattedDate }}</span>
      </div>
      <oxd-button
        class="ohrm-mobile__logout"
        display-type="ghost"
        :label="$t('general.logout')"
        @click="onLogout"
      />
    </header>

    <section class="ohrm-mobile__clock-card">
      <div class="ohrm-mobile__clock">{{ currentTime }}</div>
      <div
        class="ohrm-mobile__status"
        :class="{
          'ohrm-mobile__status--in': isPunchedIn,
          'ohrm-mobile__status--out': !isPunchedIn,
        }"
      >
        {{
          isPunchedIn
            ? $t('attendance.punched_in')
            : $t('attendance.punched_out')
        }}
      </div>
      <div v-if="lastPunchLabel" class="ohrm-mobile__last-punch">
        {{ lastPunchLabel }}
      </div>
    </section>

    <section class="ohrm-mobile__location">
      <span class="ohrm-mobile__location-badge" :class="locationBadgeClass">
        <i class="oxd-icon bi-geo-alt-fill"></i>
        {{ locationStatusText }}
      </span>
      <span v-if="geofenceEnabled" class="ohrm-mobile__geofence-note">
        {{ $t('attendance.geofence_active') }}
      </span>
    </section>

    <section class="ohrm-mobile__punch">
      <button
        class="ohrm-mobile__punch-button"
        :class="{
          'ohrm-mobile__punch-button--in': !isPunchedIn,
          'ohrm-mobile__punch-button--out': isPunchedIn,
        }"
        :disabled="isLoading"
        @click="onPunch"
      >
        <span v-if="isLoading" class="ohrm-mobile__spinner"></span>
        <span v-else>{{
          isPunchedIn ? $t('attendance.out') : $t('attendance.in')
        }}</span>
      </button>
    </section>

    <section v-if="punchError" class="ohrm-mobile__error">
      <i class="oxd-icon bi-exclamation-triangle-fill"></i>
      {{ punchError }}
    </section>

    <section class="ohrm-mobile__note">
      <oxd-input-field
        v-model="punchNote"
        type="textarea"
        :label="$t('general.note')"
        :placeholder="$t('attendance.note_placeholder')"
        :rules="rules.note"
      />
    </section>

    <section v-if="todayRecords.length" class="ohrm-mobile__history">
      <oxd-text class="ohrm-mobile__history-title" tag="p">
        {{ $t('attendance.today_records') }}
      </oxd-text>
      <div
        v-for="record in todayRecords"
        :key="record.id"
        class="ohrm-mobile__history-item"
      >
        <span class="ohrm-mobile__history-time">
          <i class="oxd-icon bi-box-arrow-in-right"></i>
          {{ record.punchIn.userTime }}
        </span>
        <span v-if="record.punchOut" class="ohrm-mobile__history-time">
          <i class="oxd-icon bi-box-arrow-right"></i>
          {{ record.punchOut.userTime }}
        </span>
        <span v-else class="ohrm-mobile__history-open">
          {{ $t('attendance.in') }}
        </span>
      </div>
    </section>
  </div>
</template>

<script>
import {computed, toRefs} from 'vue';
import {
  parseDate,
  formatTime,
  formatDate,
  guessTimezone,
} from '@/core/util/helper/datefns';
import {shouldNotExceedCharLength} from '@/core/util/validation/rules';
import {APIService} from '@ohrm/core/util/services/api.service';
import {convertPHPDateFormat} from '@ohrm/oxd';
import useLocale from '@/core/util/composable/useLocale';
import useGeolocation from '@/orangehrmAttendancePlugin/composables/useGeolocation';

const GEO_STALE_MS = 30000;

export default {
  name: 'MobileAttendance',
  props: {
    // Injected by mobile.html.twig — same shape oxd-layout normally provides
    dateFormat: {
      type: Object,
      default: () => ({id: 'Y-m-d', label: 'yyyy-MM-dd'}),
    },
  },
  setup(props) {
    const {locale} = useLocale();
    const {dateFormat} = toRefs(props);
    const jsDateFormat = computed(() =>
      convertPHPDateFormat(dateFormat.value.id),
    );
    const userDateFormat = computed(() => dateFormat.value.label);
    const timeFormat = 'HH:mm';
    const jsTimeFormat = 'hh:mm a';

    const recordsHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/records',
    );
    // Geofence/business errors are surfaced inline with translated messages
    recordsHttp.setIgnorePath('/api/v2/attendance/records');
    const {getCoordinates} = useGeolocation();

    return {
      locale,
      recordsHttp,
      getCoordinates,
      userDateFormat,
      timeFormat,
      jsTimeFormat,
      jsDateFormat,
    };
  },
  data() {
    return {
      isLoading: false,
      isPunchedIn: false,
      lastRecord: null,
      employeeName: '',
      todayRecords: [],
      punchNote: '',
      geofenceEnabled: false,
      locationStatus: 'idle', // idle | locating | located | denied
      lastCoordinates: null,
      lastLocationAt: null,
      currentTime: '',
      clockTimer: null,
      punchError: null,
      rules: {
        note: [shouldNotExceedCharLength(250)],
      },
    };
  },
  computed: {
    formattedDate() {
      return formatDate(new Date(), this.jsDateFormat, {locale: this.locale});
    },
    locationBadgeClass() {
      if (this.locationStatus === 'located') return 'is-located';
      if (this.locationStatus === 'denied') return 'is-denied';
      return 'is-idle';
    },
    locationStatusText() {
      if (this.locationStatus === 'located') {
        return this.$t('attendance.location_captured');
      }
      if (this.locationStatus === 'denied') {
        return this.$t('attendance.location_denied');
      }
      return this.$t('attendance.locating');
    },
    lastPunchLabel() {
      if (!this.lastRecord) return null;
      const punch = this.isPunchedIn
        ? this.lastRecord.punchIn
        : this.lastRecord.punchOut;
      if (!punch || !punch.userTime) return null;
      const prefix = this.isPunchedIn
        ? this.$t('attendance.punched_in')
        : this.$t('attendance.punched_out');
      return `${prefix} - ${formatTime(
        parseDate(`${punch.userDate} ${punch.userTime}`),
        this.jsTimeFormat,
      )}`;
    },
  },
  beforeMount() {
    this.updateClock();
    this.clockTimer = setInterval(this.updateClock, 15000);
    this.refreshLocation();
    Promise.all([this.loadStatus(), this.loadGeofence(), this.loadToday()])
      .catch(() => null)
      .finally(() => {
        this.isLoading = false;
      });
  },
  beforeUnmount() {
    if (this.clockTimer) clearInterval(this.clockTimer);
  },
  methods: {
    updateClock() {
      const now = new Date();
      const hh = String(now.getHours()).padStart(2, '0');
      const mm = String(now.getMinutes()).padStart(2, '0');
      const ss = String(now.getSeconds()).padStart(2, '0');
      this.currentTime = `${hh}:${mm}:${ss}`;
    },
    loadStatus() {
      return this.recordsHttp
        .request({method: 'GET', url: '/api/v2/attendance/records/latest'})
        .then((response) => {
          const {data} = response.data;
          this.lastRecord = data;
          this.isPunchedIn = data.state?.id === 'PUNCHED IN';
          if (data.employee) {
            this.employeeName = [
              data.employee.firstName,
              data.employee.middleName,
              data.employee.lastName,
            ]
              .filter(Boolean)
              .join(' ');
          }
        });
    },
    loadGeofence() {
      return this.recordsHttp
        .request({method: 'GET', url: '/api/v2/attendance/geofence'})
        .then((response) => {
          this.geofenceEnabled = response.data.data.enabled;
        });
    },
    loadToday() {
      return this.recordsHttp.request({method: 'GET'}).then((response) => {
        const [records] = response.data.data;
        this.todayRecords = Array.isArray(records) ? records : [];
      });
    },
    async refreshLocation() {
      this.locationStatus = 'locating';
      const coords = await this.getCoordinates();
      if (coords) {
        this.locationStatus = 'located';
        this.lastCoordinates = coords;
        this.lastLocationAt = Date.now();
      } else {
        this.locationStatus = 'denied';
      }
    },
    async onPunch() {
      if (this.isLoading) return;
      this.isLoading = true;
      this.punchError = null;

      const timezone = guessTimezone();
      const now = new Date();

      // Fresh coordinates when stale (geofence is enforced server-side)
      if (
        !this.lastCoordinates ||
        Date.now() - this.lastLocationAt > GEO_STALE_MS
      ) {
        const coords = await this.getCoordinates();
        if (coords) {
          this.locationStatus = 'located';
          this.lastCoordinates = coords;
          this.lastLocationAt = Date.now();
        }
      }

      this.recordsHttp
        .request({
          method: this.isPunchedIn ? 'PUT' : 'POST',
          data: {
            date: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(
              2,
              '0',
            )}-${String(now.getDate()).padStart(2, '0')}`,
            time: `${String(now.getHours()).padStart(2, '0')}:${String(
              now.getMinutes(),
            ).padStart(2, '0')}`,
            note: this.punchNote || null,
            timezoneOffset: timezone.offset,
            timezoneName: timezone.name,
            latitude: this.lastCoordinates?.latitude ?? null,
            longitude: this.lastCoordinates?.longitude ?? null,
          },
        })
        .then(() => {
          this.punchNote = '';
          return this.$toast.saveSuccess();
        })
        .then(() => {
          return Promise.all([this.loadStatus(), this.loadToday()]);
        })
        .catch((error) => {
          // APIService rejects with the response object for ignored paths
          const message =
            error?.data?.error?.message ??
            error?.response?.data?.error?.message ??
            null;
          this.punchError = this.translatePunchError(message);
          this.refreshLocation();
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    translatePunchError(message) {
      if (!message) return this.$t('general.error');
      if (message.includes('Location Coordinates Required')) {
        return this.$t(
          'attendance.geofence_validation_failed_location_coordinates_required',
        );
      }
      if (message.includes('Location Outside Allowed Area')) {
        return this.$t(
          'attendance.geofence_validation_failed_location_outside_allowed_area',
        );
      }
      return message;
    },
    onLogout() {
      window.location.href = `${window.appGlobal.baseUrl}/auth/logout`;
    },
  },
};
</script>

<style src="./mobile-attendance.scss" lang="scss" scoped></style>
