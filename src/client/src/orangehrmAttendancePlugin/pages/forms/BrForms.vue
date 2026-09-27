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
  <div class="orangehrm-background-container ohrm-forms">
    <div class="orangehrm-card-container">
      <div class="ohrm-forms__head">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('attendance.form_forms') }}
        </oxd-text>
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__btn--primary"
          @click="onNew"
        >
          <i class="oxd-icon bi-plus-lg"></i>
          {{ $t('attendance.form_new') }}
        </button>
      </div>

      <p v-if="error" class="ohrm-builder__error">{{ error }}</p>

      <p v-if="!isLoading && !items.length" class="ohrm-forms__empty">
        {{ $t('general.no_records_found') }}
      </p>
      <table v-else-if="items.length" class="ohrm-br-table">
        <thead>
          <tr>
            <th>{{ $t('attendance.announcement_title') }}</th>
            <th>{{ $t('general.type') }}</th>
            <th>{{ $t('general.status') }}</th>
            <th>{{ $t('attendance.form_audience') }}</th>
            <th>{{ $t('attendance.form_due_at') }}</th>
            <th>{{ $t('attendance.form_responded') }}</th>
            <th>{{ $t('general.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="form in items" :key="form.id">
            <td class="ohrm-forms__title">{{ form.title }}</td>
            <td>
              {{ kindLabel(form) }}
              <i
                v-if="form.anonymous"
                class="oxd-icon bi-incognito"
                :title="$t('attendance.form_anonymous')"
              ></i>
            </td>
            <td>
              <span
                class="ohrm-builder__status"
                :class="`is-${form.status.toLowerCase()}`"
              >
                {{ $t(`attendance.form_status_${form.status.toLowerCase()}`) }}
              </span>
            </td>
            <td>{{ audienceLabel(form) }}</td>
            <td>{{ dateLabel(form.dueAt) }}</td>
            <td>
              <template v-if="form.respondedCount !== null">
                {{ form.respondedCount }} / {{ form.audienceCount }}
              </template>
              <template v-else>—</template>
            </td>
            <td class="ohrm-forms__actions">
              <button
                type="button"
                class="ohrm-builder__link-btn"
                @click="onEdit(form)"
              >
                {{
                  form.status === 'DRAFT'
                    ? $t('general.edit')
                    : $t('attendance.form_preview')
                }}
              </button>
              <button
                type="button"
                class="ohrm-builder__link-btn"
                @click="onCopy(form.id)"
              >
                {{ $t('attendance.form_duplicate') }}
              </button>
              <button
                v-if="form.status !== 'DRAFT'"
                type="button"
                class="ohrm-builder__link-btn ohrm-forms__results"
                @click="onResults(form)"
              >
                {{ $t('attendance.form_results') }}
              </button>
              <button
                v-if="form.status === 'PUBLISHED'"
                type="button"
                class="ohrm-builder__link-btn ohrm-forms__close"
                @click="onClose(form)"
              >
                {{ $t('attendance.form_close') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="templates.length" class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('attendance.form_templates') }}
      </oxd-text>
      <div class="ohrm-forms__templates">
        <article
          v-for="template in templates"
          :key="template.id"
          class="ohrm-forms__template"
        >
          <span class="ohrm-forms__template-kind">
            <i
              class="oxd-icon"
              :class="
                template.kind === 'QUIZ' ? 'bi-patch-check' : 'bi-bar-chart'
              "
            ></i>
            {{ kindLabel(template) }}
            <template v-if="template.anonymous"
              >· {{ $t('attendance.form_anonymous') }}</template
            >
          </span>
          <strong class="ohrm-forms__template-title">{{
            template.title
          }}</strong>
          <p v-if="template.description" class="ohrm-forms__template-text">
            {{ template.description }}
          </p>
          <button
            type="button"
            class="ohrm-builder__btn ohrm-forms__use-template"
            @click="onCopy(template.id)"
          >
            {{ $t('attendance.form_use_template') }}
          </button>
        </article>
      </div>
    </div>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {navigate} from '@ohrm/core/util/helper/navigation';

const FORMS = '/api/v2/attendance/br/forms';

/**
 * BR: HR's list of tests and surveys, and the ready-made templates.
 */
export default {
  name: 'BrForms',
  setup() {
    const http = new APIService(window.appGlobal.baseUrl, FORMS);
    http.setIgnorePath(FORMS);
    return {http};
  },
  data() {
    return {items: [], templates: [], isLoading: true, error: null};
  },
  beforeMount() {
    this.load();
  },
  methods: {
    load() {
      this.isLoading = true;
      return this.http
        .request({method: 'GET'})
        .then((response) => {
          this.items = response.data.data;
          this.templates = response.data.meta?.templates ?? [];
        })
        .catch(() => {
          this.items = [];
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    kindLabel(form) {
      return form.kind === 'QUIZ'
        ? this.$t('attendance.form_kind_quiz')
        : this.$t('attendance.form_kind_survey');
    },
    audienceLabel(form) {
      if (form.scope === 'SUBUNIT') return form.subunitName ?? '—';
      if (form.scope === 'EMPLOYEE') return form.employeeName ?? '—';
      return this.$t('attendance.announcement_scope_network');
    },
    dateLabel(date) {
      if (!date) return '—';
      const [y, m, d] = date.split('-');
      return `${d}/${m}/${y}`;
    },
    onNew() {
      navigate('/attendance/brFormBuilder');
    },
    onEdit(form) {
      navigate('/attendance/brFormBuilder/{id}', {id: form.id});
    },
    onResults(form) {
      navigate('/attendance/brFormResults/{id}', {id: form.id});
    },
    // Duplicating and using a template are the same thing: a fresh draft
    onCopy(sourceId) {
      this.error = null;
      return this.http
        .create({sourceId})
        .then((response) => {
          navigate('/attendance/brFormBuilder/{id}', {
            id: response.data.data.id,
          });
        })
        .catch((e) => {
          this.error = e?.data?.error?.message ?? this.$t('general.error');
        });
    },
    onClose(form) {
      if (!window.confirm(this.$t('attendance.form_close_confirm'))) return;
      return this.http
        .update(form.id, {action: 'close'})
        .then(() => this.load())
        .catch((e) => {
          this.error = e?.data?.error?.message ?? this.$t('general.error');
        });
    },
  },
};
</script>

<style src="../inbox/br-inbox.scss" lang="scss"></style>
<style src="./br-forms.scss" lang="scss" scoped></style>
