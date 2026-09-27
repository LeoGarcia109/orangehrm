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
  <div class="ohrm-assessment">
    <div class="ohrm-assessment__progress">
      <span>
        {{ $t('attendance.assessment_part') }} {{ current + 1 }}
        {{ $t('attendance.assessment_of') }} {{ state.pages.length }}
      </span>
      <span class="ohrm-assessment__bar">
        <span
          class="ohrm-assessment__bar-fill"
          :style="{width: `${((current + 1) / state.pages.length) * 100}%`}"
        ></span>
      </span>
    </div>

    <p class="ohrm-assessment__legend">
      <span>1 = {{ $t('attendance.assessment_scale_1') }}</span>
      <span>5 = {{ $t('attendance.assessment_scale_5') }}</span>
    </p>

    <section
      v-for="item in page"
      :key="item.code"
      class="ohrm-assessment__statement"
      :class="{'is-missing': missing.includes(item.code)}"
    >
      <p class="ohrm-assessment__text">{{ item.text }}</p>
      <div class="ohrm-assessment__options">
        <button
          v-for="n in 5"
          :key="n"
          type="button"
          class="ohrm-assessment__option"
          :class="{'is-selected': answers[item.code] === n}"
          :title="$t(`attendance.assessment_scale_${n}`)"
          :disabled="busy"
          @click="choose(item.code, n)"
        >
          {{ n }}
        </button>
      </div>
      <div class="ohrm-assessment__ends">
        <span>{{ $t('attendance.assessment_scale_1') }}</span>
        <span>{{ $t('attendance.assessment_scale_5') }}</span>
      </div>
    </section>

    <p v-if="error" class="ohrm-assessment__error">{{ error }}</p>
    <p v-else-if="missing.length" class="ohrm-assessment__error">
      {{ $t('attendance.assessment_answer_all') }}
    </p>

    <div class="ohrm-assessment__actions">
      <button
        v-if="current > 0"
        type="button"
        class="ohrm-assessment__btn ohrm-assessment__btn--secondary ohrm-assessment__back"
        :disabled="busy"
        @click="onBack"
      >
        {{ $t('attendance.assessment_back') }}
      </button>
      <button
        type="button"
        class="ohrm-assessment__btn ohrm-assessment__btn--primary ohrm-assessment__next"
        :disabled="busy"
        @click="onNext"
      >
        {{
          isLast
            ? $t('attendance.assessment_finish')
            : $t('attendance.assessment_continue')
        }}
      </button>
    </div>
  </div>
</template>

<script>
/**
 * BR: the behavioural questionnaire, one page of statements at a time --
 * shared by the candidate's public page and the employee's Provas tab.
 *
 * Each page is saved (`save`) before moving on, so a closed browser loses at
 * most the page on screen; the last page saves and then `complete`s. Both
 * are functions returning promises, so each host brings its own API.
 */
export default {
  name: 'AssessmentRunner',
  props: {
    // {pages: [[{code, text}]], answers: {code: 1..5}, nextPage}
    state: {type: Object, required: true},
    save: {type: Function, required: true},
    complete: {type: Function, required: true},
  },
  emits: ['done'],
  data() {
    return {
      current: this.state.nextPage || 0,
      // An empty map comes from PHP as a JSON list
      answers: Array.isArray(this.state.answers) ? {} : {...this.state.answers},
      missing: [],
      busy: false,
      error: null,
    };
  },
  computed: {
    page() {
      return this.state.pages[this.current] || [];
    },
    isLast() {
      return this.current === this.state.pages.length - 1;
    },
  },
  methods: {
    choose(code, value) {
      this.answers = {...this.answers, [code]: value};
      this.missing = this.missing.filter((c) => c !== code);
    },
    onBack() {
      this.error = null;
      this.missing = [];
      this.current -= 1;
      window.scrollTo?.(0, 0);
    },
    onNext() {
      this.error = null;
      this.missing = this.page
        .map((item) => item.code)
        .filter((code) => !this.answers[code]);
      if (this.missing.length) {
        this.$nextTick(() => {
          this.$el
            .querySelector('.ohrm-assessment__statement.is-missing')
            ?.scrollIntoView?.({behavior: 'smooth', block: 'center'});
        });
        return;
      }
      const pageAnswers = {};
      this.page.forEach((item) => {
        pageAnswers[item.code] = this.answers[item.code];
      });
      this.busy = true;
      this.save(pageAnswers)
        .then(() => (this.isLast ? this.complete() : null))
        .then(() => {
          if (this.isLast) {
            this.$emit('done');
          } else {
            this.current += 1;
            window.scrollTo?.(0, 0);
          }
        })
        .catch((e) => {
          this.error =
            e?.data?.error?.message ??
            e?.response?.data?.error?.message ??
            this.$t('general.error');
        })
        .finally(() => {
          this.busy = false;
        });
    },
  },
};
</script>

<style src="./assessment-runner.scss" lang="scss" scoped></style>
