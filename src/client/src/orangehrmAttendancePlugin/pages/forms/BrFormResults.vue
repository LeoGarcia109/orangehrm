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
  <div class="orangehrm-background-container ohrm-results">
    <div class="ohrm-builder__top">
      <button type="button" class="ohrm-builder__link-btn" @click="goToList">
        <i class="oxd-icon bi-chevron-left"></i>
        {{ $t('attendance.form_forms') }}
      </button>
      <a class="ohrm-builder__btn ohrm-results__csv" :href="csvUrl">
        <i class="oxd-icon bi-file-earmark-spreadsheet"></i>
        {{ $t('attendance.form_export_csv') }}
      </a>
    </div>

    <template v-if="data">
      <section class="ohrm-builder__card">
        <span class="ohrm-results__eyebrow">
          {{
            data.kind === 'QUIZ'
              ? $t('attendance.form_kind_quiz')
              : $t('attendance.form_kind_survey')
          }}
          <template v-if="data.anonymous"
            >· {{ $t('attendance.form_anonymous') }}</template
          >
          · {{ $t(`attendance.form_status_${data.status.toLowerCase()}`) }}
        </span>
        <h2 class="ohrm-results__title">{{ data.title }}</h2>

        <div class="ohrm-results__summary">
          <div class="ohrm-results__stat">
            <strong
              >{{ data.summary.respondedCount }} /
              {{ data.summary.audienceCount }}</strong
            >
            <span>{{ $t('attendance.form_responded') }}</span>
          </div>
          <template v-if="data.kind === 'QUIZ'">
            <div class="ohrm-results__stat">
              <strong>{{ percent(data.summary.average) }}</strong>
              <span>{{ $t('attendance.form_average') }}</span>
            </div>
            <div class="ohrm-results__stat">
              <strong>{{ percent(data.summary.passedPercent) }}</strong>
              <span
                >{{ $t('attendance.form_passed_percent') }} (≥
                {{ data.passPercent }}%)</span
              >
            </div>
            <div
              class="ohrm-results__stat"
              :class="{'is-warn': data.summary.pendingReviewCount}"
            >
              <strong>{{ data.summary.pendingReviewCount }}</strong>
              <span>{{ $t('attendance.form_pending_review') }}</span>
            </div>
          </template>
        </div>

        <div
          v-if="data.summary.pendingPeople.length"
          class="ohrm-results__missing"
        >
          <span class="ohrm-builder__label">{{
            $t('attendance.form_not_responded')
          }}</span>
          <div class="ohrm-results__chips">
            <span
              v-for="person in data.summary.pendingPeople"
              :key="person.employeeId"
              class="ohrm-results__chip"
            >
              {{ person.name
              }}<small v-if="person.unit"> · {{ person.unit }}</small>
            </span>
          </div>
        </div>
      </section>

      <p v-if="data.hidden" class="ohrm-results__notice">
        <i class="oxd-icon bi-incognito"></i>
        {{ $t('attendance.form_hidden_anonymous') }}
      </p>

      <section v-if="data.perItem.length" class="ohrm-builder__card">
        <oxd-text tag="h6" class="orangehrm-main-title">{{
          $t('attendance.form_by_question')
        }}</oxd-text>
        <article
          v-for="(item, index) in data.perItem"
          :key="item.itemId"
          class="ohrm-results__question"
        >
          <header class="ohrm-results__question-head">
            <span class="ohrm-builder__item-index">{{ index + 1 }}</span>
            <strong>{{ item.prompt }}</strong>
            <span
              v-if="
                item.correctPercent !== null &&
                item.correctPercent !== undefined
              "
              class="ohrm-results__rate"
            >
              {{ $t('attendance.form_correct_rate') }}
              {{ percent(item.correctPercent) }}
            </span>
          </header>

          <template v-if="item.options">
            <div
              v-for="option in item.options"
              :key="option.id"
              class="ohrm-results__bar"
              :class="{'is-correct': option.isCorrect}"
            >
              <span class="ohrm-results__bar-label">
                <i
                  v-if="option.isCorrect"
                  class="oxd-icon bi-check-circle-fill"
                ></i>
                {{ option.label }}
              </span>
              <span class="ohrm-results__bar-track">
                <span
                  class="ohrm-results__bar-fill"
                  :style="{width: `${option.percent}%`}"
                ></span>
              </span>
              <span class="ohrm-results__bar-value"
                >{{ option.count }} · {{ percent(option.percent) }}</span
              >
            </div>
          </template>

          <template v-else-if="item.type === 'YES_NO'">
            <div
              v-for="choice in [
                ['yes', 'form_yes'],
                ['no', 'form_no'],
              ]"
              :key="choice[0]"
              class="ohrm-results__bar"
            >
              <span class="ohrm-results__bar-label">{{
                $t(`attendance.${choice[1]}`)
              }}</span>
              <span class="ohrm-results__bar-track">
                <span
                  class="ohrm-results__bar-fill"
                  :style="{width: `${share(item[choice[0]], item.answered)}%`}"
                ></span>
              </span>
              <span class="ohrm-results__bar-value">{{ item[choice[0]] }}</span>
            </div>
          </template>

          <template v-else-if="item.type === 'SCALE'">
            <p class="ohrm-results__average">
              {{ $t('attendance.form_average') }}:
              <strong>{{ decimal(item.average) }}</strong>
            </p>
            <div v-for="n in 5" :key="n" class="ohrm-results__bar">
              <span class="ohrm-results__bar-label">{{ n }}</span>
              <span class="ohrm-results__bar-track">
                <span
                  class="ohrm-results__bar-fill"
                  :style="{
                    width: `${share(item.distribution[n], item.answered)}%`,
                  }"
                ></span>
              </span>
              <span class="ohrm-results__bar-value">{{
                item.distribution[n]
              }}</span>
            </div>
          </template>

          <ul v-else class="ohrm-results__texts">
            <li v-for="(text, i) in item.texts" :key="i">{{ text }}</li>
            <li v-if="!item.texts.length" class="ohrm-results__muted">—</li>
          </ul>
        </article>
      </section>

      <section
        v-if="!data.anonymous && data.people.length"
        class="ohrm-builder__card ohrm-results__people"
      >
        <oxd-text tag="h6" class="orangehrm-main-title">{{
          $t('attendance.form_by_person')
        }}</oxd-text>
        <p v-if="error" class="ohrm-builder__error">{{ error }}</p>
        <table class="ohrm-br-table">
          <thead>
            <tr>
              <th>{{ $t('general.name') }}</th>
              <th>{{ $t('general.sub_unit') }}</th>
              <th>{{ $t('attendance.form_submitted_at') }}</th>
              <th>{{ $t('attendance.form_attempt') }}</th>
              <th v-if="data.kind === 'QUIZ'">
                {{ $t('attendance.form_score') }}
              </th>
              <th>{{ $t('general.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="person in data.people" :key="person.submissionId">
              <tr>
                <td>{{ person.name }}</td>
                <td>{{ person.unit || '—' }}</td>
                <td>{{ person.submittedAt }}</td>
                <td>{{ person.attempt }}</td>
                <td v-if="data.kind === 'QUIZ'">
                  <span
                    class="ohrm-results__grade"
                    :class="`is-${tone(person)}`"
                  >
                    {{ gradeLabel(person) }}
                  </span>
                </td>
                <td class="ohrm-forms__actions">
                  <button
                    type="button"
                    class="ohrm-builder__link-btn ohrm-results__view"
                    @click="toggle(person)"
                  >
                    {{ $t('attendance.form_view_answers') }}
                  </button>
                  <button
                    type="button"
                    class="ohrm-builder__link-btn ohrm-results__retake"
                    @click="onRetake(person)"
                  >
                    {{ $t('attendance.form_grant_retake') }}
                  </button>
                </td>
              </tr>
              <tr v-if="openId === person.submissionId && detail">
                <td
                  :colspan="data.kind === 'QUIZ' ? 6 : 5"
                  class="ohrm-results__detail"
                >
                  <!-- Numbered like "Por questao": questions only, no content blocks -->
                  <div
                    v-for="(answer, n) in detail.answers"
                    :key="answer.itemId"
                    class="ohrm-results__answer"
                  >
                    <strong>{{ n + 1 }}. {{ answer.prompt }}</strong>
                    <ul
                      v-if="answer.options.length"
                      class="ohrm-results__answer-options"
                    >
                      <li
                        v-for="option in answer.options"
                        :key="option.id"
                        :class="{
                          'is-given': answer.optionIds.includes(option.id),
                          'is-correct':
                            data.kind === 'QUIZ' &&
                            answer.correctOptionIds.includes(option.id),
                        }"
                      >
                        <i
                          class="oxd-icon"
                          :class="
                            answer.optionIds.includes(option.id)
                              ? 'bi-check-square-fill'
                              : 'bi-square'
                          "
                        ></i>
                        {{ option.label }}
                      </li>
                    </ul>
                    <p v-else-if="answer.type === 'SCALE'">
                      {{ answer.scale ?? '—' }}
                    </p>
                    <p v-else-if="answer.type === 'YES_NO'">
                      {{ yesNo(answer.yesNo) }}
                      <small v-if="data.kind === 'QUIZ'">
                        ({{ $t('attendance.form_correct') }}:
                        {{ yesNo(answer.correctYesNo) }})</small
                      >
                    </p>
                    <template v-else>
                      <p class="ohrm-results__answer-text">
                        {{ answer.text || '—' }}
                      </p>
                      <label
                        v-if="data.kind === 'QUIZ' && answer.text"
                        class="ohrm-results__review"
                      >
                        {{ $t('attendance.form_points') }} (0–{{
                          answer.points
                        }})
                        <input
                          v-model="review[answer.itemId]"
                          class="ohrm-builder__input ohrm-builder__narrow ohrm-results__review-points"
                          type="number"
                          min="0"
                          :max="answer.points"
                          step="0.5"
                        />
                      </label>
                    </template>
                    <small
                      v-if="data.kind === 'QUIZ' && answer.awarded !== null"
                      class="ohrm-results__muted"
                    >
                      {{ decimal(answer.awarded) }} /
                      {{ decimal(answer.points) }}
                      {{ $t('attendance.form_points').toLowerCase() }}
                    </small>
                  </div>
                  <button
                    v-if="hasReview"
                    type="button"
                    class="ohrm-builder__btn ohrm-builder__btn--primary ohrm-results__review-save"
                    @click="onReview(person)"
                  >
                    {{ $t('attendance.form_review') }}
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </section>
    </template>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {navigate} from '@ohrm/core/util/helper/navigation';

const RESULTS = '/api/v2/attendance/br/form-results';

/**
 * BR: a form's results for HR -- summary, per question, per person, grading
 * the written answers and allowing another attempt. An anonymous survey
 * shows no people, and nothing at all below three answers (the server
 * decides that; this only shows what came back).
 */
export default {
  name: 'BrFormResults',
  props: {
    formId: {type: Number, required: true},
  },
  setup() {
    const http = new APIService(window.appGlobal.baseUrl, RESULTS);
    http.setIgnorePath(RESULTS);
    return {http, baseUrl: window.appGlobal.baseUrl};
  },
  data() {
    return {data: null, openId: null, detail: null, review: {}, error: null};
  },
  computed: {
    csvUrl() {
      return `${this.baseUrl}/attendance/brFormResultsCsv/${this.formId}`;
    },
    hasReview() {
      return (
        this.data?.kind === 'QUIZ' &&
        (this.detail?.answers ?? []).some(
          (a) => ['SHORT_TEXT', 'LONG_TEXT'].includes(a.type) && a.text,
        )
      );
    },
  },
  beforeMount() {
    this.load();
  },
  methods: {
    load() {
      return this.http
        .request({method: 'GET', params: {formId: this.formId}})
        .then((response) => {
          this.data = response.data.data;
        });
    },
    toggle(person) {
      if (this.openId === person.submissionId) {
        this.openId = null;
        return;
      }
      this.openId = person.submissionId;
      this.detail = null;
      this.http
        .request({
          method: 'GET',
          params: {formId: this.formId, submissionId: person.submissionId},
        })
        .then((response) => {
          this.detail = response.data.data;
          this.review = {};
          this.detail.answers.forEach((a) => {
            if (['SHORT_TEXT', 'LONG_TEXT'].includes(a.type) && a.text) {
              this.review[a.itemId] = a.awarded ?? '';
            }
          });
        });
    },
    onReview(person) {
      const points = {};
      Object.entries(this.review).forEach(([itemId, value]) => {
        if (value !== '' && value !== null) points[itemId] = Number(value);
      });
      this.error = null;
      this.http
        .request({
          method: 'PUT',
          data: {submissionId: person.submissionId, points},
        })
        .then(() => this.$toast.saveSuccess())
        .then(() => {
          this.openId = null;
          return this.load();
        })
        .catch((e) => {
          this.error = e?.data?.error?.message ?? this.$t('general.error');
        });
    },
    onRetake(person) {
      if (!window.confirm(this.$t('attendance.form_retake_confirm'))) return;
      this.error = null;
      this.http
        .request({
          method: 'POST',
          data: {formId: this.formId, employeeId: person.employeeId},
        })
        .then(() => this.$toast.saveSuccess())
        .catch((e) => {
          this.error = e?.data?.error?.message ?? this.$t('general.error');
        });
    },
    tone(person) {
      if (person.status === 'PENDING_REVIEW') return 'waiting';
      return person.passed ? 'passed' : 'failed';
    },
    gradeLabel(person) {
      if (person.status === 'PENDING_REVIEW')
        return this.$t('attendance.form_pending_review');
      const label = person.passed
        ? this.$t('attendance.form_result_passed')
        : this.$t('attendance.form_result_failed');
      return `${this.percent(person.percent)} · ${label}`;
    },
    decimal(value) {
      return value === null || value === undefined
        ? '—'
        : String(value).replace('.', ',');
    },
    percent(value) {
      return value === null || value === undefined
        ? '—'
        : `${this.decimal(value)}%`;
    },
    share(count, total) {
      return total ? Math.round((count / total) * 1000) / 10 : 0;
    },
    yesNo(value) {
      if (value === null || value === undefined) return '—';
      return value
        ? this.$t('attendance.form_yes')
        : this.$t('attendance.form_no');
    },
    goToList() {
      navigate('/attendance/brForms');
    },
  },
};
</script>

<style src="../inbox/br-inbox.scss" lang="scss"></style>
<style src="./br-forms.scss" lang="scss" scoped></style>
