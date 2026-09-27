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
  <div class="orangehrm-background-container ohrm-builder ohrm-jobfit">
    <div class="ohrm-builder__top">
      <button type="button" class="ohrm-builder__link-btn" @click="goToList">
        <i class="oxd-icon bi-chevron-left"></i>
        {{ $t('attendance.jobfit_title_profiles') }}
      </button>
    </div>

    <p v-if="loadError" class="ohrm-builder__error">{{ loadError }}</p>

    <template v-if="profile">
      <section class="ohrm-builder__card">
        <span class="ohrm-results__eyebrow">
          {{ $t('attendance.jobfit_job_title') }}
        </span>
        <h2 class="ohrm-jobfit__job-name">{{ profile.jobTitle.name }}</h2>
      </section>

      <section class="ohrm-builder__card">
        <div class="ohrm-jobfit__section-head">
          <h3>{{ $t('attendance.jobfit_factors') }}</h3>
          <button
            type="button"
            class="ohrm-builder__btn ohrm-jobfit__suggest"
            @click="toggleSuggest"
          >
            <i class="oxd-icon bi-people"></i>
            {{ $t('attendance.jobfit_suggest') }}
          </button>
        </div>

        <div v-if="suggesting" class="ohrm-jobfit__suggest-panel">
          <p>{{ $t('attendance.jobfit_suggest_hint') }}</p>
          <select
            v-model="unitId"
            class="ohrm-builder__input"
            @change="loadReferences"
          >
            <option :value="null">
              {{ $t('attendance.jobfit_all_companies') }}
            </option>
            <option v-for="unit in units" :key="unit.id" :value="unit.id">
              {{ unit.name }}
            </option>
          </select>
          <p v-if="!references.length">
            {{ $t('attendance.jobfit_no_people') }}
          </p>
          <div v-else class="ohrm-jobfit__refs">
            <label
              v-for="person in references"
              :key="person.key"
              class="ohrm-jobfit__ref"
            >
              <input
                v-model="picked"
                type="checkbox"
                :value="person.id"
                @change="suggestError = null"
              />
              <span>
                {{ person.name }}
                <small v-if="person.subunit">· {{ person.subunit }}</small>
              </span>
            </label>
          </div>
          <p v-if="suggestError" class="ohrm-jobfit__suggest-error">
            {{ suggestError }}
          </p>
          <div>
            <button
              type="button"
              class="ohrm-builder__btn ohrm-builder__btn--primary ohrm-jobfit__apply"
              :disabled="busy"
              @click="onApplySuggestion"
            >
              {{ $t('attendance.jobfit_apply') }}
            </button>
          </div>
        </div>

        <p v-if="notice" class="ohrm-jobfit__notice">{{ notice }}</p>

        <template v-for="group in groups" :key="group.instrument">
          <h4 class="ohrm-jobfit__group-title">{{ group.title }}</h4>
          <factor-range-row
            v-for="index in group.indexes"
            :key="`${group.instrument}-${profile.factors[index].factor}`"
            :label="factorLabel(profile.factors[index])"
            :model-value="profile.factors[index]"
            @update:model-value="setFactor(index, $event)"
          />
        </template>
      </section>

      <section class="ohrm-builder__card">
        <div class="ohrm-jobfit__section-head">
          <h3>{{ $t('attendance.jobfit_competencies') }}</h3>
        </div>
        <div
          v-for="(competency, index) in profile.competencies"
          :key="competency.uid"
          class="ohrm-jobfit__competency"
        >
          <input
            v-model="competency.name"
            class="ohrm-builder__input"
            maxlength="100"
            :placeholder="$t('attendance.jobfit_competency_name')"
          />
          <span class="ohrm-builder__segmented">
            <button
              v-for="weight in [1, 2]"
              :key="weight"
              type="button"
              :data-weight="weight"
              :class="{'is-selected': competency.weight === weight}"
              @click="competency.weight = weight"
            >
              {{ $t(`attendance.jobfit_weight_${weight}`) }}
            </button>
          </span>
          <label class="ohrm-jobfit__min-level">
            {{ $t('attendance.jobfit_min_level') }}
            <select
              v-model.number="competency.minLevel"
              class="ohrm-builder__input"
            >
              <option v-for="level in [1, 2, 3, 4, 5]" :key="level">
                {{ level }}
              </option>
            </select>
          </label>
          <span class="ohrm-jobfit__competency-actions">
            <button
              type="button"
              class="ohrm-builder__icon-btn"
              :disabled="index === 0"
              @click="move(index, -1)"
            >
              <i class="oxd-icon bi-arrow-up"></i>
            </button>
            <button
              type="button"
              class="ohrm-builder__icon-btn"
              :disabled="index === profile.competencies.length - 1"
              @click="move(index, 1)"
            >
              <i class="oxd-icon bi-arrow-down"></i>
            </button>
            <button
              type="button"
              class="ohrm-builder__icon-btn ohrm-jobfit__remove"
              @click="profile.competencies.splice(index, 1)"
            >
              <i class="oxd-icon bi-trash"></i>
            </button>
          </span>
        </div>
        <button
          type="button"
          class="ohrm-builder__link-btn ohrm-jobfit__add"
          @click="addCompetency"
        >
          <i class="oxd-icon bi-plus-lg"></i>
          {{ $t('attendance.jobfit_add_competency') }}
        </button>
      </section>

      <section class="ohrm-builder__card">
        <div class="ohrm-jobfit__section-head">
          <h3>{{ $t('attendance.jobfit_behavior_weight') }}</h3>
        </div>
        <div class="ohrm-jobfit__split">
          <input
            v-model.number="profile.behaviorWeight"
            type="number"
            class="ohrm-builder__input"
            min="0"
            max="100"
            step="5"
          />
          <span>{{ split }}</span>
        </div>
      </section>

      <p v-if="error" class="ohrm-builder__error">{{ error }}</p>
      <div class="ohrm-builder__bar">
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__btn--primary ohrm-jobfit__save"
          :disabled="busy"
          @click="onSave"
        >
          {{ $t('attendance.jobfit_save') }}
        </button>
      </div>
    </template>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {navigate} from '@ohrm/core/util/helper/navigation';
import FactorRangeRow from '@/orangehrmAttendancePlugin/components/jobfit/FactorRangeRow.vue';
import {formatSplit} from '@/orangehrmAttendancePlugin/utils/jobFit';

let uid = 0;

/**
 * BR: the ideal profile of one job title -- the range and importance of each
 * behavioural factor, the competencies and how the two parts share the fit.
 * The suggestion from reference employees only fills the form; nothing is
 * kept until HR saves.
 */
export default {
  name: 'BrJobProfile',
  components: {'factor-range-row': FactorRangeRow},
  props: {
    jobTitleId: {type: Number, required: true},
  },
  setup() {
    const base = window.appGlobal.baseUrl;
    return {
      http: new APIService(base, '/api/v2/attendance/br/job-profiles'),
      peopleHttp: new APIService(base, '/api/v2/attendance/br/profile-people'),
      suggestHttp: new APIService(
        base,
        '/api/v2/attendance/br/job-profile-suggestion',
      ),
      unitsHttp: new APIService(base, '/api/v2/admin/subunits'),
    };
  },
  data() {
    return {
      profile: null,
      loadError: null,
      error: null,
      notice: null,
      busy: false,
      suggesting: false,
      units: [],
      unitId: null,
      references: [],
      picked: [],
      suggestError: null,
    };
  },
  computed: {
    groups() {
      const indexes = (instrument) =>
        this.profile.factors
          .map((f, i) => (f.instrument === instrument ? i : null))
          .filter((i) => i !== null);
      return [
        {
          instrument: 'BIG5',
          title: this.$t('attendance.assessment_big5'),
          indexes: indexes('BIG5'),
        },
        {
          instrument: 'DISC',
          title: this.$t('attendance.assessment_disc'),
          indexes: indexes('DISC'),
        },
      ];
    },
    split() {
      const weight = Math.min(
        100,
        Math.max(0, Number(this.profile.behaviorWeight) || 0),
      );
      return formatSplit(this.$t, weight);
    },
  },
  beforeMount() {
    this.http
      .get(this.jobTitleId)
      .then((response) => {
        this.profile = this.withUids(response.data.data);
      })
      .catch((e) => {
        this.loadError =
          e?.response?.data?.error?.message ?? this.$t('general.error');
      });
  },
  methods: {
    withUids(profile) {
      return {
        ...profile,
        competencies: profile.competencies.map((c) => ({...c, uid: ++uid})),
      };
    },
    factorLabel(f) {
      return this.$t(
        `attendance.assessment_f_${f.instrument.toLowerCase()}_${f.factor.toLowerCase()}`,
      );
    },
    setFactor(index, value) {
      this.profile.factors[index] = {...this.profile.factors[index], ...value};
      this.notice = null;
    },
    addCompetency() {
      this.profile.competencies.push({
        id: null,
        name: '',
        weight: 1,
        minLevel: 3,
        uid: ++uid,
      });
    },
    move(index, step) {
      const list = this.profile.competencies;
      [list[index], list[index + step]] = [list[index + step], list[index]];
    },
    toggleSuggest() {
      this.suggesting = !this.suggesting;
      if (!this.suggesting) return;
      if (!this.units.length) {
        this.unitsHttp
          .getAll({limit: 0})
          .then((response) => {
            this.units = response.data.data;
          })
          .catch(() => {
            this.units = [];
          });
      }
      this.loadReferences();
    },
    loadReferences() {
      const params = {type: 'e'};
      if (this.unitId) params.subunitId = this.unitId;
      return this.peopleHttp
        .getAll(params)
        .then((response) => {
          this.references = response.data.data;
        })
        .catch(() => {
          this.references = [];
        });
    },
    onApplySuggestion() {
      if (!this.picked.length) {
        this.suggestError = this.$t('attendance.jobfit_no_reference');
        return;
      }
      this.busy = true;
      this.suggestHttp
        .getAll({empNumbers: this.picked.join(',')})
        .then((response) => {
          const byKey = {};
          response.data.data.forEach((row) => {
            byKey[`${row.instrument}|${row.factor}`] = row;
          });
          this.profile.factors = this.profile.factors.map((f) => {
            const row = byKey[`${f.instrument}|${f.factor}`];
            return row ? {...f, min: row.min, max: row.max} : f;
          });
          this.notice = this.$t('attendance.jobfit_applied');
          this.suggesting = false;
        })
        .catch((e) => {
          this.suggestError =
            e?.response?.data?.error?.message ?? this.$t('general.error');
        })
        .finally(() => {
          this.busy = false;
        });
    },
    payload() {
      return {
        behaviorWeight: Number(this.profile.behaviorWeight) || 0,
        factors: this.profile.factors.map(
          ({instrument, factor, min, max, weight}) => ({
            instrument,
            factor,
            min,
            max,
            weight,
          }),
        ),
        competencies: this.profile.competencies.map(
          ({id, name, weight, minLevel}) => ({id, name, weight, minLevel}),
        ),
      };
    },
    onSave() {
      this.busy = true;
      this.error = null;
      this.http
        .update(this.jobTitleId, this.payload())
        .then((response) => {
          this.profile = this.withUids(response.data.data);
          this.notice = null;
          return this.$toast.saveSuccess();
        })
        .catch((e) => {
          this.error =
            e?.response?.data?.error?.message ?? this.$t('general.error');
        })
        .finally(() => {
          this.busy = false;
        });
    },
    goToList() {
      navigate('/recruitment/brJobProfiles');
    },
  },
};
</script>

<style src="../forms/br-forms.scss" lang="scss" scoped></style>
<style src="./jobfit.scss" lang="scss" scoped></style>
