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

    <nav class="ohrm-mobile__tabs">
      <button
        class="ohrm-mobile__tab"
        :class="{'ohrm-mobile__tab--active': tab === 'punch'}"
        @click="tab = 'punch'"
      >
        <i class="oxd-icon bi-stopwatch"></i>
        {{ $t('general.punch_in_out') }}
      </button>
      <button
        class="ohrm-mobile__tab"
        :class="{'ohrm-mobile__tab--active': tab === 'history'}"
        @click="onOpenHistory"
      >
        <i class="oxd-icon bi-clock-history"></i>
        {{ $t('attendance.history') }}
      </button>
      <button
        class="ohrm-mobile__tab"
        :class="{'ohrm-mobile__tab--active': tab === 'announcements'}"
        @click="tab = 'announcements'"
      >
        <i class="oxd-icon bi-megaphone"></i>
        {{ $t('attendance.announcements') }}
        <span v-if="pendingAckCount" class="ohrm-mobile__tab-badge">
          {{ pendingAckCount }}
        </span>
      </button>
      <button
        class="ohrm-mobile__tab"
        :class="{'ohrm-mobile__tab--active': tab === 'absences'}"
        @click="tab = 'absences'"
      >
        <i class="oxd-icon bi-file-earmark-medical"></i>
        {{ $t('attendance.absences') }}
      </button>
    </nav>

    <mobile-announcements
      v-if="tab === 'announcements'"
      @pending-changed="pendingAckCount = $event"
    />

    <mobile-absences v-if="tab === 'absences'" />

    <template v-if="tab === 'punch'">
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

      <section v-if="pendingCount" class="ohrm-mobile__pending">
        <span class="ohrm-mobile__pending-text">
          <i class="oxd-icon bi-cloud-arrow-up"></i>
          {{ pendingCount }} {{ $t('attendance.offline_punch_pending') }}
        </span>
        <button
          class="ohrm-mobile__pending-action"
          :disabled="isSyncing"
          @click="flushQueue"
        >
          {{ $t('attendance.offline_punch_sync_now') }}
        </button>
      </section>

      <section v-if="offlineNotice" class="ohrm-mobile__offline-notice">
        {{ offlineNotice }}
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

      <section v-if="todayRecords.length" class="ohrm-mobile__today">
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
    </template>

    <template v-if="tab === 'history'">
      <section class="ohrm-mobile__history-nav">
        <button class="ohrm-mobile__history-arrow" @click="onHistoryPrev">
          <i class="oxd-icon bi-chevron-left"></i>
        </button>
        <div class="ohrm-mobile__history-date">
          <span class="ohrm-mobile__history-date-label">
            {{ historyDateLabel }}
          </span>
          <button
            v-if="historyDate !== todayIso"
            class="ohrm-mobile__history-today-link"
            @click="onHistoryToday"
          >
            {{ $t('general.today') }}
          </button>
        </div>
        <button
          class="ohrm-mobile__history-arrow"
          :disabled="historyDate >= todayIso"
          @click="onHistoryNext"
        >
          <i class="oxd-icon bi-chevron-right"></i>
        </button>
      </section>

      <section v-if="historyLoading" class="ohrm-mobile__history-empty">
        {{ $t('attendance.history_loading') }}
      </section>

      <section
        v-else-if="!historyRecords.length"
        class="ohrm-mobile__history-empty"
      >
        {{ $t('general.no_records_found') }}
      </section>

      <section v-else class="ohrm-mobile__history-list">
        <div
          v-for="record in historyRecords"
          :key="record.id"
          class="ohrm-mobile__history-card"
        >
          <div class="ohrm-mobile__history-card-row">
            <span class="ohrm-mobile__history-card-time">
              <i class="oxd-icon bi-box-arrow-in-right"></i>
              {{ record.punchIn.userTime }}
            </span>
            <span class="ohrm-mobile__history-card-duration">
              {{ record.duration }}h
            </span>
            <span class="ohrm-mobile__history-card-time">
              <i v-if="record.punchOut" class="oxd-icon bi-box-arrow-right"></i>
              {{ record.punchOut ? record.punchOut.userTime : '—' }}
            </span>
          </div>
          <div
            v-if="
              record.punchIn.note || (record.punchOut && record.punchOut.note)
            "
            class="ohrm-mobile__history-card-note"
          >
            {{ record.punchIn.note || record.punchOut.note }}
          </div>
        </div>
        <div class="ohrm-mobile__history-total">
          {{ $t('general.total') }}: {{ historyTotalLabel }}
        </div>
      </section>
    </template>
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
import MobileAnnouncements from './MobileAnnouncements.vue';
import MobileAbsences from './MobileAbsences.vue';
import useOfflinePunchQueue, {
  isUndelivered,
} from '@/orangehrmAttendancePlugin/composables/useOfflinePunchQueue';

const GEO_STALE_MS = 30000;

export default {
  name: 'MobileAttendance',
  components: {
    'mobile-announcements': MobileAnnouncements,
    'mobile-absences': MobileAbsences,
  },
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
    const offlineQueue = useOfflinePunchQueue();

    return {
      locale,
      recordsHttp,
      getCoordinates,
      offlineQueue,
      userDateFormat,
      timeFormat,
      jsTimeFormat,
      jsDateFormat,
    };
  },
  data() {
    return {
      tab: 'punch',
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
      pendingCount: 0,
      pendingAckCount: 0,
      isSyncing: false,
      offlineNotice: null,
      historyDate: null,
      historyRecords: [],
      historyLoading: false,
      historyTotalSeconds: 0,
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
      const parsed = parseDate(
        `${punch.userDate} ${punch.userTime}`,
        'yyyy-MM-dd HH:mm',
      );
      const label = parsed
        ? formatTime(parsed, this.jsTimeFormat)
        : punch.userTime;
      return `${prefix} - ${label}`;
    },
    todayIso() {
      const now = new Date();
      return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(
        2,
        '0',
      )}-${String(now.getDate()).padStart(2, '0')}`;
    },
    historyDateLabel() {
      if (!this.historyDate) return '';
      const parsed = parseDate(this.historyDate);
      return parsed
        ? formatDate(parsed, this.jsDateFormat, {locale: this.locale})
        : this.historyDate;
    },
    historyTotalLabel() {
      const hours = Math.floor(this.historyTotalSeconds / 3600);
      const minutes = Math.round((this.historyTotalSeconds % 3600) / 60);
      return `${hours}h ${String(minutes).padStart(2, '0')}m`;
    },
  },
  beforeMount() {
    this.updateClock();
    this.clockTimer = setInterval(this.updateClock, 15000);
    this.refreshLocation();
    this.pendingCount = this.offlineQueue.size();
    window.addEventListener('online', this.flushQueue);
    this.loadPendingAck();
    Promise.all([this.loadStatus(), this.loadGeofence(), this.loadToday()])
      .catch(() => null)
      .finally(() => {
        this.isLoading = false;
        // Anything queued from a previous session goes out before the employee
        // has a chance to punch again on top of it.
        if (this.pendingCount) this.flushQueue();
      });
  },
  beforeUnmount() {
    if (this.clockTimer) clearInterval(this.clockTimer);
    window.removeEventListener('online', this.flushQueue);
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
    loadPendingAck() {
      // The badge has to be right before the employee opens the tab -- an
      // acknowledgement nobody knows is owed does not get given.
      return this.recordsHttp
        .request({
          method: 'GET',
          url: '/api/v2/attendance/br/announcements',
        })
        .then((response) => {
          this.pendingAckCount = response.data.meta?.pendingAck ?? 0;
        })
        .catch(() => {
          this.pendingAckCount = 0;
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
      return this.recordsHttp
        .request({method: 'GET'})
        .then((response) => {
          this.todayRecords = response.data.data;
        })
        .catch(() => {
          this.todayRecords = [];
        });
    },
    onOpenHistory() {
      this.tab = 'history';
      if (!this.historyDate) {
        this.historyDate = this.todayIso;
      }
      this.loadHistoryRecords();
    },
    loadHistoryRecords() {
      if (!this.historyDate) return;
      this.historyLoading = true;
      this.recordsHttp
        .request({
          method: 'GET',
          params: {
            fromDate: this.historyDate,
            toDate: this.historyDate,
            limit: 100,
          },
        })
        .then((response) => {
          this.historyRecords = response.data.data;
          const total = response.data.meta?.sum;
          this.historyTotalSeconds = total
            ? total.hours * 3600 + total.minutes * 60
            : 0;
        })
        .catch(() => {
          this.historyRecords = [];
          this.historyTotalSeconds = 0;
        })
        .finally(() => {
          this.historyLoading = false;
        });
    },
    shiftHistoryDate(days) {
      const parsed = parseDate(this.historyDate);
      if (!parsed) return;
      parsed.setDate(parsed.getDate() + days);
      this.historyDate = formatDate(parsed, 'yyyy-MM-dd');
      this.loadHistoryRecords();
    },
    onHistoryPrev() {
      this.shiftHistoryDate(-1);
    },
    onHistoryNext() {
      if (this.historyDate < this.todayIso) {
        this.shiftHistoryDate(1);
      }
    },
    onHistoryToday() {
      this.historyDate = this.todayIso;
      this.loadHistoryRecords();
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
    buildPunchPayload(now) {
      const timezone = guessTimezone();
      return {
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
      };
    },
    async onPunch() {
      if (this.isLoading) return;
      this.isLoading = true;
      this.punchError = null;
      this.offlineNotice = null;

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

      const method = this.isPunchedIn ? 'PUT' : 'POST';
      const payload = this.buildPunchPayload(now);

      this.recordsHttp
        .request({method, data: payload})
        .then(() => {
          this.punchNote = '';
          return this.$toast.saveSuccess();
        })
        .then(() => {
          return Promise.all([this.loadStatus(), this.loadToday()]);
        })
        .catch((error) => {
          // A request that never reached the server is not a refusal: the
          // punch happened, and losing it would cost the employee the hours.
          if (isUndelivered(error)) {
            this.queuePunch(method, payload);
            return;
          }
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
    queuePunch(method, payload) {
      const stored = this.offlineQueue.enqueue(method, payload);
      if (!stored) {
        // Storage refused it, so there is nowhere to keep the punch. Saying so
        // beats a success message over a punch that no longer exists.
        this.punchError = this.$t('attendance.offline_punch_not_stored');
        return;
      }
      this.punchNote = '';
      this.pendingCount = this.offlineQueue.size();
      this.offlineNotice = this.$t('attendance.offline_punch_saved');
      // Flip locally so the next punch queues as the matching out (or in).
      this.isPunchedIn = !this.isPunchedIn;
    },
    async flushQueue() {
      if (this.isSyncing || !this.offlineQueue.size()) return;
      this.isSyncing = true;

      const result = await this.offlineQueue.flush((item) =>
        this.recordsHttp.request({method: item.method, data: item.payload}),
      );

      this.pendingCount = result.pending;
      if (result.rejected.length) {
        const message =
          result.rejected[0].error?.data?.error?.message ??
          result.rejected[0].error?.response?.data?.error?.message ??
          null;
        this.punchError = this.translatePunchError(message);
      }
      if (result.synced) {
        this.offlineNotice = this.$t('attendance.offline_punch_synced');
        await Promise.all([this.loadStatus(), this.loadToday()]).catch(
          () => null,
        );
      }
      this.isSyncing = false;
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
      if (message.includes('Employee Has No Company Unit')) {
        return this.$t('attendance.geofence_missing_subunit');
      }
      if (message.includes('No Location Registered For This Company')) {
        return this.$t('attendance.geofence_not_configured');
      }
      if (message.includes('janela de')) {
        return this.$t('attendance.offline_punch_too_old');
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
