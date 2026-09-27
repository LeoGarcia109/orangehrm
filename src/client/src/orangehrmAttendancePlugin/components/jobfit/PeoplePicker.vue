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
  <div class="ohrm-picker">
    <div class="ohrm-picker__filters">
      <select v-model="filters.type" class="ohrm-builder__input" @change="load">
        <option value="">{{ $t('attendance.jobfit_type_all') }}</option>
        <option value="c">{{ $t('attendance.jobfit_type_c') }}</option>
        <option value="e">{{ $t('attendance.jobfit_type_e') }}</option>
      </select>
      <select
        v-model="filters.vacancyId"
        class="ohrm-builder__input"
        @change="onVacancy"
      >
        <option :value="null">
          {{ $t('attendance.jobfit_all_vacancies') }}
        </option>
        <option v-for="v in vacancies" :key="v.id" :value="v.id">
          {{ v.name }}
        </option>
      </select>
      <select
        v-model="filters.subunitId"
        class="ohrm-builder__input"
        @change="load"
      >
        <option :value="null">
          {{ $t('attendance.jobfit_all_companies') }}
        </option>
        <option v-for="unit in units" :key="unit.id" :value="unit.id">
          {{ unit.name }}
        </option>
      </select>
      <input
        v-model="filters.name"
        class="ohrm-builder__input ohrm-picker__name"
        :placeholder="$t('attendance.jobfit_search_name')"
      />
    </div>

    <div class="ohrm-picker__bar">
      <span class="ohrm-picker__count">{{ counter }}</span>
      <button
        type="button"
        class="ohrm-builder__link-btn ohrm-picker__all"
        :disabled="!people.length"
        @click="onSelectAll"
      >
        {{ $t('attendance.jobfit_select_all') }}
      </button>
      <button
        type="button"
        class="ohrm-builder__link-btn ohrm-picker__clear"
        :disabled="!modelValue.length"
        @click="$emit('update:modelValue', [])"
      >
        {{ $t('attendance.jobfit_clear') }}
      </button>
    </div>

    <p v-if="loaded && !people.length" class="ohrm-picker__empty">
      {{ $t('attendance.jobfit_no_people') }}
    </p>
    <div v-else class="ohrm-picker__list">
      <label
        v-for="person in people"
        :key="person.key"
        class="ohrm-picker__person"
        :class="{'is-picked': modelValue.includes(person.key)}"
      >
        <input
          type="checkbox"
          :checked="modelValue.includes(person.key)"
          :disabled="
            !modelValue.includes(person.key) && modelValue.length >= max
          "
          @change="toggle(person.key)"
        />
        <span>
          <span class="ohrm-picker__person-name">{{ person.name }}</span>
          <small>
            {{ $t(`attendance.jobfit_type_${person.type}`) }}
            <template v-if="person.subunit"> · {{ person.subunit }}</template>
            <template v-if="person.vacancies && person.vacancies.length">
              · {{ person.vacancies.join(', ') }}
            </template>
          </small>
        </span>
      </label>
    </div>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {MAX_PEOPLE} from '@/orangehrmAttendancePlugin/utils/jobFit';

/**
 * BR: who to compare -- everyone with a completed profile test, filtered by
 * type, vacancy (candidates), company (employees, sub-units included) and
 * name. "Select all" adds what the filters show, up to twenty. With a
 * vacancy chosen, its job title goes up to the page.
 */
export default {
  name: 'PeoplePicker',
  props: {
    modelValue: {type: Array, required: true},
    vacancyId: {type: Number, default: null},
  },
  emits: ['update:modelValue', 'update:vacancyId', 'vacancy-job-title'],
  setup() {
    const base = window.appGlobal.baseUrl;
    return {
      http: new APIService(base, '/api/v2/attendance/br/profile-people'),
      vacanciesHttp: new APIService(base, '/api/v2/recruitment/vacancies'),
      unitsHttp: new APIService(base, '/api/v2/admin/subunits'),
    };
  },
  data() {
    return {
      filters: {type: '', vacancyId: this.vacancyId, subunitId: null, name: ''},
      people: [],
      vacancies: [],
      units: [],
      loaded: false,
      max: MAX_PEOPLE,
      timer: null,
    };
  },
  computed: {
    counter() {
      return this.$t('attendance.jobfit_selected')
        .replace('{n}', this.modelValue.length)
        .replace('{max}', this.max);
    },
  },
  watch: {
    'filters.name'() {
      clearTimeout(this.timer);
      this.timer = setTimeout(() => this.load(), 300);
    },
  },
  beforeMount() {
    this.load();
    this.vacanciesHttp
      .getAll({limit: 0})
      .then((response) => {
        this.vacancies = response.data.data;
      })
      .catch(() => {
        this.vacancies = [];
      });
    this.unitsHttp
      .getAll({limit: 0})
      .then((response) => {
        this.units = response.data.data;
      })
      .catch(() => {
        this.units = [];
      });
  },
  beforeUnmount() {
    clearTimeout(this.timer);
  },
  methods: {
    params() {
      const params = {};
      if (this.filters.type) params.type = this.filters.type;
      if (this.filters.vacancyId) params.vacancyId = this.filters.vacancyId;
      if (this.filters.subunitId) params.subunitId = this.filters.subunitId;
      if (this.filters.name.trim()) params.name = this.filters.name.trim();
      return params;
    },
    load() {
      return this.http
        .getAll(this.params())
        .then((response) => {
          this.people = response.data.data;
          const jobTitleId = response.data.meta?.jobTitleId;
          if (this.filters.vacancyId && jobTitleId) {
            this.$emit('vacancy-job-title', jobTitleId);
          }
        })
        .catch(() => {
          this.people = [];
        })
        .finally(() => {
          this.loaded = true;
        });
    },
    onVacancy() {
      this.$emit('update:vacancyId', this.filters.vacancyId);
      this.load();
    },
    toggle(key) {
      const selected = this.modelValue.includes(key)
        ? this.modelValue.filter((k) => k !== key)
        : [...this.modelValue, key];
      this.$emit('update:modelValue', selected);
    },
    onSelectAll() {
      const selected = [...this.modelValue];
      for (const person of this.people) {
        if (selected.length >= this.max) break;
        if (!selected.includes(person.key)) selected.push(person.key);
      }
      this.$emit('update:modelValue', selected);
    },
  },
};
</script>

<style src="../../pages/forms/br-forms.scss" lang="scss" scoped></style>
<style src="../../pages/jobfit/jobfit.scss" lang="scss" scoped></style>
