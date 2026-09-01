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
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('attendance.timesheet_signature') }}
      </oxd-text>
      <oxd-divider />

      <oxd-form-row>
        <oxd-grid :cols="3" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-input-field
              v-model="month"
              :label="$t('attendance.timesheet_month')"
              placeholder="AAAA-MM"
              @update:model-value="load"
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>

      <oxd-divider />

      <div v-if="broken" class="ohrm-br-alert">
        {{ broken }} {{ $t('attendance.timesheet_broken') }}
      </div>

      <oxd-text v-if="isLoading" tag="p">
        {{ $t('attendance.history_loading') }}
      </oxd-text>
      <oxd-text v-else-if="!items.length" tag="p">
        {{ $t('general.no_records_found') }}
      </oxd-text>

      <table v-else class="ohrm-br-table">
        <thead>
          <tr>
            <th>{{ $t('general.employee') }}</th>
            <th>{{ $t('attendance.timesheet_signed_at') }}</th>
            <th>{{ $t('attendance.timesheet_records') }}</th>
            <th>{{ $t('attendance.timesheet_total') }}</th>
            <th>IP</th>
            <th>{{ $t('attendance.timesheet_integrity') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.employeeId">
            <td>{{ item.employeeName }}</td>
            <td>{{ item.signedAt }}</td>
            <td>{{ item.recordCount }}</td>
            <td>{{ hours(item.totalSeconds) }}</td>
            <td>{{ item.ipAddress || '—' }}</td>
            <td>
              <span
                class="ohrm-br-status"
                :class="item.intact ? 'is-approved' : 'is-rejected'"
              >
                {{
                  item.intact
                    ? $t('attendance.timesheet_intact')
                    : $t('attendance.timesheet_broken')
                }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';

export default {
  name: 'BrTimesheets',
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/timesheet-signature',
    );
    return {http};
  },
  data() {
    const now = new Date();
    const previous = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    return {
      isLoading: false,
      items: [],
      broken: 0,
      month: `${previous.getFullYear()}-${String(
        previous.getMonth() + 1,
      ).padStart(2, '0')}`,
    };
  },
  beforeMount() {
    this.load();
  },
  methods: {
    hours(seconds) {
      const h = Math.floor((seconds ?? 0) / 3600);
      const m = Math.round(((seconds ?? 0) % 3600) / 60);
      return `${h}h ${String(m).padStart(2, '0')}m`;
    },
    load() {
      if (!/^\d{4}-\d{2}$/.test(this.month)) return Promise.resolve();
      this.isLoading = true;
      return this.http
        .request({method: 'GET', params: {queue: true, month: this.month}})
        .then((response) => {
          this.items = response.data.data;
          this.broken = response.data.meta?.broken ?? 0;
        })
        .catch(() => {
          this.items = [];
          this.broken = 0;
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>

<style src="./br-inbox.scss" lang="scss"></style>
