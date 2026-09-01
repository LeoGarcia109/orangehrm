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
        {{ $t('attendance.announcements') }}
      </oxd-text>
      <oxd-divider />

      <oxd-form :loading="isLoading" @submit-valid="onPublish">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.title"
                :label="$t('attendance.announcement_title')"
                :rules="rules.title"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.scope"
                type="select"
                :label="$t('attendance.announcement_scope')"
                :options="scopeOptions"
                :clear="false"
              />
            </oxd-grid-item>
            <oxd-grid-item v-if="form.scope && form.scope.id === 'SUBUNIT'">
              <oxd-input-field
                v-model="form.subunit"
                type="select"
                :label="$t('general.sub_unit')"
                :options="unitOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item v-if="form.scope && form.scope.id === 'EMPLOYEE'">
              <oxd-input-field
                v-model="form.employeeId"
                :label="$t('attendance.announcement_employee_id')"
                :rules="rules.employeeId"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-form-row>
          <oxd-grid :cols="1" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.body"
                type="textarea"
                :label="$t('attendance.announcement_body')"
                :rules="rules.body"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.requiresAck"
                type="switch"
                :label="$t('attendance.announcement_requires_ack')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="form.expiresAt"
                type="date"
                :label="$t('attendance.announcement_expires_at')"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-divider />
        <oxd-form-actions>
          <oxd-button
            display-type="secondary"
            :label="$t('attendance.announcement_publish')"
            type="submit"
          />
        </oxd-form-actions>
      </oxd-form>
    </div>

    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('attendance.announcement_published') }}
      </oxd-text>
      <oxd-divider />

      <oxd-text v-if="!items.length" tag="p">
        {{ $t('general.no_records_found') }}
      </oxd-text>

      <table v-else class="ohrm-br-table">
        <thead>
          <tr>
            <th>{{ $t('attendance.announcement_title') }}</th>
            <th>{{ $t('attendance.announcement_scope') }}</th>
            <th>{{ $t('general.date') }}</th>
            <th>{{ $t('attendance.announcement_read_count') }}</th>
            <th>{{ $t('attendance.announcement_ack_count') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.title }}</td>
            <td>{{ scopeLabel(item) }}</td>
            <td>{{ item.publishedAt }}</td>
            <td>{{ item.readCount }}</td>
            <td>
              <template v-if="item.requiresAck">{{ item.ackCount }}</template>
              <template v-else>—</template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {
  required,
  shouldNotExceedCharLength,
} from '@ohrm/core/util/validation/rules';

export default {
  name: 'BrAnnouncements',
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/announcements',
    );
    const unitsHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/admin/subunits',
    );
    return {http, unitsHttp};
  },
  data() {
    return {
      isLoading: false,
      items: [],
      units: [],
      form: this.emptyForm(),
      rules: {
        title: [required, shouldNotExceedCharLength(150)],
        body: [required, shouldNotExceedCharLength(5000)],
        employeeId: [shouldNotExceedCharLength(10)],
      },
    };
  },
  computed: {
    scopeOptions() {
      return [
        {
          id: 'NETWORK',
          label: this.$t('attendance.announcement_scope_network'),
        },
        {
          id: 'SUBUNIT',
          label: this.$t('attendance.announcement_scope_subunit'),
        },
        {
          id: 'EMPLOYEE',
          label: this.$t('attendance.announcement_scope_employee'),
        },
      ];
    },
    // oxd-select renders option.label, so options keyed on `name` show up blank
    unitOptions() {
      return this.units.map((unit) => ({id: unit.id, label: unit.name}));
    },
  },
  beforeMount() {
    this.loadUnits();
    this.load();
  },
  methods: {
    emptyForm() {
      return {
        title: '',
        body: '',
        scope: {id: 'NETWORK', label: ''},
        subunit: null,
        employeeId: '',
        requiresAck: false,
        expiresAt: null,
      };
    },
    loadUnits() {
      return this.unitsHttp
        .getAll({limit: 0})
        .then((response) => {
          this.units = response.data.data;
        })
        .catch(() => {
          this.units = [];
        });
    },
    load() {
      this.isLoading = true;
      return this.http
        .request({method: 'GET', params: {sent: true}})
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
    scopeLabel(item) {
      if (item.scope === 'SUBUNIT') {
        return `${this.$t('attendance.announcement_scope_subunit')}: ${
          item.subunitName ?? '—'
        }`;
      }
      if (item.scope === 'EMPLOYEE') {
        return `${this.$t('attendance.announcement_scope_employee')}: ${
          item.employeeId ?? '—'
        }`;
      }
      return this.$t('attendance.announcement_scope_network');
    },
    onPublish() {
      this.isLoading = true;
      const scope = this.form.scope?.id ?? 'NETWORK';
      this.http
        .create({
          title: this.form.title,
          body: this.form.body,
          scope,
          subunitId: scope === 'SUBUNIT' ? this.form.subunit?.id : null,
          employeeId:
            scope === 'EMPLOYEE' ? Number(this.form.employeeId) || null : null,
          requiresAck: this.form.requiresAck,
          expiresAt: this.form.expiresAt || null,
        })
        .then(() => {
          this.form = this.emptyForm();
          return this.$toast.saveSuccess();
        })
        .then(() => this.load())
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>

<style src="./br-inbox.scss" lang="scss"></style>
