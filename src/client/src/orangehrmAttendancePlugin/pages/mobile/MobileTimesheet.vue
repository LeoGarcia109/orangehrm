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
      <span class="ohrm-mobile__sheet-title">
        <i class="oxd-icon bi-file-earmark-text"></i>
        {{ $t('attendance.timesheet_signature') }} — {{ monthLabel }}
      </span>
    </header>

    <div class="ohrm-mobile__sheet-figures">
      <div>
        <span class="ohrm-mobile__sheet-figure">{{ sheet.recordCount }}</span>
        <span class="ohrm-mobile__sheet-caption">
          {{ $t('attendance.timesheet_records') }}
        </span>
      </div>
      <div>
        <span class="ohrm-mobile__sheet-figure">{{ totalLabel }}</span>
        <span class="ohrm-mobile__sheet-caption">
          {{ $t('attendance.timesheet_total') }}
        </span>
      </div>
    </div>

    <div v-if="sheet.signedAt" class="ohrm-mobile__sheet-signed">
      <i class="oxd-icon bi-check-circle-fill"></i>
      {{ $t('attendance.timesheet_signed_at') }} {{ sheet.signedAt }}
      <span v-if="sheet.intact === false" class="ohrm-mobile__sheet-broken">
        {{ $t('attendance.timesheet_broken') }}
      </span>
    </div>

    <template v-else>
      <p v-if="sheet.blocker" class="ohrm-mobile__sheet-blocker">
        {{ sheet.blocker }}
      </p>

      <template v-else-if="sheet.canSign">
        <label class="ohrm-mobile__field">
          <span>{{ $t('attendance.timesheet_confirm_password') }}</span>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            class="ohrm-mobile__input"
          />
        </label>
        <p v-if="error" class="ohrm-mobile__absence-error">{{ error }}</p>
        <button
          class="ohrm-mobile__sheet-sign"
          :disabled="isSigning || !password"
          @click="onSign"
        >
          {{ $t('attendance.timesheet_sign') }}
        </button>
      </template>
    </template>
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
      const [year, month] = this.month.split('-');
      return `${month}/${year}`;
    },
    totalLabel() {
      const seconds = this.sheet?.totalSeconds ?? 0;
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.round((seconds % 3600) / 60);
      return `${hours}h ${String(minutes).padStart(2, '0')}m`;
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
    onSign() {
      this.isSigning = true;
      this.error = null;
      this.http
        .request({
          method: 'POST',
          data: {month: this.month, password: this.password},
        })
        .then(() => {
          this.password = '';
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
