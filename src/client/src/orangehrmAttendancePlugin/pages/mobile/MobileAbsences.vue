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
  <section class="ohrm-mobile__absences">
    <button
      v-if="!showForm"
      class="ohrm-mobile__absence-new"
      @click="showForm = true"
    >
      <i class="oxd-icon bi-plus-circle"></i>
      {{ $t('attendance.absence_new') }}
    </button>

    <form v-else class="ohrm-mobile__absence-form" @submit.prevent="onSubmit">
      <label class="ohrm-mobile__field">
        <span>{{ $t('attendance.absence_reason') }}</span>
        <select v-model="form.reasonType" class="ohrm-mobile__input">
          <option v-for="reason in reasonTypes" :key="reason" :value="reason">
            {{ $t(`attendance.absence_reason_${reason.toLowerCase()}`) }}
          </option>
        </select>
      </label>

      <div class="ohrm-mobile__field-row">
        <label class="ohrm-mobile__field">
          <span>{{ $t('attendance.absence_from') }}</span>
          <input
            v-model="form.fromDate"
            type="date"
            class="ohrm-mobile__input"
          />
        </label>
        <label class="ohrm-mobile__field">
          <span>{{ $t('attendance.absence_to') }}</span>
          <input v-model="form.toDate" type="date" class="ohrm-mobile__input" />
        </label>
      </div>

      <label class="ohrm-mobile__field">
        <span>{{ $t('attendance.absence_note') }}</span>
        <textarea
          v-model="form.note"
          class="ohrm-mobile__input"
          rows="3"
          maxlength="2000"
        ></textarea>
      </label>

      <label class="ohrm-mobile__field">
        <span>
          {{ $t('attendance.absence_attach') }}
          <em v-if="documentRequired">*</em>
        </span>
        <!-- capture opens the camera on a phone and the file picker elsewhere,
             so a photo of the certificate is one tap -->
        <input
          type="file"
          accept="image/*,application/pdf"
          capture="environment"
          class="ohrm-mobile__input"
          @change="onFileChosen"
        />
        <span v-if="form.filename" class="ohrm-mobile__file-name">
          {{ form.filename }}
        </span>
      </label>

      <p v-if="error" class="ohrm-mobile__absence-error">{{ error }}</p>

      <div class="ohrm-mobile__field-row">
        <button
          type="button"
          class="ohrm-mobile__absence-cancel"
          @click="onCancel"
        >
          {{ $t('general.cancel') }}
        </button>
        <button
          type="submit"
          class="ohrm-mobile__absence-submit"
          :disabled="isSaving"
        >
          {{ $t('attendance.absence_submit') }}
        </button>
      </div>
    </form>

    <div v-if="isLoading" class="ohrm-mobile__history-empty">
      {{ $t('attendance.history_loading') }}
    </div>
    <div v-else-if="!items.length" class="ohrm-mobile__history-empty">
      {{ $t('attendance.no_absences') }}
    </div>

    <article
      v-for="item in items"
      v-else
      :key="item.id"
      class="ohrm-mobile__absence-card"
    >
      <header class="ohrm-mobile__absence-head">
        <span class="ohrm-mobile__absence-reason">
          {{ $t(`attendance.absence_reason_${item.reasonType.toLowerCase()}`) }}
        </span>
        <span
          class="ohrm-mobile__absence-status"
          :class="`is-${item.status.toLowerCase()}`"
        >
          {{ $t(`attendance.absence_status_${item.status.toLowerCase()}`) }}
        </span>
      </header>
      <div class="ohrm-mobile__absence-dates">
        {{ item.fromDate }}
        <template v-if="item.toDate !== item.fromDate">
          — {{ item.toDate }}
        </template>
        <span v-if="item.hasAttachment" class="ohrm-mobile__absence-clip">
          <i class="oxd-icon bi-paperclip"></i>
        </span>
      </div>
      <p v-if="item.note" class="ohrm-mobile__absence-note">{{ item.note }}</p>
      <p v-if="item.decisionNote" class="ohrm-mobile__absence-decision">
        {{ item.decisionNote }}
      </p>
    </article>
  </section>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';

// Mirrors AbsenceJustificationRules: these are the reasons whose whole
// evidence is the document.
const REASONS_NEEDING_A_DOCUMENT = [
  'ATESTADO_MEDICO',
  'DECLARACAO_COMPARECIMENTO',
];

export default {
  name: 'MobileAbsences',
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/absences',
    );
    // Business refusals are shown inline, not as a generic toast
    http.setIgnorePath('/api/v2/attendance/br/absences');
    return {http};
  },
  data() {
    return {
      items: [],
      isLoading: true,
      isSaving: false,
      showForm: false,
      error: null,
      reasonTypes: [
        'ATESTADO_MEDICO',
        'DECLARACAO_COMPARECIMENTO',
        'FALTA_JUSTIFICADA',
        'OUTRO',
      ],
      form: this.emptyForm(),
    };
  },
  computed: {
    documentRequired() {
      return REASONS_NEEDING_A_DOCUMENT.includes(this.form.reasonType);
    },
  },
  beforeMount() {
    this.load();
  },
  methods: {
    emptyForm() {
      const today = new Date().toISOString().slice(0, 10);
      return {
        reasonType: 'ATESTADO_MEDICO',
        fromDate: today,
        toDate: today,
        note: '',
        filename: null,
        attachment: null,
      };
    },
    load() {
      this.isLoading = true;
      return this.http
        .request({method: 'GET'})
        .then((response) => {
          this.items = response.data.data;
          if (response.data.meta?.reasonTypes?.length) {
            this.reasonTypes = response.data.meta.reasonTypes;
          }
        })
        .catch(() => {
          this.items = [];
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    onFileChosen(event) {
      const file = event.target.files?.[0];
      if (!file) {
        this.form.filename = null;
        this.form.attachment = null;
        return;
      }
      const reader = new FileReader();
      reader.onload = () => {
        this.form.filename = file.name;
        this.form.attachment = reader.result;
      };
      reader.readAsDataURL(file);
    },
    onCancel() {
      this.showForm = false;
      this.error = null;
      this.form = this.emptyForm();
    },
    onSubmit() {
      this.isSaving = true;
      this.error = null;
      this.http
        .request({
          method: 'POST',
          data: {
            reasonType: this.form.reasonType,
            fromDate: this.form.fromDate,
            toDate: this.form.toDate,
            note: this.form.note || null,
            attachment: this.form.attachment,
            filename: this.form.filename,
          },
        })
        .then(() => {
          this.onCancel();
          return this.load();
        })
        .catch((error) => {
          this.error =
            error?.data?.error?.message ??
            error?.response?.data?.error?.message ??
            this.$t('general.error');
        })
        .finally(() => {
          this.isSaving = false;
        });
    },
  },
};
</script>
