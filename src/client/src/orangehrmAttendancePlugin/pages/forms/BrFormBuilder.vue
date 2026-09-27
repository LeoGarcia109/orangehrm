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
  <div class="orangehrm-background-container ohrm-builder">
    <div class="ohrm-builder__top">
      <button type="button" class="ohrm-builder__link-btn" @click="goToList">
        <i class="oxd-icon bi-chevron-left"></i>
        {{ $t('attendance.form_forms') }}
      </button>
      <span
        v-if="id"
        class="ohrm-builder__status"
        :class="`is-${status.toLowerCase()}`"
      >
        {{ $t(`attendance.form_status_${status.toLowerCase()}`) }}
      </span>
    </div>

    <div v-if="!editable" class="ohrm-builder__locked">
      <span>
        <i class="oxd-icon bi-lock-fill"></i>
        {{ lockedText }}
      </span>
      <div class="ohrm-builder__locked-actions">
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__duplicate"
          @click="duplicateForm"
        >
          {{ $t('attendance.form_duplicate') }}
        </button>
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__btn--primary"
          @click="goToResults"
        >
          {{ $t('attendance.form_results') }}
        </button>
      </div>
    </div>

    <section class="ohrm-builder__card ohrm-builder__header">
      <input
        v-model="title"
        class="ohrm-builder__input ohrm-builder__title"
        type="text"
        maxlength="150"
        :placeholder="$t('attendance.form_new')"
        :disabled="!editable"
      />
      <textarea
        v-model="description"
        class="ohrm-builder__input"
        rows="2"
        maxlength="5000"
        :placeholder="$t('general.description')"
        :disabled="!editable"
      ></textarea>

      <div class="ohrm-builder__settings">
        <div class="ohrm-builder__setting">
          <span class="ohrm-builder__label">{{ $t('general.type') }}</span>
          <div class="ohrm-builder__segmented">
            <button
              v-for="kind in ['QUIZ', 'SURVEY']"
              :key="kind"
              type="button"
              :data-kind="kind"
              :class="{'is-selected': settings.kind === kind}"
              :disabled="!editable"
              @click="settings.kind = kind"
            >
              {{ $t(`attendance.form_kind_${kind.toLowerCase()}`) }}
            </button>
          </div>
        </div>

        <label v-if="settings.kind === 'QUIZ'" class="ohrm-builder__setting">
          <span class="ohrm-builder__label">{{
            $t('attendance.form_pass_percent')
          }}</span>
          <input
            v-model.number="settings.passPercent"
            class="ohrm-builder__input ohrm-builder__narrow"
            type="number"
            min="0"
            max="100"
            :disabled="!editable"
          />
        </label>
        <label v-else class="ohrm-builder__setting ohrm-builder__toggle">
          <input
            v-model="settings.anonymous"
            type="checkbox"
            :disabled="!editable"
          />
          <span>{{ $t('attendance.form_anonymous') }}</span>
        </label>

        <label class="ohrm-builder__setting">
          <span class="ohrm-builder__label">{{
            $t('attendance.form_due_at')
          }}</span>
          <input
            v-model="settings.dueAt"
            class="ohrm-builder__input ohrm-builder__narrow"
            type="date"
            :disabled="!editable"
          />
        </label>
      </div>

      <div class="ohrm-builder__settings">
        <div class="ohrm-builder__setting">
          <span class="ohrm-builder__label">{{
            $t('attendance.form_audience')
          }}</span>
          <div class="ohrm-builder__segmented">
            <button
              v-for="scope in scopes"
              :key="scope.id"
              type="button"
              :class="{'is-selected': settings.scope === scope.id}"
              :disabled="!editable"
              @click="settings.scope = scope.id"
            >
              {{ scope.label }}
            </button>
          </div>
        </div>
        <label
          v-if="settings.scope === 'SUBUNIT'"
          class="ohrm-builder__setting"
        >
          <span class="ohrm-builder__label">{{ $t('general.sub_unit') }}</span>
          <select
            v-model="settings.subunitId"
            class="ohrm-builder__input"
            :disabled="!editable"
          >
            <option :value="null">—</option>
            <option v-for="unit in units" :key="unit.id" :value="unit.id">
              {{ '— '.repeat(Math.max(unit.level - 1, 0)) }}{{ unit.name }}
            </option>
          </select>
        </label>
        <div
          v-if="settings.scope === 'EMPLOYEE'"
          class="ohrm-builder__setting ohrm-builder__employee"
        >
          <employee-autocomplete
            v-if="editable"
            v-model="settings.employee"
            :label="$t('general.employee_name')"
          />
          <span v-else>{{ settings.employee?.label }}</span>
        </div>
      </div>
    </section>

    <p v-if="error" class="ohrm-builder__error">{{ error }}</p>

    <template v-if="!previewing">
      <form-builder-item
        v-for="(item, index) in items"
        :key="item.key"
        :data-index="index"
        :item="item"
        :index="index"
        :total="items.length"
        :quiz="settings.kind === 'QUIZ'"
        :editable="editable"
        :error="itemErrors[index] || null"
        :base-url="baseUrl"
        :upload-image="uploadImage"
        @change="onChange(index, $event)"
        @move-up="move(index, -1)"
        @move-down="move(index, 1)"
        @duplicate="duplicateItem(index)"
        @remove="removeItem(index)"
      />

      <div v-if="editable" class="ohrm-builder__card ohrm-builder__add">
        <span class="ohrm-builder__label"
          >+ {{ $t('attendance.form_add') }}</span
        >
        <div class="ohrm-builder__add-row">
          <button
            v-for="type in types"
            :key="type.id"
            type="button"
            class="ohrm-builder__add-btn"
            :data-type="type.id"
            @click="addItem(type.id)"
          >
            <i class="oxd-icon" :class="type.icon"></i>
            {{ $t(`attendance.form_type_${type.id.toLowerCase()}`) }}
          </button>
        </div>
      </div>
    </template>

    <div v-else class="ohrm-builder__preview-frame">
      <form-filler :key="previewKey" :form="previewForm" preview />
    </div>

    <div class="ohrm-builder__bar">
      <button
        type="button"
        class="ohrm-builder__btn ohrm-builder__preview"
        @click="togglePreview"
      >
        <i class="oxd-icon" :class="previewing ? 'bi-pencil' : 'bi-eye'"></i>
        {{ previewing ? $t('general.edit') : $t('attendance.form_preview') }}
      </button>
      <template v-if="editable">
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__save"
          :disabled="busy"
          @click="onSave"
        >
          {{ $t('attendance.form_save_draft') }}
        </button>
        <button
          type="button"
          class="ohrm-builder__btn ohrm-builder__btn--primary ohrm-builder__publish"
          :disabled="busy"
          @click="onPublish"
        >
          {{ $t('attendance.form_publish') }}
        </button>
      </template>
    </div>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {navigate} from '@ohrm/core/util/helper/navigation';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';
import FormFiller from '@/orangehrmAttendancePlugin/components/forms/FormFiller.vue';
import FormBuilderItem from './FormBuilderItem.vue';

const FORMS = '/api/v2/attendance/br/forms';
const IMAGES = '/api/v2/attendance/br/forms/images';
const SCORED = ['SINGLE', 'MULTIPLE', 'YES_NO', 'SHORT_TEXT', 'LONG_TEXT'];
const TYPES = [
  {id: 'SINGLE', icon: 'bi-ui-radios'},
  {id: 'MULTIPLE', icon: 'bi-ui-checks'},
  {id: 'SHORT_TEXT', icon: 'bi-input-cursor-text'},
  {id: 'LONG_TEXT', icon: 'bi-text-paragraph'},
  {id: 'SCALE', icon: 'bi-123'},
  {id: 'YES_NO', icon: 'bi-toggles'},
  {id: 'CONTENT', icon: 'bi-card-text'},
];
let localKey = 0;
const nextKey = () => `k${++localKey}`;

function serverMessage(e) {
  return e?.data?.error?.message ?? e?.response?.data?.error?.message ?? null;
}

function readAsBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result).split(',')[1]);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

/**
 * BR: building a test or survey block by block (HR, desktop).
 *
 * The page owns the blocks; each FormBuilderItem sends back a whole new block
 * on every edit. A draft is saved whole -- the server rebuilds the blocks in
 * order -- and publishing is refused with "Bloco N: ...", which is used here
 * to point at the card. Once published, everything is read-only.
 */
export default {
  name: 'BrFormBuilder',
  components: {
    'employee-autocomplete': EmployeeAutocomplete,
    'form-filler': FormFiller,
    'form-builder-item': FormBuilderItem,
  },
  props: {
    formId: {type: Number, default: null},
  },
  setup() {
    const http = new APIService(window.appGlobal.baseUrl, FORMS);
    const imageHttp = new APIService(window.appGlobal.baseUrl, IMAGES);
    const unitsHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/admin/subunits',
    );
    // Form rules come back as 400 with a message meant for HR
    http.setIgnorePath(FORMS);
    imageHttp.setIgnorePath(IMAGES);
    return {
      http,
      imageHttp,
      unitsHttp,
      baseUrl: window.appGlobal.baseUrl,
      types: TYPES,
    };
  },
  data() {
    return {
      id: this.formId,
      status: 'DRAFT',
      title: '',
      description: '',
      settings: {
        kind: 'QUIZ',
        anonymous: false,
        passPercent: 70,
        scope: 'NETWORK',
        subunitId: null,
        employee: null,
        dueAt: '',
      },
      items: [],
      units: [],
      busy: false,
      error: null,
      itemErrors: {},
      previewing: false,
      previewKey: 0,
    };
  },
  computed: {
    editable() {
      return this.status === 'DRAFT';
    },
    lockedText() {
      return this.$t(`attendance.form_status_${this.status.toLowerCase()}`);
    },
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
    // What the employee will see: the same shaping the server does, answer key out.
    previewForm() {
      const quiz = this.settings.kind === 'QUIZ';
      return {
        id: this.id || 0,
        title: this.title,
        description: this.description,
        dueAt: this.settings.dueAt || null,
        kind: this.settings.kind,
        anonymous: !quiz && this.settings.anonymous,
        items: this.items.map((item, i) => ({
          id: i + 1,
          type: item.type,
          prompt: item.prompt,
          helpText: item.helpText,
          required: item.required,
          imageId: item.imageId,
          youtubeId: item.youtubeId,
          options: item.options.map((o, j) => ({
            id: (i + 1) * 1000 + j,
            label: o.label,
          })),
          ...(quiz && SCORED.includes(item.type) ? {points: item.points} : {}),
        })),
      };
    },
  },
  beforeMount() {
    this.loadUnits();
    if (this.id) this.load();
  },
  methods: {
    load() {
      return this.http.get(this.id).then((response) => {
        const form = response.data.data;
        const d = form.definition;
        this.status = form.status;
        this.title = form.title;
        this.description = form.description || '';
        this.settings = {
          kind: d.kind,
          anonymous: d.anonymous,
          passPercent: d.passPercent ?? 70,
          scope: d.scope,
          subunitId: d.subunitId,
          employee: d.employeeId
            ? {id: d.employeeId, label: form.employeeName}
            : null,
          dueAt: d.dueAt || '',
        };
        this.items = d.items.map((item) => ({
          ...item,
          key: nextKey(),
          helpText: item.helpText || '',
          youtubeUrl: item.youtubeId
            ? `https://youtu.be/${item.youtubeId}`
            : '',
          options: item.options.map((o) => ({
            label: o.label,
            isCorrect: o.isCorrect,
            key: nextKey(),
          })),
        }));
      });
    },
    loadUnits() {
      return this.unitsHttp
        .getAll({limit: 0})
        .then((response) => {
          this.units = response.data.data;
        })
        .catch(() => {
          this.units = [];
        });
    },
    newItem(type) {
      const choice = ['SINGLE', 'MULTIPLE'].includes(type);
      return {
        key: nextKey(),
        type,
        prompt: '',
        helpText: '',
        required: type !== 'CONTENT',
        points: 1,
        imageId: null,
        youtubeId: null,
        youtubeUrl: '',
        correctYesNo: null,
        options: choice
          ? [
              {key: nextKey(), label: '', isCorrect: false},
              {key: nextKey(), label: '', isCorrect: false},
            ]
          : [],
      };
    },
    addItem(type) {
      this.items.push(this.newItem(type));
      this.itemErrors = {};
    },
    onChange(index, item) {
      this.items.splice(index, 1, item);
      if (this.itemErrors[index]) this.itemErrors = {};
    },
    move(index, direction) {
      const target = index + direction;
      if (target < 0 || target >= this.items.length) return;
      const items = [...this.items];
      [items[index], items[target]] = [items[target], items[index]];
      this.items = items;
      this.itemErrors = {};
    },
    duplicateItem(index) {
      const source = this.items[index];
      this.items.splice(index + 1, 0, {
        ...source,
        key: nextKey(),
        options: source.options.map((o) => ({...o, key: nextKey()})),
      });
      this.itemErrors = {};
    },
    removeItem(index) {
      this.items.splice(index, 1);
      this.itemErrors = {};
    },
    definition() {
      const s = this.settings;
      return {
        kind: s.kind,
        anonymous: s.kind === 'SURVEY' && s.anonymous,
        passPercent: s.kind === 'QUIZ' ? s.passPercent : null,
        scope: s.scope,
        subunitId: s.scope === 'SUBUNIT' ? s.subunitId : null,
        employeeId: s.scope === 'EMPLOYEE' ? s.employee?.id ?? null : null,
        dueAt: s.dueAt || null,
        items: this.items.map((item) => ({
          type: item.type,
          prompt: item.prompt,
          helpText: item.helpText || null,
          required: item.required,
          points: item.points,
          imageId: item.imageId,
          youtubeId: item.youtubeId,
          correctYesNo: item.correctYesNo,
          options: item.options.map((o) => ({
            label: o.label,
            isCorrect: o.isCorrect,
          })),
        })),
      };
    },
    save() {
      this.error = null;
      this.itemErrors = {};
      if (!this.title.trim()) {
        this.error = `${this.$t('attendance.form_new')}: ${this.$t(
          'general.required',
        )}`;
        return Promise.reject(null);
      }
      const body = {
        title: this.title.trim(),
        description: this.description.trim() || null,
        definition: this.definition(),
      };
      const request = this.id
        ? this.http.update(this.id, {action: 'save', ...body})
        : this.http.create(body).then((response) => {
            this.id = response.data.data.id;
            // So a reload opens the saved draft, not a blank one
            window.history.replaceState?.(
              null,
              '',
              `${this.baseUrl}/attendance/brFormBuilder/${this.id}`,
            );
            return response;
          });
      return request.catch((e) => {
        this.showError(e);
        return Promise.reject(null);
      });
    },
    showError(e) {
      const message = serverMessage(e) ?? this.$t('general.error');
      const block = /^Bloco (\d+):\s*(.*)$/.exec(message);
      if (!block) {
        this.error = message;
        return;
      }
      const index = Number(block[1]) - 1;
      this.itemErrors = {[index]: block[2]};
      this.$nextTick(() => {
        this.$el
          .querySelector(`[data-index="${index}"]`)
          ?.scrollIntoView({behavior: 'smooth', block: 'center'});
      });
    },
    onSave() {
      this.busy = true;
      this.save()
        .then(() => this.$toast.saveSuccess())
        .catch(() => null)
        .finally(() => {
          this.busy = false;
        });
    },
    onPublish() {
      this.busy = true;
      this.save()
        .then(() =>
          this.http.update(this.id, {action: 'publish'}).catch((e) => {
            this.showError(e);
            return Promise.reject(null);
          }),
        )
        .then(() => {
          this.status = 'PUBLISHED';
          return this.$toast.saveSuccess();
        })
        .catch(() => null)
        .finally(() => {
          this.busy = false;
        });
    },
    uploadImage(file) {
      const ensureSaved = this.id ? Promise.resolve() : this.save();
      return ensureSaved
        .then(() => readAsBase64(file))
        .then((base64) =>
          this.imageHttp.create({
            formId: this.id,
            image: {
              name: file.name,
              type: file.type,
              size: String(file.size),
              base64,
            },
          }),
        )
        .then((response) => response.data.data)
        .catch((e) => Promise.reject(serverMessage(e)));
    },
    togglePreview() {
      this.previewing = !this.previewing;
      this.previewKey++;
    },
    duplicateForm() {
      this.http.create({sourceId: this.id}).then((response) => {
        navigate('/attendance/brFormBuilder/{id}', {id: response.data.data.id});
      });
    },
    goToResults() {
      navigate('/attendance/brFormResults/{id}', {id: this.id});
    },
    goToList() {
      navigate('/attendance/brForms');
    },
  },
};
</script>

<style src="./br-forms.scss" lang="scss" scoped></style>
