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
  <div class="orangehrm-background-container ohrm-jobfit">
    <div class="orangehrm-card-container">
      <div class="ohrm-forms__head">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('attendance.jobfit_title_profiles') }}
        </oxd-text>
        <button
          type="button"
          class="ohrm-builder__btn ohrm-jobfit__new"
          @click="onNewJobTitle"
        >
          <i class="oxd-icon bi-box-arrow-up-right"></i>
          {{ $t('attendance.jobfit_new_job_title') }}
        </button>
      </div>

      <p v-if="loaded && !items.length" class="ohrm-forms__empty">
        {{ $t('attendance.jobfit_no_job_titles') }}
      </p>
      <table v-else-if="items.length" class="ohrm-br-table">
        <thead>
          <tr>
            <th>{{ $t('attendance.jobfit_job_title') }}</th>
            <th>{{ $t('general.status') }}</th>
            <th>{{ $t('attendance.jobfit_competencies') }}</th>
            <th>{{ $t('attendance.jobfit_updated_at') }}</th>
            <th>{{ $t('general.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td class="ohrm-forms__title">{{ item.name }}</td>
            <td>
              <span
                class="ohrm-jobfit__chip"
                :class="item.hasProfile ? 'is-on' : 'is-off'"
              >
                {{
                  item.hasProfile
                    ? $t('attendance.jobfit_has_profile')
                    : $t('attendance.jobfit_no_profile')
                }}
              </span>
            </td>
            <td>{{ item.hasProfile ? item.competencies : '—' }}</td>
            <td>{{ dateLabel(item.updatedAt) }}</td>
            <td class="ohrm-forms__actions">
              <button
                type="button"
                class="ohrm-builder__link-btn ohrm-jobfit__edit"
                @click="onEdit(item)"
              >
                {{
                  item.hasProfile
                    ? $t('attendance.jobfit_edit')
                    : $t('attendance.jobfit_define_profile')
                }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {navigate} from '@ohrm/core/util/helper/navigation';

/**
 * BR: the native job titles and whether each already has its ideal profile.
 * Job titles themselves are created in Admin; this screen only profiles them.
 */
export default {
  name: 'BrJobProfiles',
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/job-profiles',
    );
    return {http};
  },
  data() {
    return {items: [], loaded: false};
  },
  beforeMount() {
    this.http
      .getAll()
      .then((response) => {
        this.items = response.data.data;
      })
      .catch(() => {
        this.items = [];
      })
      .finally(() => {
        this.loaded = true;
      });
  },
  methods: {
    onEdit(item) {
      navigate(`/recruitment/brJobProfile/${item.id}`);
    },
    onNewJobTitle() {
      window.open(
        `${window.appGlobal.baseUrl}/admin/viewJobTitleList`,
        '_blank',
        'noopener',
      );
    },
    dateLabel(value) {
      if (!value) return '—';
      const [date, time] = value.split(' ');
      const [y, m, d] = date.split('-');
      return `${d}/${m}/${y}${time ? ' ' + time : ''}`;
    },
  },
};
</script>

<style src="../inbox/br-inbox.scss" lang="scss"></style>
<style src="../forms/br-forms.scss" lang="scss" scoped></style>
<style src="./jobfit.scss" lang="scss" scoped></style>
