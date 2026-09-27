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
  <div class="ohrm-compare__table-view">
    <section class="ohrm-builder__card">
      <table class="ohrm-br-table ohrm-compare__ranking">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ $t('attendance.jobfit_person') }}</th>
            <th>{{ $t('attendance.jobfit_overall') }}</th>
            <th>{{ $t('attendance.jobfit_behavior') }}</th>
            <th>{{ $t('attendance.jobfit_competencies') }}</th>
            <th>{{ $t('attendance.jobfit_show_in_chart') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(person, index) in result.people"
            :key="person.key"
            class="ohrm-compare__rank-row"
          >
            <td class="ohrm-compare__pos">{{ index + 1 }}º</td>
            <td>
              <span class="ohrm-compare__who">
                <span
                  class="ohrm-compare__swatch"
                  :style="{background: colors[person.key] || 'transparent'}"
                ></span>
                <span>
                  <span class="ohrm-compare__name">{{ person.name }}</span>
                  <small class="ohrm-compare__meta">
                    {{ $t(`attendance.jobfit_type_${person.type}`) }}
                    <template v-if="person.subunit">
                      · {{ person.subunit }}
                    </template>
                  </small>
                </span>
              </span>
              <span class="ohrm-compare__flags">
                <span
                  v-if="person.partial"
                  class="ohrm-jobfit__chip is-partial"
                  :title="$t('attendance.jobfit_partial_hint')"
                >
                  {{ $t('attendance.jobfit_partial') }}
                </span>
                <span
                  v-for="alert in person.alerts"
                  :key="alert"
                  class="ohrm-jobfit__chip is-alert"
                >
                  {{ $t(alertKey(alert)) }}
                </span>
              </span>
            </td>
            <td>
              <span class="ohrm-compare__overall">
                {{ percent(person.overall) }}
              </span>
              <span class="ohrm-compare__meter">
                <span :style="{width: `${person.overall}%`}"></span>
              </span>
            </td>
            <td>{{ percent(person.behavior) }}</td>
            <td>
              {{
                person.competency === null ? '—' : percent(person.competency)
              }}
            </td>
            <td class="ohrm-compare__show">
              <input
                type="checkbox"
                :checked="shown.includes(person.key)"
                :disabled="!shown.includes(person.key) && shown.length >= 6"
                @change="$emit('toggle-shown', person.key)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="ohrm-builder__card ohrm-compare__factors">
      <div class="ohrm-compare__legend">
        <span class="is-in">{{ $t('attendance.jobfit_color_in') }}</span>
        <span class="is-near">{{ $t('attendance.jobfit_color_near') }}</span>
        <span class="is-far">{{ $t('attendance.jobfit_color_far') }}</span>
        <span>{{ $t('attendance.jobfit_essential_mark') }}</span>
      </div>
      <div class="ohrm-compare__scroll">
        <table class="ohrm-br-table">
          <thead>
            <tr>
              <th>{{ $t('attendance.jobfit_factor') }}</th>
              <th>{{ $t('attendance.jobfit_job_range') }}</th>
              <th v-for="person in result.people" :key="person.key">
                {{ person.name }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="factor in result.profile.factors"
              :key="`${factor.instrument}-${factor.factor}`"
            >
              <th :data-factor="`${factor.instrument}-${factor.factor}`">
                {{ factorLabel(factor) }}
                <template v-if="factor.weight === 2">★</template>
              </th>
              <td class="ohrm-compare__range">
                {{ factor.weight === 0 ? '—' : `${factor.min}–${factor.max}` }}
              </td>
              <td
                v-for="person in result.people"
                :key="person.key"
                class="ohrm-compare__score"
                :class="scoreClass(person, factor)"
                :data-cell="`${person.key}-${factor.instrument}-${factor.factor}`"
              >
                {{ scoreOf(person, factor) }}
              </td>
            </tr>
            <tr
              v-for="competency in result.profile.competencies"
              :key="`comp-${competency.id}`"
              class="ohrm-compare__competency"
            >
              <th>
                {{ competency.name }}
                <template v-if="competency.weight === 2">★</template>
              </th>
              <td class="ohrm-compare__range">
                {{ $t('attendance.jobfit_min_level') }}
                {{ competency.minLevel }}
              </td>
              <td v-for="person in result.people" :key="person.key">
                <select
                  class="ohrm-builder__input ohrm-compare__rating"
                  :class="{
                    'is-below': belowMin(person, competency),
                  }"
                  :data-rating="`${person.key}-${competency.id}`"
                  :value="person.ratings[competency.id] ?? ''"
                  @change="onRate(person, competency, $event.target.value)"
                >
                  <option value="">
                    {{ $t('attendance.jobfit_no_rating') }}
                  </option>
                  <option v-for="level in [1, 2, 3, 4, 5]" :key="level">
                    {{ level }}
                  </option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';

/**
 * BR: the ranking against a job title, the score of each person in each
 * factor coloured by its distance to the job's range, and the competency
 * ratings HR gives right here.
 */
export default {
  name: 'CompareTable',
  props: {
    result: {type: Object, required: true},
    // keys of the people on the radar
    shown: {type: Array, required: true},
    // key => colour on the radar
    colors: {type: Object, default: () => ({})},
  },
  emits: ['toggle-shown', 'rated'],
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/competency-ratings',
    );
    return {http};
  },
  methods: {
    percent(value) {
      return `${Math.round(value)}%`;
    },
    alertKey(alert) {
      return alert === 'ESSENTIAL_FACTOR'
        ? 'attendance.jobfit_alert_factor'
        : 'attendance.jobfit_alert_competency';
    },
    factorLabel(f) {
      return this.$t(
        `attendance.assessment_f_${f.instrument.toLowerCase()}_${f.factor.toLowerCase()}`,
      );
    },
    fitOf(person, factor) {
      return person.factors.find(
        (f) => f.instrument === factor.instrument && f.factor === factor.factor,
      );
    },
    scoreOf(person, factor) {
      const row = this.fitOf(person, factor);
      return row ? String(row.score).replace('.', ',') : '—';
    },
    scoreClass(person, factor) {
      if (factor.weight === 0) return 'is-ignored';
      const row = this.fitOf(person, factor);
      return row ? `is-${row.color.toLowerCase()}` : '';
    },
    belowMin(person, competency) {
      const rating = person.ratings[competency.id];
      return rating !== undefined && rating < competency.minLevel;
    },
    onRate(person, competency, value) {
      const data = {subject: person.key};
      if (value !== '') data.rating = Number(value);
      this.http
        .update(competency.id, data)
        .then(() => this.$emit('rated'))
        .catch(() => this.$emit('rated'));
    },
  },
};
</script>

<style src="../../pages/inbox/br-inbox.scss" lang="scss"></style>
<style src="../../pages/forms/br-forms.scss" lang="scss" scoped></style>
<style src="../../pages/jobfit/jobfit.scss" lang="scss" scoped></style>
