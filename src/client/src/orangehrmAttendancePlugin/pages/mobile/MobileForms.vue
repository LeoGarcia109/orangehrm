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
  <section class="ohrm-mobile__forms">
    <template v-if="view === 'list'">
      <div v-if="isLoading" class="ohrm-mobile__forms-empty">
        {{ $t('attendance.history_loading') }}
      </div>

      <template v-else>
        <div class="ohrm-mobile__forms-section">
          <h3 class="ohrm-mobile__forms-heading">
            {{ $t('attendance.form_pending') }}
          </h3>
          <button
            v-for="assessment in assessments"
            :key="`assessment-${assessment.id}`"
            type="button"
            class="ohrm-mobile__form-card ohrm-mobile__form-card--pending ohrm-mobile__form-card--assessment"
            @click="openAssessment(assessment.id)"
          >
            <span class="ohrm-mobile__form-kind">
              <i class="oxd-icon bi-person-lines-fill"></i>
              {{ $t('attendance.assessment_title') }}
            </span>
            <span class="ohrm-mobile__form-title">
              {{ $t('attendance.assessment_title') }}
            </span>
            <span class="ohrm-mobile__form-go">
              {{ $t('attendance.form_answer') }}
              <i class="oxd-icon bi-chevron-right"></i>
            </span>
          </button>
          <p
            v-if="!pending.length && !assessments.length"
            class="ohrm-mobile__forms-empty"
          >
            {{ $t('attendance.form_no_pending') }}
          </p>
          <button
            v-for="form in pending"
            :key="form.id"
            type="button"
            class="ohrm-mobile__form-card ohrm-mobile__form-card--pending"
            @click="open(form.id)"
          >
            <span class="ohrm-mobile__form-kind">
              <i
                class="oxd-icon"
                :class="
                  form.kind === 'QUIZ' ? 'bi-patch-check' : 'bi-bar-chart'
                "
              ></i>
              {{ kindLabel(form) }}
              <i
                v-if="form.anonymous"
                class="oxd-icon bi-incognito ohrm-mobile__form-anon"
              ></i>
            </span>
            <span class="ohrm-mobile__form-title">{{ form.title }}</span>
            <span v-if="form.dueAt" class="ohrm-mobile__form-due">
              {{ $t('attendance.form_due_in') }} {{ dateLabel(form.dueAt) }}
            </span>
            <span class="ohrm-mobile__form-go">
              {{ $t('attendance.form_answer') }}
              <i class="oxd-icon bi-chevron-right"></i>
            </span>
          </button>
        </div>

        <div v-if="answered.length" class="ohrm-mobile__forms-section">
          <h3 class="ohrm-mobile__forms-heading">
            {{ $t('attendance.form_answered') }}
          </h3>
          <div
            v-for="form in answered"
            :key="form.id"
            class="ohrm-mobile__form-card"
          >
            <span class="ohrm-mobile__form-kind">{{ kindLabel(form) }}</span>
            <span class="ohrm-mobile__form-title">{{ form.title }}</span>
            <span
              class="ohrm-mobile__form-chip"
              :class="`is-${resultTone(form.result)}`"
            >
              {{ resultLabel(form.result) }}
            </span>
          </div>
        </div>
      </template>
    </template>

    <template v-else-if="view.startsWith('assessment')">
      <button type="button" class="ohrm-mobile__forms-back" @click="backToList">
        <i class="oxd-icon bi-chevron-left"></i>
        {{ $t('attendance.form_tab') }}
      </button>
      <div v-if="view === 'assessment-intro'" class="ohrm-mobile__form-result">
        <i
          class="oxd-icon bi-person-lines-fill ohrm-mobile__form-result-icon is-done"
        ></i>
        <strong class="ohrm-mobile__form-result-text">
          {{ $t('attendance.assessment_title') }}
        </strong>
        <p class="ohrm-mobile__assessment-notice">
          {{ $t('attendance.assessment_employee_notice') }}
        </p>
        <p class="ohrm-mobile__assessment-notice">
          {{ $t('attendance.assessment_intro') }}
        </p>
        <button
          type="button"
          class="ohrm-mobile__assessment-start"
          :disabled="!assessment"
          @click="view = 'assessment-questions'"
        >
          {{ $t('attendance.assessment_start') }}
        </button>
      </div>
      <assessment-runner
        v-else-if="view === 'assessment-questions' && assessment"
        :state="assessment"
        :save="saveAssessment"
        :complete="completeAssessment"
        @done="onAssessmentDone"
      />
      <div
        v-else-if="view === 'assessment-done'"
        class="ohrm-mobile__form-result"
      >
        <i
          class="oxd-icon bi-check-circle-fill ohrm-mobile__form-result-icon is-done"
        ></i>
        <span class="ohrm-mobile__form-result-text">
          {{ $t('attendance.assessment_thanks') }}
        </span>
      </div>
      <div v-else class="ohrm-mobile__forms-empty">
        {{ openError || $t('attendance.history_loading') }}
      </div>
    </template>

    <template v-else>
      <button type="button" class="ohrm-mobile__forms-back" @click="backToList">
        <i class="oxd-icon bi-chevron-left"></i>
        {{ $t('attendance.form_tab') }}
      </button>

      <div v-if="view === 'result'" class="ohrm-mobile__form-result">
        <i
          class="oxd-icon ohrm-mobile__form-result-icon"
          :class="`is-${resultTone(result)} ${resultIcon(result)}`"
        ></i>
        <strong
          v-if="result.status === 'GRADED'"
          class="ohrm-mobile__form-result-score"
        >
          {{ percentLabel(result.percent) }}
        </strong>
        <span class="ohrm-mobile__form-result-text">
          {{ resultLabel(result) }}
        </span>
      </div>

      <form-filler
        v-else-if="current"
        ref="filler"
        :key="current.id"
        :form="current"
        :submitting="isSending"
        :error="sendError"
        @submit="onSubmit"
      />
      <div v-else class="ohrm-mobile__forms-empty">
        {{ openError || $t('attendance.history_loading') }}
      </div>
    </template>
  </section>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import FormFiller from '@/orangehrmAttendancePlugin/components/forms/FormFiller.vue';
import AssessmentRunner from '@/orangehrmAttendancePlugin/components/assessment/AssessmentRunner.vue';

const MY_FORMS = '/api/v2/attendance/br/my-forms';
const SUBMISSIONS = '/api/v2/attendance/br/my-forms/submissions';
const MY_ASSESSMENTS = '/api/v2/attendance/br/my-assessments';

function serverMessage(e) {
  return e?.data?.error?.message ?? e?.response?.data?.error?.message ?? null;
}

/**
 * BR: the "Provas" tab -- tests and surveys HR sent, answering them, and the
 * grade. A notice's "Responder" button lands here through openFormId.
 */
export default {
  name: 'MobileForms',
  components: {
    'form-filler': FormFiller,
    'assessment-runner': AssessmentRunner,
  },
  props: {
    openFormId: {type: Number, default: null},
  },
  emits: ['pending-changed'],
  setup() {
    const http = new APIService(window.appGlobal.baseUrl, MY_FORMS);
    const submitHttp = new APIService(window.appGlobal.baseUrl, SUBMISSIONS);
    // A closed deadline or a second attempt are answers, not crashes
    http.setIgnorePath(MY_FORMS);
    submitHttp.setIgnorePath(SUBMISSIONS);
    const assessmentHttp = new APIService(
      window.appGlobal.baseUrl,
      MY_ASSESSMENTS,
    );
    assessmentHttp.setIgnorePath(MY_ASSESSMENTS);
    return {http, submitHttp, assessmentHttp};
  },
  data() {
    return {
      view: 'list',
      items: [],
      // Behavioural questionnaires share the tab (meta.assessments)
      assessments: [],
      assessment: null,
      assessmentId: null,
      isLoading: true,
      current: null,
      openError: null,
      isSending: false,
      sendError: null,
      result: null,
    };
  },
  computed: {
    pending() {
      return this.items.filter((f) => f.section === 'pending');
    },
    answered() {
      return this.items.filter((f) => f.section === 'answered');
    },
  },
  watch: {
    openFormId(id) {
      if (id) this.open(id);
    },
  },
  beforeMount() {
    this.load();
    if (this.openFormId) this.open(this.openFormId);
  },
  methods: {
    load() {
      this.isLoading = true;
      return this.http
        .request({method: 'GET'})
        .then((response) => {
          this.items = response.data.data;
          this.assessments = response.data.meta?.assessments ?? [];
          this.$emit('pending-changed', response.data.meta?.pendingCount ?? 0);
        })
        .catch(() => {
          this.items = [];
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    open(id) {
      this.view = 'fill';
      this.current = null;
      this.openError = null;
      this.sendError = null;
      return this.http
        .get(id)
        .then((response) => {
          this.current = response.data.data;
        })
        .catch((e) => {
          this.openError = serverMessage(e) ?? this.$t('general.error');
        });
    },
    onSubmit(answers) {
      this.isSending = true;
      this.sendError = null;
      this.submitHttp
        .request({
          method: 'POST',
          data: {formId: this.current.id, answers},
        })
        .then((response) => {
          this.$refs.filler?.clearDraft();
          this.result = response.data.data;
          this.view = 'result';
          window.scrollTo?.(0, 0);
          return this.load();
        })
        .catch((e) => {
          // The draft stays: nothing typed is lost to a refusal.
          this.sendError = serverMessage(e) ?? this.$t('general.error');
        })
        .finally(() => {
          this.isSending = false;
        });
    },
    openAssessment(id) {
      this.view = 'assessment-intro';
      this.assessment = null;
      this.assessmentId = id;
      this.openError = null;
      return this.assessmentHttp
        .get(id)
        .then((response) => {
          this.assessment = response.data.data;
        })
        .catch((e) => {
          this.view = 'assessment-error';
          this.openError = serverMessage(e) ?? this.$t('general.error');
        });
    },
    saveAssessment(answers) {
      return this.assessmentHttp.update(this.assessmentId, {answers});
    },
    completeAssessment() {
      return this.assessmentHttp.update(this.assessmentId, {complete: true});
    },
    onAssessmentDone() {
      this.view = 'assessment-done';
      this.assessment = null;
      return this.load();
    },
    backToList() {
      this.view = 'list';
      this.current = null;
      this.result = null;
    },
    kindLabel(form) {
      return form.kind === 'QUIZ'
        ? this.$t('attendance.form_kind_quiz')
        : this.$t('attendance.form_kind_survey');
    },
    dateLabel(date) {
      const [y, m, d] = date.split('-');
      return `${d}/${m}/${y}`;
    },
    percentLabel(percent) {
      return `${String(percent).replace('.', ',')}%`;
    },
    resultTone(result) {
      if (!result || result.status === 'RECORDED') return 'done';
      if (result.status === 'PENDING_REVIEW') return 'waiting';
      return result.passed ? 'passed' : 'failed';
    },
    resultIcon(result) {
      const tone = this.resultTone(result);
      if (tone === 'failed') return 'bi-x-circle-fill';
      if (tone === 'waiting') return 'bi-hourglass-split';
      return 'bi-check-circle-fill';
    },
    resultLabel(result) {
      const tone = this.resultTone(result);
      if (tone === 'done') {
        // The thanks is for the moment of sending; in the list it is a status
        return this.view === 'result'
          ? this.$t('attendance.form_thanks')
          : this.$t('attendance.form_done');
      }
      if (tone === 'waiting') return this.$t('attendance.form_pending_review');
      const label =
        tone === 'passed'
          ? this.$t('attendance.form_result_passed')
          : this.$t('attendance.form_result_failed');
      return this.view === 'result'
        ? label
        : `${this.percentLabel(result.percent)} · ${label}`;
    },
  },
};
</script>

<style src="./mobile-forms.scss" lang="scss" scoped></style>
