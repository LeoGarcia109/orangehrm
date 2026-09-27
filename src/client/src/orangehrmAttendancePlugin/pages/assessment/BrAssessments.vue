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
  <div class="orangehrm-background-container ohrm-assessments">
    <div class="orangehrm-card-container">
      <div class="ohrm-forms__head">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('attendance.assessment_profiles') }}
        </oxd-text>
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__btn--primary ohrm-assessments__new"
          @click="onNew"
        >
          <i class="oxd-icon bi-plus-lg"></i>
          {{ $t('attendance.assessment_new') }}
        </button>
      </div>

      <section v-if="creating" class="ohrm-assessments__panel">
        <div class="ohrm-builder__segmented">
          <button
            v-for="subject in ['CANDIDATE', 'EMPLOYEE']"
            :key="subject"
            type="button"
            :data-subject="subject"
            :class="{'is-selected': invite.subject === subject}"
            @click="invite.subject = subject"
          >
            {{
              subject === 'CANDIDATE'
                ? $t('attendance.assessment_candidate')
                : $t('attendance.assessment_employees')
            }}
          </button>
        </div>

        <div
          v-if="invite.subject === 'CANDIDATE'"
          class="ohrm-assessments__field"
        >
          <candidate-autocomplete v-model="invite.candidate" />
        </div>
        <template v-else>
          <div class="ohrm-builder__segmented">
            <button
              v-for="scope in scopes"
              :key="scope.id"
              type="button"
              :class="{'is-selected': invite.scope === scope.id}"
              @click="invite.scope = scope.id"
            >
              {{ scope.label }}
            </button>
          </div>
          <select
            v-if="invite.scope === 'SUBUNIT'"
            v-model="invite.subunitId"
            class="ohrm-builder__input ohrm-assessments__field"
          >
            <option :value="null">—</option>
            <option v-for="unit in units" :key="unit.id" :value="unit.id">
              {{ '— '.repeat(Math.max(unit.level - 1, 0)) }}{{ unit.name }}
            </option>
          </select>
          <div
            v-if="invite.scope === 'EMPLOYEE'"
            class="ohrm-assessments__field"
          >
            <employee-autocomplete
              v-model="invite.employee"
              :label="$t('general.employee_name')"
            />
          </div>
        </template>

        <p v-if="error" class="ohrm-builder__error">{{ error }}</p>
        <div class="ohrm-assessments__panel-actions">
          <button
            type="button"
            class="ohrm-builder__btn"
            @click="creating = false"
          >
            {{ $t('general.cancel') }}
          </button>
          <button
            type="button"
            class="ohrm-builder__btn ohrm-builder__btn--primary ohrm-assessments__send"
            :disabled="busy"
            @click="onSend"
          >
            {{ $t('attendance.assessment_new') }}
          </button>
        </div>
      </section>

      <section
        v-if="link"
        class="ohrm-assessments__panel ohrm-assessments__link"
      >
        <strong>{{ $t('attendance.assessment_invite_created') }}</strong>
        <input
          class="ohrm-builder__input"
          type="text"
          readonly
          :value="link.url"
          @focus="$event.target.select()"
        />
        <div class="ohrm-assessments__panel-actions">
          <button
            type="button"
            class="ohrm-builder__btn ohrm-assessments__copy"
            @click="onCopy"
          >
            <i
              class="oxd-icon"
              :class="copied ? 'bi-check2' : 'bi-clipboard'"
            ></i>
            {{
              copied
                ? $t('attendance.assessment_copied')
                : $t('attendance.assessment_copy_link')
            }}
          </button>
          <a
            class="ohrm-builder__btn ohrm-builder__btn--primary ohrm-assessments__whatsapp"
            :href="whatsappUrl"
            target="_blank"
            rel="noopener"
          >
            <i class="oxd-icon bi-whatsapp"></i>
            {{ $t('attendance.assessment_whatsapp') }}
          </a>
        </div>
      </section>

      <p v-if="createdCount !== null" class="ohrm-assessments__created">
        {{ $t('attendance.assessment_employees_invited') }} {{ createdCount }}
      </p>

      <div class="ohrm-assessments__filters">
        <select
          v-model="filters.subjectType"
          class="ohrm-builder__input"
          @change="load"
        >
          <option value="">{{ $t('attendance.assessment_all') }}</option>
          <option value="CANDIDATE">
            {{ $t('attendance.assessment_candidate') }}
          </option>
          <option value="EMPLOYEE">
            {{ $t('attendance.assessment_employees') }}
          </option>
        </select>
        <select
          v-model="filters.status"
          class="ohrm-builder__input"
          @change="load"
        >
          <option value="">{{ $t('attendance.assessment_all') }}</option>
          <option v-for="status in statuses" :key="status" :value="status">
            {{ $t(`attendance.assessment_status_${status.toLowerCase()}`) }}
          </option>
        </select>
        <select
          v-model="filters.vacancyId"
          class="ohrm-builder__input"
          @change="load"
        >
          <option value="">{{ $t('attendance.jobfit_all_vacancies') }}</option>
          <option v-for="v in vacancies" :key="v.id" :value="v.id">
            {{ v.name }}
          </option>
        </select>
        <button
          type="button"
          class="ohrm-builder__btn ohrm-assessments__compare"
          :disabled="!picked.length"
          @click="onCompare"
        >
          <i class="oxd-icon bi-bar-chart-line"></i>
          {{ $t('attendance.jobfit_compare_selected') }}
          <template v-if="picked.length">({{ picked.length }})</template>
        </button>
      </div>

      <p v-if="!items.length" class="ohrm-forms__empty">
        {{ $t('general.no_records_found') }}
      </p>
      <table v-else class="ohrm-br-table">
        <thead>
          <tr>
            <th></th>
            <th>{{ $t('general.name') }}</th>
            <th>{{ $t('general.type') }}</th>
            <th>{{ $t('attendance.assessment_vacancy') }}</th>
            <th>{{ $t('general.status') }}</th>
            <th>{{ $t('attendance.assessment_sent_at') }}</th>
            <th>{{ $t('attendance.assessment_completed_at') }}</th>
            <th>{{ $t('general.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td class="ohrm-assessments__pick">
              <input
                v-if="item.status === 'COMPLETED'"
                v-model="picked"
                type="checkbox"
                :value="subjectKey(item)"
                :disabled="
                  !picked.includes(subjectKey(item)) &&
                  picked.length >= maxPeople
                "
              />
            </td>
            <td class="ohrm-forms__title">{{ item.name || '—' }}</td>
            <td>
              {{
                item.subjectType === 'CANDIDATE'
                  ? $t('attendance.assessment_candidate')
                  : $t('attendance.assessment_employees')
              }}
            </td>
            <td>{{ item.vacancyName || '—' }}</td>
            <td>
              <span
                class="ohrm-assessments__status"
                :class="`is-${item.status.toLowerCase()}`"
              >
                {{
                  $t(
                    `attendance.assessment_status_${item.status.toLowerCase()}`,
                  )
                }}
              </span>
            </td>
            <td>{{ dateLabel(item.createdAt) }}</td>
            <td>{{ dateLabel(item.completedAt) }}</td>
            <td class="ohrm-forms__actions">
              <button
                v-if="item.status === 'COMPLETED'"
                type="button"
                class="ohrm-builder__link-btn ohrm-assessments__view"
                @click="onView(item)"
              >
                {{ $t('attendance.assessment_view') }}
              </button>
              <template v-else-if="item.status !== 'CANCELLED'">
                <button
                  v-if="item.subjectType === 'CANDIDATE'"
                  type="button"
                  class="ohrm-builder__link-btn ohrm-assessments__resend"
                  @click="onResend(item)"
                >
                  {{ $t('attendance.assessment_resend') }}
                </button>
                <button
                  type="button"
                  class="ohrm-builder__link-btn ohrm-assessments__cancel"
                  @click="onCancel(item)"
                >
                  {{ $t('attendance.assessment_cancel') }}
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {navigate} from '@ohrm/core/util/helper/navigation';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';
import CandidateAutocomplete from '@/orangehrmRecruitmentPlugin/components/CandidateAutocomplete.vue';
import {MAX_PEOPLE, subjectKey} from '@/orangehrmAttendancePlugin/utils/jobFit';

const ASSESSMENTS = '/api/v2/attendance/br/assessments';

/**
 * BR: Recruitment -> Behavioural profiles. Invites for candidates (a link to
 * send) and employees (they answer in the app), and the way to each profile.
 *
 * Only a hash of each link is kept, so a link can be shown only when it is
 * made; "New link" issues a fresh one and the old one stops working.
 */
export default {
  name: 'BrAssessments',
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
    'candidate-autocomplete': CandidateAutocomplete,
  },
  setup() {
    const http = new APIService(window.appGlobal.baseUrl, ASSESSMENTS);
    http.setIgnorePath(ASSESSMENTS);
    const unitsHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/admin/subunits',
    );
    const vacanciesHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/recruitment/vacancies',
    );
    return {http, unitsHttp, vacanciesHttp};
  },
  data() {
    return {
      items: [],
      units: [],
      filters: {subjectType: '', status: '', vacancyId: ''},
      vacancies: [],
      picked: [],
      maxPeople: MAX_PEOPLE,
      statuses: ['PENDING', 'COMPLETED', 'EXPIRED', 'CANCELLED'],
      creating: false,
      invite: this.emptyInvite(),
      busy: false,
      error: null,
      link: null,
      copied: false,
      createdCount: null,
    };
  },
  computed: {
    scopes() {
      return [
        {
          id: 'NETWORK',
          label: this.$t('attendance.announcement_scope_network'),
        },
        {
          id: 'SUBUNIT',
          label: this.$t('attendance.announcement_scope_subunit'),
        },
        {
          id: 'EMPLOYEE',
          label: this.$t('attendance.announcement_scope_employee'),
        },
      ];
    },
    whatsappUrl() {
      if (!this.link) return '#';
      const template = this.$t('attendance.assessment_whatsapp_message');
      // A translation without the placeholder must not lose the link
      const message = (
        template.includes('{link}') ? template : `${template} {link}`
      )
        .replace('{name}', this.link.firstName || '')
        .replace('{link}', this.link.url);
      return `https://wa.me/${this.phoneDigits(
        this.link.phone,
      )}?text=${encodeURIComponent(message)}`;
    },
  },
  beforeMount() {
    this.load();
    this.unitsHttp
      .getAll({limit: 0})
      .then((response) => {
        this.units = response.data.data;
      })
      .catch(() => {
        this.units = [];
      });
    this.vacanciesHttp
      .getAll({limit: 0})
      .then((response) => {
        this.vacancies = response.data.data;
      })
      .catch(() => {
        this.vacancies = [];
      });
  },
  methods: {
    emptyInvite() {
      return {
        subject: 'CANDIDATE',
        candidate: null,
        scope: 'NETWORK',
        subunitId: null,
        employee: null,
      };
    },
    load() {
      const params = {};
      if (this.filters.subjectType)
        params.subjectType = this.filters.subjectType;
      if (this.filters.status) params.status = this.filters.status;
      if (this.filters.vacancyId) params.vacancyId = this.filters.vacancyId;
      return this.http
        .request({method: 'GET', params})
        .then((response) => {
          this.items = response.data.data;
        })
        .catch(() => {
          this.items = [];
        });
    },
    onNew() {
      this.creating = true;
      this.invite = this.emptyInvite();
      this.error = null;
      this.link = null;
      this.createdCount = null;
    },
    onSend() {
      this.error = null;
      let body;
      if (this.invite.subject === 'CANDIDATE') {
        if (!this.invite.candidate?.id) {
          this.error = `${this.$t(
            'attendance.assessment_candidate',
          )}: ${this.$t('general.required')}`;
          return;
        }
        body = {candidateId: this.invite.candidate.id};
      } else {
        body = {
          scope: this.invite.scope,
          subunitId:
            this.invite.scope === 'SUBUNIT' ? this.invite.subunitId : null,
          employeeId:
            this.invite.scope === 'EMPLOYEE'
              ? this.invite.employee?.id ?? null
              : null,
        };
      }
      this.busy = true;
      this.http
        .create(body)
        .then((response) => {
          const data = response.data.data;
          this.creating = false;
          if (data.path) this.showLink(data);
          else this.createdCount = data.created;
          return this.load();
        })
        .catch((e) => {
          this.error = e?.data?.error?.message ?? this.$t('general.error');
        })
        .finally(() => {
          this.busy = false;
        });
    },
    onResend(item) {
      this.http.update(item.id, {action: 'resend'}).then((response) => {
        this.showLink(response.data.data);
        return this.load();
      });
    },
    onCancel(item) {
      if (!window.confirm(this.$t('attendance.assessment_cancel_confirm')))
        return;
      this.http.update(item.id, {action: 'cancel'}).then(() => this.load());
    },
    subjectKey,
    onCompare() {
      const query = {subjects: this.picked.join(',')};
      if (this.filters.vacancyId) query.vacancyId = this.filters.vacancyId;
      navigate('/recruitment/brProfileCompare', {}, query);
    },
    onView(item) {
      navigate('/recruitment/brAssessmentProfile/{id}', {id: item.id});
    },
    showLink(data) {
      this.createdCount = null;
      this.copied = false;
      this.link = {
        url: `${window.location.origin}${window.appGlobal.baseUrl}${data.path}`,
        phone: data.phone,
        firstName: data.firstName,
      };
    },
    onCopy() {
      navigator.clipboard?.writeText(this.link.url).then(() => {
        this.copied = true;
      });
    },
    // WhatsApp wants the number with country code, digits only
    phoneDigits(phone) {
      const digits = String(phone || '').replace(/\D/g, '');
      if (!digits) return '';
      return digits.startsWith('55') && digits.length >= 12
        ? digits
        : `55${digits}`;
    },
    dateLabel(value) {
      if (!value) return '—';
      const [date, time] = value.split(' ');
      const [y, m, d] = date.split('-');
      return `${d}/${m}/${y}${time ? ' ' + time : ''}`;
    },
  },
};
</script>

<style src="../inbox/br-inbox.scss" lang="scss"></style>
<style src="../forms/br-forms.scss" lang="scss" scoped></style>
<style src="./br-assessments.scss" lang="scss" scoped></style>
