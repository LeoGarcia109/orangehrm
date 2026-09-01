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
        {{ $t('attendance.absences') }}
      </oxd-text>
      <oxd-divider />

      <oxd-form-row>
        <oxd-grid :cols="3" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-input-field
              v-model="statusFilter"
              type="select"
              :label="$t('general.status')"
              :options="statusOptions"
              :clear="false"
              @update:model-value="load"
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>

      <oxd-divider />

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
            <th>{{ $t('attendance.absence_reason') }}</th>
            <th>{{ $t('general.date') }}</th>
            <th>{{ $t('attendance.absence_note') }}</th>
            <th>{{ $t('attendance.absence_attach') }}</th>
            <th>{{ $t('general.status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.employeeName }}</td>
            <td>
              {{
                $t(`attendance.absence_reason_${item.reasonType.toLowerCase()}`)
              }}
            </td>
            <td>
              {{ item.fromDate }}
              <template v-if="item.toDate !== item.fromDate">
                — {{ item.toDate }}
              </template>
            </td>
            <td class="ohrm-br-table__note">
              {{ item.note }}
              <div v-if="item.decisionNote" class="ohrm-br-table__decision">
                {{ item.decisionNote }}
              </div>
            </td>
            <td>
              <a
                v-if="item.hasAttachment"
                :href="documentUrl(item.id)"
                target="_blank"
                rel="noopener"
              >
                {{ item.attachmentName }}
              </a>
              <template v-else>—</template>
            </td>
            <td>
              <span
                class="ohrm-br-status"
                :class="`is-${item.status.toLowerCase()}`"
              >
                {{
                  $t(`attendance.absence_status_${item.status.toLowerCase()}`)
                }}
              </span>
            </td>
            <td>
              <template v-if="item.status === 'PENDING'">
                <oxd-button
                  display-type="success"
                  size="small"
                  :label="$t('attendance.absence_approve')"
                  @click="onApprove(item)"
                />
                <oxd-button
                  display-type="danger"
                  size="small"
                  :label="$t('attendance.absence_reject')"
                  @click="onOpenReject(item)"
                />
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <oxd-dialog v-if="rejecting" @update:show="rejecting = null">
      <oxd-text tag="p" class="orangehrm-modal-title">
        {{ $t('attendance.absence_reject') }}
      </oxd-text>
      <oxd-input-field
        v-model="rejectNote"
        type="textarea"
        :label="$t('attendance.absence_reject_reason')"
        :rules="rules.rejectNote"
      />
      <p v-if="error" class="ohrm-br-error">{{ error }}</p>
      <oxd-form-actions>
        <oxd-button
          display-type="ghost"
          :label="$t('general.cancel')"
          @click="rejecting = null"
        />
        <oxd-button
          display-type="danger"
          :label="$t('attendance.absence_reject')"
          @click="onReject"
        />
      </oxd-form-actions>
    </oxd-dialog>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {
  required,
  shouldNotExceedCharLength,
} from '@ohrm/core/util/validation/rules';

export default {
  name: 'BrAbsences',
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/absences',
    );
    // Business refusals (already settled, missing reason) are shown inline
    http.setIgnorePath('/api/v2/attendance/br/absences');
    return {http};
  },
  data() {
    return {
      isLoading: false,
      items: [],
      statusFilter: {id: 'PENDING', label: ''},
      rejecting: null,
      rejectNote: '',
      error: null,
      rules: {
        rejectNote: [required, shouldNotExceedCharLength(2000)],
      },
    };
  },
  computed: {
    statusOptions() {
      return [
        {id: 'PENDING', label: this.$t('attendance.absence_status_pending')},
        {id: 'APPROVED', label: this.$t('attendance.absence_status_approved')},
        {id: 'REJECTED', label: this.$t('attendance.absence_status_rejected')},
        {id: 'ALL', label: this.$t('general.all')},
      ];
    },
  },
  beforeMount() {
    this.load();
  },
  methods: {
    documentUrl(id) {
      return `${window.appGlobal.baseUrl}/attendance/brAbsenceDocument/${id}`;
    },
    load() {
      this.isLoading = true;
      const status = this.statusFilter?.id ?? 'PENDING';
      return this.http
        .request({
          method: 'GET',
          params: {queue: true, status: status === 'ALL' ? null : status},
        })
        .then((response) => {
          this.items = response.data.data;
        })
        .catch(() => {
          this.items = [];
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    onApprove(item) {
      this.decide(item, 'APPROVED', null);
    },
    onOpenReject(item) {
      this.rejecting = item;
      this.rejectNote = '';
      this.error = null;
    },
    onReject() {
      this.decide(this.rejecting, 'REJECTED', this.rejectNote);
    },
    decide(item, decision, decisionNote) {
      this.isLoading = true;
      this.error = null;
      this.http
        .request({
          method: 'PUT',
          data: {id: item.id, decision, decisionNote},
        })
        .then(() => {
          this.rejecting = null;
          return this.$toast.updateSuccess();
        })
        .then(() => this.load())
        .catch((e) => {
          this.error =
            e?.data?.error?.message ??
            e?.response?.data?.error?.message ??
            this.$t('general.error');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>

<style src="./br-inbox.scss" lang="scss"></style>
