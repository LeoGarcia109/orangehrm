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
  <div
    class="orangehrm-background-container ohrm-builder ohrm-compare"
    :class="`ohrm-compare--${tab}`"
  >
    <section class="ohrm-builder__card ohrm-compare__controls">
      <div class="ohrm-jobfit__section-head">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('attendance.jobfit_title_compare') }}
        </oxd-text>
      </div>
      <label class="ohrm-builder__label">
        {{ $t('attendance.jobfit_pick_job') }}
      </label>
      <select v-model="jobId" class="ohrm-builder__input ohrm-compare__job">
        <option :value="null">—</option>
        <option v-for="job in jobs" :key="job.id" :value="job.id">
          {{ job.name }}
        </option>
      </select>
      <div v-if="needsProfile" class="ohrm-compare__define">
        <span>{{ $t('attendance.jobfit_define_first') }}</span>
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__btn--primary"
          @click="onDefine"
        >
          {{ $t('attendance.jobfit_define_profile') }}
        </button>
      </div>

      <label class="ohrm-builder__label ohrm-compare__people-label">
        {{ $t('attendance.jobfit_pick_people') }}
      </label>
      <people-picker
        v-model="selected"
        :vacancy-id="vacancy"
        @update:vacancy-id="onVacancy"
        @vacancy-job-title="onVacancyJob"
      />
    </section>

    <p v-if="error" class="ohrm-builder__error">{{ error }}</p>

    <template v-if="result">
      <div class="ohrm-compare__toolbar">
        <div class="ohrm-builder__segmented ohrm-compare__tabs">
          <button
            v-for="view in views"
            :key="view"
            type="button"
            :data-tab="view"
            :class="{'is-selected': tab === view}"
            @click="tab = view"
          >
            {{ $t(`attendance.jobfit_tab_${view}`) }}
          </button>
        </div>
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__btn--primary ohrm-compare__pdf"
          @click="onPdf"
        >
          <i class="oxd-icon bi-file-earmark-pdf"></i>
          {{ $t('attendance.assessment_download_pdf') }}
        </button>
      </div>

      <section class="ohrm-builder__card ohrm-compare__print-head">
        <span class="ohrm-results__eyebrow">
          {{ $t('attendance.jobfit_comparison') }}
        </span>
        <h2 class="ohrm-jobfit__job-name">{{ result.jobTitle.name }}</h2>
      </section>

      <template v-if="tab === 'radar'">
        <section class="ohrm-builder__card ohrm-compare__radars">
          <div
            v-for="chart in charts"
            :key="chart.instrument"
            class="ohrm-compare__radar"
          >
            <h3>{{ chart.title }}</h3>
            <radar-chart
              :axes="chart.axes"
              :band="chart.band"
              :series="chart.series"
              :size="340"
            />
          </div>
        </section>
        <section class="ohrm-builder__card ohrm-compare__key">
          <span class="ohrm-compare__key-item">
            <span class="ohrm-compare__key-band"></span>
            {{ $t('attendance.jobfit_job_range') }}
          </span>
          <span
            v-for="person in shownPeople"
            :key="person.key"
            class="ohrm-compare__key-item"
          >
            <span
              class="ohrm-compare__key-line"
              :style="{background: colorOf[person.key]}"
            ></span>
            {{ person.name }} · {{ Math.round(person.overall) }}%
          </span>
          <small v-if="result.people.length > 6">
            {{ $t('attendance.jobfit_chart_limit') }}
          </small>
        </section>
      </template>

      <compare-table
        v-else-if="tab === 'table'"
        :result="result"
        :shown="shown"
        :colors="colorOf"
        @toggle-shown="toggleShown"
        @rated="compare"
      />

      <template v-else>
        <section v-if="result.people.length >= 2" class="ohrm-builder__card">
          <duel-view :result="result" />
        </section>
        <p v-else class="ohrm-forms__empty">
          {{ $t('attendance.jobfit_duel_pick') }}
        </p>
      </template>
    </template>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {navigate} from '@ohrm/core/util/helper/navigation';
import RadarChart from '@/orangehrmAttendancePlugin/components/assessment/RadarChart.vue';
import PeoplePicker from '@/orangehrmAttendancePlugin/components/jobfit/PeoplePicker.vue';
import CompareTable from '@/orangehrmAttendancePlugin/components/jobfit/CompareTable.vue';
import DuelView from '@/orangehrmAttendancePlugin/components/jobfit/DuelView.vue';
import {
  MAX_ON_CHART,
  SERIES_COLORS,
  parseSubjects,
} from '@/orangehrmAttendancePlugin/utils/jobFit';

/**
 * BR: people against a job title's profile -- overlaid radars over the job's
 * range, the ranking with every factor, and a duel of two. The job title,
 * the people and the vacancy filter live in the address, so a reload or a
 * shared link opens the same comparison.
 */
export default {
  name: 'BrProfileCompare',
  components: {
    'radar-chart': RadarChart,
    'people-picker': PeoplePicker,
    'compare-table': CompareTable,
    'duel-view': DuelView,
  },
  props: {
    jobTitleId: {type: Number, default: null},
    subjects: {type: String, default: ''},
    vacancyId: {type: Number, default: null},
  },
  setup() {
    const base = window.appGlobal.baseUrl;
    return {
      http: new APIService(base, '/api/v2/attendance/br/profile-comparison'),
      jobsHttp: new APIService(base, '/api/v2/attendance/br/job-profiles'),
    };
  },
  data() {
    return {
      jobs: [],
      jobId: this.jobTitleId,
      selected: parseSubjects(this.subjects),
      vacancy: this.vacancyId,
      result: null,
      error: null,
      tab: 'radar',
      views: ['radar', 'table', 'duel'],
      shown: [],
      colorOf: {},
      seen: [],
      request: 0,
    };
  },
  computed: {
    currentJob() {
      return this.jobs.find((j) => j.id === this.jobId) ?? null;
    },
    needsProfile() {
      return this.currentJob !== null && !this.currentJob.hasProfile;
    },
    shownPeople() {
      if (!this.result) return [];
      return this.shown
        .map((key) => this.result.people.find((p) => p.key === key))
        .filter(Boolean);
    },
    charts() {
      return ['BIG5', 'DISC'].map((instrument) => {
        const factors = this.result.profile.factors.filter(
          (f) => f.instrument === instrument,
        );
        return {
          instrument,
          title: this.$t(`attendance.assessment_${instrument.toLowerCase()}`),
          axes: factors.map((f) => ({
            key: f.factor,
            label: this.$t(
              `attendance.assessment_f_${instrument.toLowerCase()}_${f.factor.toLowerCase()}`,
            ),
            muted: f.weight === 0,
            essential: f.weight === 2,
          })),
          band: factors.map((f) =>
            f.weight === 0 ? null : {min: f.min, max: f.max},
          ),
          series: this.shownPeople.map((p) => ({
            key: p.key,
            color: this.colorOf[p.key],
            values: factors.map((f) => p.scores[instrument]?.[f.factor] ?? 0),
          })),
        };
      });
    },
  },
  watch: {
    jobId() {
      this.syncUrl();
      this.compare();
    },
    selected() {
      this.syncUrl();
      this.compare();
    },
  },
  beforeMount() {
    this.syncUrl();
    this.jobsHttp
      .getAll()
      .then((response) => {
        this.jobs = response.data.data;
      })
      .catch(() => {
        this.jobs = [];
      })
      .finally(() => this.compare());
  },
  methods: {
    compare() {
      const ticket = ++this.request;
      if (!this.jobId || !this.selected.length || this.needsProfile) {
        this.result = null;
        return Promise.resolve();
      }
      this.error = null;
      return this.http
        .getAll({jobTitleId: this.jobId, subjects: this.selected.join(',')})
        .then((response) => {
          // An older answer arriving late must not replace a newer one
          if (ticket !== this.request) return;
          this.result = response.data.data;
          this.updateShown();
        })
        .catch((e) => {
          if (ticket !== this.request) return;
          this.result = null;
          this.error =
            e?.response?.data?.error?.message ?? this.$t('general.error');
        });
    },
    /**
     * Who is on the radar: whoever already was and is still compared, then
     * people just added, best first, up to six. A new rating changes nobody,
     * so what HR hid stays hidden. Each keeps their colour.
     */
    updateShown() {
      const keys = this.result.people.map((p) => p.key);
      const fresh = keys.filter((k) => !this.seen.includes(k));
      this.seen = keys;
      const shown = this.shown.filter((k) => keys.includes(k));
      for (const key of fresh) {
        if (shown.length >= MAX_ON_CHART) break;
        shown.push(key);
      }
      this.setShown(shown);
    },
    setShown(shown) {
      const colors = {};
      shown.forEach((key) => {
        if (this.colorOf[key]) colors[key] = this.colorOf[key];
      });
      shown.forEach((key) => {
        if (colors[key]) return;
        const used = Object.values(colors);
        colors[key] = SERIES_COLORS.find((c) => !used.includes(c));
      });
      this.shown = shown;
      this.colorOf = colors;
    },
    toggleShown(key) {
      if (this.shown.includes(key)) {
        this.setShown(this.shown.filter((k) => k !== key));
      } else if (this.shown.length < MAX_ON_CHART) {
        this.setShown([...this.shown, key]);
      }
    },
    onVacancy(vacancyId) {
      this.vacancy = vacancyId;
      this.syncUrl();
    },
    onVacancyJob(jobTitleId) {
      if (!this.jobId) this.jobId = jobTitleId;
    },
    onDefine() {
      navigate(`/recruitment/brJobProfile/${this.jobId}`);
    },
    syncUrl() {
      const query = new URLSearchParams();
      if (this.jobId) query.set('jobTitleId', this.jobId);
      if (this.selected.length) query.set('subjects', this.selected.join(','));
      if (this.vacancy) query.set('vacancyId', this.vacancy);
      const search = query.toString();
      window.history.replaceState(
        null,
        '',
        `${window.appGlobal.baseUrl}/recruitment/brProfileCompare${
          search ? `?${search}` : ''
        }`,
      );
    },
    /**
     * The browser's "save as PDF", like the individual profile: the view on
     * screen, on A4, without the app around it.
     */
    onPdf() {
      const previous = document.title;
      const restore = () => {
        document.title = previous;
        window.removeEventListener('afterprint', restore);
      };
      document.title = `${this.$t('attendance.jobfit_comparison')} - ${
        this.result.jobTitle.name
      }`;
      window.addEventListener('afterprint', restore);
      window.print();
    },
  },
};
</script>

<style src="../forms/br-forms.scss" lang="scss" scoped></style>
<style src="./jobfit.scss" lang="scss" scoped></style>
<style lang="scss">
// Printing (the PDF): the comparison only, on A4, without the app around it.
@media print {
  @page {
    size: A4;
    margin: 12mm;
  }

  .oxd-sidepanel,
  .oxd-topbar,
  .oxd-layout-navigation,
  .oxd-layout-footer,
  .ohrm-compare__controls,
  .ohrm-compare__toolbar,
  .ohrm-compare__show {
    display: none !important;
  }

  body,
  .ohrm-compare,
  .oxd-layout,
  .oxd-layout-container,
  .oxd-layout-context,
  .orangehrm-background-container {
    margin: 0 !important;
    padding: 0 !important;
    background: #fff !important;
  }

  .ohrm-builder__card {
    box-shadow: none !important;
    border: 1px solid #e3e3e3;
    break-inside: avoid;
    page-break-inside: avoid;
  }

  // The factor table has a column per person: it goes on landscape, whole
  @page wide {
    size: A4 landscape;
    margin: 10mm;
  }

  .ohrm-compare--table {
    page: wide;
  }

  .ohrm-compare__scroll {
    overflow: visible !important;

    .ohrm-br-table {
      font-size: 10px;
    }

    th,
    td {
      padding: 4px 5px !important;
    }
  }

  .ohrm-compare__rating {
    appearance: none;
    min-height: 0 !important;
    padding: 0 !important;
    border: none !important;
    font-size: 11px !important;
    font-weight: 700;
  }

  * {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
