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
  <div class="orangehrm-horizontal-padding orangehrm-vertical-padding">
    <oxd-text tag="h6" class="orangehrm-main-title">
      Relatório de Jornada
    </oxd-text>
    <oxd-divider />

    <oxd-form :loading="isLoading" @submit-valid="onSearch">
      <oxd-form-row>
        <oxd-grid :cols="4" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <employee-autocomplete
              v-model="filters.employee"
              :rules="rules.employee"
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <date-input
              v-model="filters.fromDate"
              :label="$t('general.from')"
              :rules="rules.fromDate"
              required
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <date-input
              v-model="filters.toDate"
              :label="$t('general.to')"
              :rules="rules.toDate"
              required
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-input-field
              v-model="filters.businessDays"
              type="number"
              label="Dias úteis (opcional)"
              :min="1"
              :max="31"
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>
      <oxd-divider />
      <oxd-form-actions>
        <oxd-button
          type="submit"
          display-type="secondary"
          label="Gerar Relatório"
        />
        <oxd-button
          v-if="report"
          type="button"
          display-type="ghost"
          label="Baixar AFD"
          @click="downloadAfd"
        />
      </oxd-form-actions>
    </oxd-form>

    <template v-if="report">
      <oxd-divider />

      <!-- Summary cards -->
      <oxd-grid :cols="4" class="orangehrm-full-width-grid">
        <oxd-grid-item>
          <div class="br-report-card">
            <oxd-text tag="p" class="br-report-card-label">Trabalhado</oxd-text>
            <oxd-text tag="p" class="br-report-card-value">{{
              report.workedFormatted
            }}</oxd-text>
          </div>
        </oxd-grid-item>
        <oxd-grid-item>
          <div class="br-report-card">
            <oxd-text tag="p" class="br-report-card-label">Esperado</oxd-text>
            <oxd-text tag="p" class="br-report-card-value">{{
              report.expectedFormatted
            }}</oxd-text>
          </div>
        </oxd-grid-item>
        <oxd-grid-item>
          <div class="br-report-card">
            <oxd-text tag="p" class="br-report-card-label"
              >Saldo Banco de Horas</oxd-text
            >
            <oxd-text
              tag="p"
              class="br-report-card-value"
              :class="balanceClass"
            >
              {{ report.balanceFormatted }}
            </oxd-text>
          </div>
        </oxd-grid-item>
        <oxd-grid-item>
          <div class="br-report-card">
            <oxd-text tag="p" class="br-report-card-label"
              >Horas Extras</oxd-text
            >
            <oxd-text tag="p" class="br-report-card-value">{{
              report.overtimeFormatted
            }}</oxd-text>
          </div>
        </oxd-grid-item>
      </oxd-grid>

      <oxd-divider />

      <!-- Daily breakdown table -->
      <oxd-text tag="h6" class="orangehrm-sub-title"
        >Detalhamento Diário</oxd-text
      >
      <oxd-table
        :headers="headers"
        :items="report.dailyBreakdown"
        :loading="isLoading"
      >
        <template #cell(date)="{item}">
          {{ formatDateBr(item.date) }}
        </template>
        <template #cell(totalWorkedFormatted)="{item}">
          {{ item.totalWorkedFormatted }}
        </template>
        <template #cell(overtime)="{item}">
          {{ item.overtimeFirst2hFormatted }} ({{
            item.overtimeFirst2hPercent
          }}%)
          <template v-if="item.overtimeBeyond2hSeconds > 0">
            + {{ item.overtimeBeyond2hFormatted }} ({{
              item.overtimeBeyond2hPercent
            }}%)
          </template>
        </template>
        <template #cell(night)="{item}">
          {{ item.nightHoursReduced }}h ({{ item.nightBonusPercent }}%)
        </template>
        <template #cell(intrajourney)="{item}">
          <oxd-text :class="item.intrajourneyCompliant ? '--ok' : '--warn'">
            {{
              item.intrajourneyCompliant
                ? 'OK'
                : 'Déficit: ' + formatSeconds(item.intrajourneyDeficitSeconds)
            }}
          </oxd-text>
        </template>
      </oxd-table>
    </template>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {required, validDateFormat} from '@ohrm/core/util/validation/rules';
import useDateFormat from '@/core/util/composable/useDateFormat';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';

export default {
  name: 'BrazilWorkTimeReport',
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
  },
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/work-time-report',
    );
    const {userDateFormat} = useDateFormat();
    return {http, userDateFormat};
  },
  data() {
    return {
      isLoading: false,
      report: null,
      filters: {
        employee: null,
        fromDate: null,
        toDate: null,
        businessDays: null,
      },
      rules: {
        employee: [required],
        fromDate: [required, validDateFormat(this.userDateFormat)],
        toDate: [required, validDateFormat(this.userDateFormat)],
      },
      headers: [
        {name: 'date', title: 'Data', sortField: 'date'},
        {name: 'totalWorkedFormatted', title: 'Trabalhado'},
        {name: 'overtime', title: 'Horas Extras'},
        {name: 'night', title: 'Adicional Noturno'},
        {name: 'intrajourney', title: 'Intervalo Intrajornada'},
      ],
    };
  },
  computed: {
    balanceClass() {
      if (!this.report) return '';
      return this.report.balanceSeconds >= 0 ? '--positive' : '--negative';
    },
  },
  methods: {
    onSearch() {
      this.isLoading = true;
      this.report = null;
      const params = {
        empNumber: this.filters.employee?.id,
        fromDate: this.filters.fromDate,
        toDate: this.filters.toDate,
      };
      if (this.filters.businessDays) {
        params.businessDays = this.filters.businessDays;
      }
      this.http
        .request({method: 'GET', params})
        .then((response) => {
          const {data} = response.data;
          this.report = data[0];
        })
        .catch(() => {
          this.$toast.error('Erro ao gerar relatório');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    downloadAfd() {
      const params = new URLSearchParams({
        fromDate: this.filters.fromDate,
        toDate: this.filters.toDate,
        empNumber: this.filters.employee?.id,
      });
      window.open(
        `${
          window.appGlobal.baseUrl
        }/api/v2/attendance/br/afd-export?${params.toString()}`,
        '_blank',
      );
    },
    formatDateBr(dateStr) {
      if (!dateStr) return '';
      const [y, m, d] = dateStr.split('-');
      return `${d}/${m}/${y}`;
    },
    formatSeconds(seconds) {
      const h = Math.floor(seconds / 3600);
      const m = Math.floor((seconds % 3600) / 60);
      return `${h}h${String(m).padStart(2, '0')}min`;
    },
  },
};
</script>

<style lang="scss" scoped>
.br-report-card {
  padding: 1rem;
  text-align: center;
  border-radius: 0.5rem;
  background: var(--oxd-interface-gray-lighten-2-color, #f4f4f4);
  &-label {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--oxd-interface-gray-darken-1-color, #666);
  }
  &-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-top: 0.25rem;
  }
}
.--positive {
  color: var(--oxd-feedback-success-color, #2ea36a);
}
.--negative {
  color: var(--oxd-feedback-danger-color, #d32f2f);
}
.--ok {
  color: var(--oxd-feedback-success-color, #2ea36a);
  font-weight: 600;
}
.--warn {
  color: var(--oxd-feedback-warning-color, #ed6d07);
  font-weight: 600;
}
</style>
