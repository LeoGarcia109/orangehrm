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
  <div class="ohrm-assessment-public">
    <img
      v-if="bannerSrc"
      class="ohrm-assessment-public__banner"
      :src="bannerSrc"
      alt=""
    />

    <div v-if="view === 'loading'" class="ohrm-assessment-public__card">
      <p class="ohrm-assessment-public__muted">…</p>
    </div>

    <div v-else-if="view === 'inactive'" class="ohrm-assessment-public__card">
      <i class="oxd-icon bi-link-45deg ohrm-assessment-public__icon"></i>
      <p class="ohrm-assessment-public__message">
        {{ $t('attendance.assessment_inactive') }}
      </p>
    </div>

    <div v-else-if="view === 'welcome'" class="ohrm-assessment-public__card">
      <p v-if="applied" class="ohrm-assessment-public__applied">
        <i class="oxd-icon bi-check-circle-fill"></i>
        {{ $t('attendance.assessment_applied') }}
      </p>
      <h1 class="ohrm-assessment-public__title">
        {{ $t('attendance.assessment_welcome') }}, {{ state.firstName }}!
      </h1>
      <p v-if="state.vacancyName" class="ohrm-assessment-public__vacancy">
        {{ $t('attendance.assessment_vacancy') }}:
        <strong>{{ state.vacancyName }}</strong>
      </p>
      <p class="ohrm-assessment-public__intro">
        {{ $t('attendance.assessment_intro') }}
      </p>

      <div v-if="!state.consentGiven" class="ohrm-assessment-public__consent">
        <strong>{{ $t('attendance.assessment_consent_title') }}</strong>
        <p>{{ $t('attendance.assessment_consent_text') }}</p>
        <label>
          <input v-model="consent" type="checkbox" />
          {{ $t('attendance.assessment_consent_check') }}
        </label>
      </div>

      <p v-if="error" class="ohrm-assessment-public__error">{{ error }}</p>

      <button
        type="button"
        class="ohrm-assessment-public__start"
        :disabled="busy || (!state.consentGiven && !consent)"
        @click="onStart"
      >
        {{
          state.consentGiven
            ? $t('attendance.assessment_continue')
            : $t('attendance.assessment_start')
        }}
      </button>
    </div>

    <assessment-runner
      v-else-if="view === 'questions'"
      :state="state"
      :save="saveAnswers"
      :complete="finish"
      @done="view = 'done'"
    />

    <div v-else class="ohrm-assessment-public__card">
      <i
        class="oxd-icon bi-check-circle-fill ohrm-assessment-public__icon is-done"
      ></i>
      <p class="ohrm-assessment-public__message">
        {{ $t('attendance.assessment_thanks') }}
      </p>
    </div>
  </div>
</template>

<script>
import AssessmentRunner from '@/orangehrmAttendancePlugin/components/assessment/AssessmentRunner.vue';

/**
 * BR: the candidate's behavioural questionnaire, reached by a link and
 * nothing else -- no login. Consent comes before anything is stored; the
 * candidate sees no result, only a thank-you.
 *
 * Plain fetch rather than the app's API service: a dead link answers 404,
 * which here is an expected outcome, not an error to toast.
 */
export default {
  name: 'AssessmentPublic',
  components: {'assessment-runner': AssessmentRunner},
  props: {
    token: {type: String, required: true},
    applied: {type: Boolean, default: false},
    bannerSrc: {type: String, default: null},
  },
  data() {
    return {
      view: 'loading',
      state: null,
      consent: false,
      busy: false,
      error: null,
    };
  },
  computed: {
    url() {
      return `${
        window.appGlobal.baseUrl
      }/recruitmentApply/assessment/${encodeURIComponent(this.token)}/api`;
    },
  },
  beforeMount() {
    this.call('GET')
      .then((state) => {
        this.state = state;
        this.view = 'welcome';
      })
      .catch(() => {
        this.view = 'inactive';
      });
  },
  methods: {
    call(method, body = null) {
      return fetch(this.url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: body === null ? undefined : JSON.stringify(body),
        credentials: 'same-origin',
      }).then((response) =>
        response.json().then((json) => {
          if (!response.ok) {
            return Promise.reject({
              response: {status: response.status, data: json},
            });
          }
          return json.data;
        }),
      );
    },
    onStart() {
      if (this.state.consentGiven) {
        this.view = 'questions';
        return;
      }
      this.busy = true;
      this.error = null;
      this.call('PUT', {consent: true})
        .then((state) => {
          this.state = state;
          this.view = 'questions';
        })
        .catch((e) => {
          if (e?.response?.status === 404) this.view = 'inactive';
          else this.error = e?.response?.data?.error?.message ?? null;
        })
        .finally(() => {
          this.busy = false;
        });
    },
    saveAnswers(answers) {
      return this.call('PUT', {answers});
    },
    finish() {
      return this.call('POST', {complete: true});
    },
  },
};
</script>

<style src="./assessment-public.scss" lang="scss" scoped></style>
