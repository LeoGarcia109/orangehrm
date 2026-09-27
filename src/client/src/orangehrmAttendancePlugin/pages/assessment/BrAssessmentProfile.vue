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
        {{ $t('attendance.assessment_profiles') }}
      </button>
    </div>

    <template v-if="profile">
      <section class="ohrm-builder__card">
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__btn--primary ohrm-profile__pdf"
          @click="onPdf"
        >
          <i class="oxd-icon bi-file-earmark-pdf"></i>
          {{ $t('attendance.assessment_download_pdf') }}
        </button>
        <div class="ohrm-profile__head">
          <span class="ohrm-results__eyebrow">
            {{
              profile.subjectType === 'CANDIDATE'
                ? $t('attendance.assessment_candidate')
                : $t('attendance.assessment_employees')
            }}
          </span>
          <h2 class="ohrm-profile__name">{{ profile.name }}</h2>
          <span class="ohrm-profile__meta">
            <template v-if="profile.vacancyName">
              {{ $t('attendance.assessment_vacancy') }}:
              {{ profile.vacancyName }} ·
            </template>
            {{ $t('attendance.assessment_completed_at') }}
            {{ dateLabel(profile.completedAt) }}
          </span>
        </div>
      </section>

      <section v-if="big5" class="ohrm-builder__card ohrm-profile__big5">
        <h3 class="ohrm-profile__section-title">
          {{ $t('attendance.assessment_big5') }}
          <small>{{ big5.version }}</small>
        </h3>
        <radar-chart
          class="ohrm-profile__radar"
          :axes="axes('big5', big5.factors)"
        />
        <div
          v-for="f in big5.factors"
          :key="f.factor"
          class="ohrm-profile__bar"
        >
          <span class="ohrm-profile__factor">
            {{ $t(`attendance.assessment_f_big5_${f.factor.toLowerCase()}`) }}
          </span>
          <span class="ohrm-profile__track">
            <span
              class="ohrm-profile__bar-fill"
              :style="{width: `${f.score}%`}"
            ></span>
          </span>
          <span class="ohrm-profile__score">{{ decimal(f.score) }}</span>
          <span class="ohrm-profile__desc">
            <span
              class="ohrm-profile__band"
              :class="`is-${f.band.toLowerCase()}`"
            >
              {{ $t(`attendance.assessment_band_${f.band.toLowerCase()}`) }}
            </span>
            {{
              $t(
                `attendance.assessment_f_big5_${f.factor.toLowerCase()}_${
                  f.score >= 50 ? 'high' : 'low'
                }`,
              )
            }}
          </span>
        </div>
      </section>

      <section v-if="disc" class="ohrm-builder__card ohrm-profile__disc">
        <h3 class="ohrm-profile__section-title">
          {{ $t('attendance.assessment_disc') }}
          <small>{{ disc.version }}</small>
        </h3>
        <radar-chart
          class="ohrm-profile__radar"
          :axes="axes('disc', disc.factors)"
        />
        <div class="ohrm-profile__styles">
          <div
            v-for="(style, role) in disc.styles"
            :key="role"
            class="ohrm-profile__style"
            :class="{'is-primary': role === 'primary'}"
          >
            <span>
              {{
                role === 'primary'
                  ? $t('attendance.assessment_primary_style')
                  : $t('attendance.assessment_secondary_style')
              }}
            </span>
            <strong>{{
              $t(`attendance.assessment_f_disc_${style.toLowerCase()}`)
            }}</strong>
            <p>
              {{
                $t(`attendance.assessment_f_disc_${style.toLowerCase()}_desc`)
              }}
            </p>
          </div>
        </div>
        <div
          v-for="f in disc.factors"
          :key="f.factor"
          class="ohrm-profile__bar"
        >
          <span class="ohrm-profile__factor">
            {{ $t(`attendance.assessment_f_disc_${f.factor.toLowerCase()}`) }}
          </span>
          <span class="ohrm-profile__track">
            <span
              class="ohrm-profile__bar-fill"
              :style="{width: `${f.score}%`}"
            ></span>
          </span>
          <span class="ohrm-profile__score">{{ decimal(f.score) }}</span>
        </div>
      </section>
    </template>

    <p v-else-if="error" class="ohrm-builder__error">{{ error }}</p>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {navigate} from '@ohrm/core/util/helper/navigation';
import RadarChart from '@/orangehrmAttendancePlugin/components/assessment/RadarChart.vue';

/**
 * BR: one behavioural profile test result, for HR.
 */
export default {
  name: 'BrAssessmentProfile',
  components: {'radar-chart': RadarChart},
  props: {
    assessmentId: {type: Number, required: true},
  },
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/assessment-profile',
    );
    return {http};
  },
  data() {
    return {profile: null, error: null};
  },
  computed: {
    big5() {
      return this.profile?.instruments?.BIG5 ?? null;
    },
    disc() {
      return this.profile?.instruments?.DISC ?? null;
    },
  },
  beforeMount() {
    this.http
      .request({method: 'GET', params: {assessmentId: this.assessmentId}})
      .then((response) => {
        this.profile = response.data.data;
      })
      .catch((e) => {
        this.error =
          e?.response?.data?.error?.message ?? this.$t('general.error');
      });
  },
  methods: {
    axes(instrument, factors) {
      return factors.map((f) => ({
        key: f.factor,
        label: this.$t(
          `attendance.assessment_f_${instrument}_${f.factor.toLowerCase()}`,
        ),
        value: f.score,
      }));
    },
    /**
     * The browser's own "save as PDF": the print stylesheet lays the profile
     * out on A4 without the app around it, and the title becomes the
     * suggested file name.
     */
    onPdf() {
      const previous = document.title;
      const restore = () => {
        document.title = previous;
        window.removeEventListener('afterprint', restore);
      };
      document.title = `${this.$t('attendance.assessment_title')} - ${
        this.profile.name
      }`;
      window.addEventListener('afterprint', restore);
      window.print();
    },
    decimal(value) {
      return String(value).replace('.', ',');
    },
    dateLabel(value) {
      if (!value) return '—';
      const [date, time] = value.split(' ');
      const [y, m, d] = date.split('-');
      return `${d}/${m}/${y}${time ? ' ' + time : ''}`;
    },
    goToList() {
      navigate('/recruitment/brAssessments');
    },
  },
};
</script>

<style src="../forms/br-forms.scss" lang="scss" scoped></style>
<style src="./br-assessments.scss" lang="scss" scoped></style>
<style lang="scss">
// Printing (the PDF): only the profile, on A4, without the app around it.
@media print {
  @page {
    size: A4;
    margin: 14mm;
  }

  .oxd-sidepanel,
  .oxd-topbar,
  .oxd-layout-navigation,
  .oxd-layout-footer,
  .ohrm-builder__top,
  .ohrm-profile__pdf {
    display: none !important;
  }

  body,
  .ohrm-results,
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

  // Bars and chips keep their colours on paper
  * {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
