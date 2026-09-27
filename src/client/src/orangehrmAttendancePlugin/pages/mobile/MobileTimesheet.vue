<!--
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
-->

<template>
  <section v-if="sheet" class="ohrm-mobile__sheet">
    <header class="ohrm-mobile__sheet-head">
      <div>
        <span class="ohrm-mobile__sheet-eyebrow">
          {{ $t('attendance.timesheet_signature') }}
        </span>
        <h2 class="ohrm-mobile__sheet-title">{{ monthLabel }}</h2>
      </div>
      <span class="ohrm-mobile__sheet-chip" :class="`is-${status}`">
        {{ statusLabel }}
      </span>
    </header>

    <div class="ohrm-mobile__sheet-stats">
      <div class="ohrm-mobile__sheet-stat">
        <span class="ohrm-mobile__sheet-stat-value">
          {{ sheet.recordCount }}
        </span>
        <span class="ohrm-mobile__sheet-stat-label">
          {{ $t('attendance.timesheet_records') }}
        </span>
      </div>
      <div class="ohrm-mobile__sheet-stat">
        <span class="ohrm-mobile__sheet-stat-value">{{ totalLabel }}</span>
        <span class="ohrm-mobile__sheet-stat-label">
          {{ $t('attendance.timesheet_total') }}
        </span>
      </div>
    </div>

    <p
      v-if="sheet.signedAt"
      class="ohrm-mobile__sheet-note"
      :class="sheet.intact === false ? 'is-danger' : 'is-ok'"
    >
      <i
        class="oxd-icon"
        :class="
          sheet.intact === false
            ? 'bi-exclamation-triangle-fill'
            : 'bi-check-circle-fill'
        "
      ></i>
      <span>
        {{ $t('attendance.timesheet_signed_at') }} {{ sheet.signedAt }}
        <template v-if="sheet.intact === false">
          — {{ $t('attendance.timesheet_broken') }}
        </template>
      </span>
    </p>

    <p v-else-if="sheet.blocker" class="ohrm-mobile__sheet-note is-warn">
      <i class="oxd-icon bi-info-circle-fill"></i>
      <span>{{ sheet.blocker }}</span>
    </p>

    <button
      v-if="sheet.canSign && !confirming"
      class="ohrm-mobile__sheet-cta"
      @click="onStartSigning"
    >
      <i class="oxd-icon bi-pen"></i>
      {{ $t('attendance.timesheet_sign') }}
    </button>

    <!-- Signing is meant to stand up as evidence, so it is a separate, explicit
         step: what is being agreed to is spelled out, and only then is the
         password asked for. -->
    <div v-if="confirming" class="ohrm-mobile__sheet-confirm">
      <p class="ohrm-mobile__sheet-confirm-text">
        {{ $t('attendance.timesheet_confirm_text') }}
      </p>

      <label class="ohrm-mobile__sheet-label" for="ohrm-sheet-password">
        {{ $t('attendance.timesheet_confirm_password') }}
      </label>
      <input
        id="ohrm-sheet-password"
        ref="password"
        v-model="password"
        type="password"
        autocomplete="current-password"
        class="ohrm-mobile__sheet-input"
        @keyup.enter="onSign"
      />

      <p v-if="error" class="ohrm-mobile__sheet-error">{{ error }}</p>

      <div class="ohrm-mobile__sheet-actions">
        <button
          type="button"
          class="ohrm-mobile__sheet-btn ohrm-mobile__sheet-btn--secondary"
          @click="onCancel"
        >
          {{ $t('general.cancel') }}
        </button>
        <button
          type="button"
          class="ohrm-mobile__sheet-btn ohrm-mobile__sheet-btn--primary"
          :disabled="isSigning || !password"
          @click="onSign"
        >
          {{ $t('attendance.timesheet_confirm_sign') }}
        </button>
      </div>
    </div>
  </section>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';

export default {
  name: 'MobileTimesheet',
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/timesheet-signature',
    );
    // A wrong password and an open punch are answers, not failures
    http.setIgnorePath('/api/v2/attendance/br/timesheet-signature');
    return {http};
  },
  data() {
    return {
      sheet: null,
      confirming: false,
      password: '',
      error: null,
      isSigning: false,
    };
  },
  computed: {
    // The sheet signed at the start of a month is the previous one
    month() {
      const now = new Date();
      const previous = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      return `${previous.getFullYear()}-${String(
        previous.getMonth() + 1,
      ).padStart(2, '0')}`;
    },
    monthLabel() {
      const [year, month] = this.month.split('-').map(Number);
      const label = new Date(year, month - 1, 1).toLocaleDateString(undefined, {
        month: 'long',
        year: 'numeric',
      });
      return label.charAt(0).toUpperCase() + label.slice(1);
    },
    totalLabel() {
      const seconds = this.sheet?.totalSeconds ?? 0;
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.round((seconds % 3600) / 60);
      return `${hours}h ${String(minutes).padStart(2, '0')}m`;
    },
    status() {
      if (!this.sheet?.signedAt) return 'pending';
      return this.sheet.intact === false ? 'broken' : 'signed';
    },
    statusLabel() {
      if (this.status === 'broken')
        return this.$t('attendance.timesheet_broken');
      if (this.status === 'signed')
        return this.$t('attendance.timesheet_status_signed');
      return this.$t('attendance.timesheet_status_pending');
    },
  },
  beforeMount() {
    this.load();
  },
  methods: {
    load() {
      return this.http
        .request({method: 'GET', params: {month: this.month}})
        .then((response) => {
          this.sheet = response.data.data;
        })
        .catch(() => {
          this.sheet = null;
        });
    },
    onStartSigning() {
      this.confirming = true;
      this.error = null;
      this.$nextTick(() => this.$refs.password?.focus());
    },
    onCancel() {
      this.confirming = false;
      this.password = '';
      this.error = null;
    },
    onSign() {
      if (!this.password || this.isSigning) return;
      this.isSigning = true;
      this.error = null;
      this.http
        .request({
          method: 'POST',
          data: {month: this.month, password: this.password},
        })
        .then(() => {
          this.onCancel();
          return this.$toast.saveSuccess();
        })
        .then(() => this.load())
        .catch((e) => {
          this.error =
            e?.data?.error?.message ??
            e?.response?.data?.error?.message ??
            this.$t('general.error');
        })
        .finally(() => {
          this.isSigning = false;
        });
    },
  },
};
</script>

<style src="./mobile-timesheet.scss" lang="scss" scoped></style>
