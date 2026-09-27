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
  <div class="ohrm-form">
    <header class="ohrm-form__head">
      <span class="ohrm-form__eyebrow">
        {{
          form.kind === 'QUIZ'
            ? $t('attendance.form_kind_quiz')
            : $t('attendance.form_kind_survey')
        }}
        <template v-if="form.dueAt">
          · {{ $t('attendance.form_due_at') }} {{ dueLabel }}
        </template>
      </span>
      <h2 class="ohrm-form__title">{{ form.title }}</h2>
      <p v-if="form.description" class="ohrm-form__description">
        {{ form.description }}
      </p>
    </header>

    <p v-if="form.anonymous" class="ohrm-form__anonymous">
      <i class="oxd-icon bi-incognito"></i>
      <span>{{ $t('attendance.form_anonymous_banner') }}</span>
    </p>

    <p v-if="restored" class="ohrm-form__restored">
      <i class="oxd-icon bi-arrow-counterclockwise"></i>
      <span>{{ $t('attendance.form_draft_restored') }}</span>
    </p>

    <section
      v-for="item in form.items"
      :key="item.id"
      :data-item-id="item.id"
      class="ohrm-form__item"
      :class="{
        'ohrm-form__item--content': item.type === 'CONTENT',
        'is-missing': missingId === item.id,
      }"
    >
      <div class="ohrm-form__prompt-row">
        <span v-if="numbers[item.id]" class="ohrm-form__number">
          {{ numbers[item.id] }}
        </span>
        <h3 class="ohrm-form__prompt">
          {{ item.prompt }}
          <span v-if="item.required" class="ohrm-form__required">*</span>
        </h3>
        <span v-if="item.points !== undefined" class="ohrm-form__points">
          {{ item.points }} {{ $t('attendance.form_points').toLowerCase() }}
        </span>
      </div>
      <p v-if="item.helpText" class="ohrm-form__help">{{ item.helpText }}</p>

      <img
        v-if="item.imageId"
        class="ohrm-form__image"
        :src="imageUrl(item.imageId)"
        loading="lazy"
        alt=""
      />
      <youtube-embed v-if="item.youtubeId" :video-id="item.youtubeId" />

      <div
        v-if="item.type === 'SINGLE' || item.type === 'MULTIPLE'"
        class="ohrm-form__options"
      >
        <label
          v-for="option in item.options"
          :key="option.id"
          class="ohrm-form__option"
          :class="{'is-selected': isChecked(item, option)}"
        >
          <input
            :type="item.type === 'SINGLE' ? 'radio' : 'checkbox'"
            :name="`ohrm-form-item-${item.id}`"
            :checked="isChecked(item, option)"
            :disabled="locked"
            @change="onChoose(item, option, $event.target.checked)"
          />
          <span>{{ option.label }}</span>
        </label>
      </div>

      <input
        v-if="item.type === 'SHORT_TEXT'"
        v-model="answers[item.id].text"
        type="text"
        class="ohrm-form__input"
        maxlength="255"
        :disabled="locked"
      />
      <textarea
        v-if="item.type === 'LONG_TEXT'"
        v-model="answers[item.id].text"
        class="ohrm-form__input ohrm-form__textarea"
        maxlength="5000"
        rows="4"
        :disabled="locked"
      ></textarea>

      <div v-if="item.type === 'SCALE'" class="ohrm-form__segmented">
        <button
          v-for="n in 5"
          :key="n"
          type="button"
          class="ohrm-form__choice ohrm-form__scale-btn"
          :class="{'is-selected': answers[item.id].scale === n}"
          :disabled="locked"
          @click="answers[item.id].scale = n"
        >
          {{ n }}
        </button>
      </div>

      <div v-if="item.type === 'YES_NO'" class="ohrm-form__segmented">
        <button
          v-for="choice in [true, false]"
          :key="String(choice)"
          type="button"
          class="ohrm-form__choice ohrm-form__yesno-btn"
          :class="{'is-selected': answers[item.id].yesNo === choice}"
          :disabled="locked"
          @click="answers[item.id].yesNo = choice"
        >
          {{ choice ? $t('attendance.form_yes') : $t('attendance.form_no') }}
        </button>
      </div>
    </section>

    <p v-if="error" class="ohrm-form__error">{{ error }}</p>
    <p v-else-if="missingId" class="ohrm-form__error">
      {{ $t('attendance.form_required_missing') }}
    </p>

    <div v-if="confirming" class="ohrm-form__confirm">
      <p class="ohrm-form__confirm-text">
        {{ $t('attendance.form_submit_confirm') }}
      </p>
      <div class="ohrm-form__confirm-actions">
        <button
          type="button"
          class="ohrm-form__btn ohrm-form__btn--secondary ohrm-form__confirm-cancel"
          @click="confirming = false"
        >
          {{ $t('general.cancel') }}
        </button>
        <button
          type="button"
          class="ohrm-form__btn ohrm-form__btn--primary ohrm-form__confirm-send"
          :disabled="submitting"
          @click="onSend"
        >
          {{ $t('attendance.form_submit') }}
        </button>
      </div>
    </div>
    <button
      v-else
      type="button"
      class="ohrm-form__btn ohrm-form__btn--primary ohrm-form__submit"
      :disabled="preview || submitting"
      @click="onSubmit"
    >
      {{ $t('attendance.form_submit') }}
    </button>
  </div>
</template>

<script>
import useFormDraft from '@/orangehrmAttendancePlugin/composables/useFormDraft';
import YoutubeEmbed from './YoutubeEmbed.vue';

const CHOICE = ['SINGLE', 'MULTIPLE'];
const TEXT = ['SHORT_TEXT', 'LONG_TEXT'];

function emptyAnswer(type) {
  if (CHOICE.includes(type)) return {optionIds: []};
  if (TEXT.includes(type)) return {text: ''};
  if (type === 'SCALE') return {scale: null};
  if (type === 'YES_NO') return {yesNo: null};
  return null;
}

function isBlank(type, answer) {
  if (!answer) return true;
  if (CHOICE.includes(type)) return answer.optionIds.length === 0;
  if (TEXT.includes(type)) return answer.text.trim() === '';
  if (type === 'SCALE') return answer.scale === null;
  return answer.yesNo === null;
}

/**
 * BR: answering a form -- shared by the phone tab and the desktop page.
 *
 * Emits `submit` with only what was answered, in the server's shape
 * ([{itemId, optionIds|text|scale|yesNo}]), and only after the employee
 * confirms: a sent form cannot be changed. The server checks everything again
 * and grades; nothing here knows the right answers.
 *
 * The parent calls clearDraft() once the server accepted the answers.
 */
export default {
  name: 'FormFiller',
  components: {'youtube-embed': YoutubeEmbed},
  props: {
    form: {type: Object, required: true},
    submitting: {type: Boolean, default: false},
    error: {type: String, default: null},
    // Builder preview: looks the same, never stores or sends anything.
    preview: {type: Boolean, default: false},
  },
  emits: ['submit'],
  setup(props) {
    return {draft: useFormDraft(props.form.id)};
  },
  data() {
    const answers = {};
    this.form.items.forEach((item) => {
      const empty = emptyAnswer(item.type);
      if (empty) answers[item.id] = empty;
    });
    return {answers, restored: false, confirming: false, missingId: null};
  },
  computed: {
    locked() {
      return this.submitting;
    },
    // Questions are numbered; content blocks are not.
    numbers() {
      const numbers = {};
      let n = 0;
      this.form.items.forEach((item) => {
        if (item.type !== 'CONTENT') numbers[item.id] = ++n;
      });
      return numbers;
    },
    dueLabel() {
      const [y, m, d] = (this.form.dueAt || '').split('-');
      return d ? `${d}/${m}/${y}` : '';
    },
  },
  watch: {
    answers: {
      deep: true,
      handler() {
        this.missingId = null;
        if (!this.preview) this.draft.save(this.compact());
      },
    },
  },
  beforeMount() {
    if (this.preview) return;
    const saved = this.draft.load();
    if (!saved) return;
    let restored = false;
    Object.entries(saved).forEach(([id, value]) => {
      if (this.answers[id] && value && typeof value === 'object') {
        Object.assign(this.answers[id], value);
        restored = true;
      }
    });
    this.restored = restored;
  },
  methods: {
    imageUrl(imageId) {
      return `${window.appGlobal.baseUrl}/attendance/brFormImage/${imageId}`;
    },
    isChecked(item, option) {
      return this.answers[item.id].optionIds.includes(option.id);
    },
    onChoose(item, option, checked) {
      const answer = this.answers[item.id];
      if (item.type === 'SINGLE') {
        answer.optionIds = checked ? [option.id] : [];
        return;
      }
      answer.optionIds = checked
        ? [...answer.optionIds.filter((id) => id !== option.id), option.id]
        : answer.optionIds.filter((id) => id !== option.id);
    },
    // Only what was answered, keyed by question -- the draft's shape.
    compact() {
      const out = {};
      this.form.items.forEach((item) => {
        const answer = this.answers[item.id];
        if (!answer || isBlank(item.type, answer)) return;
        out[item.id] = {...answer};
      });
      return out;
    },
    onSubmit() {
      const missing = this.form.items.find(
        (item) =>
          item.required &&
          item.type !== 'CONTENT' &&
          isBlank(item.type, this.answers[item.id]),
      );
      if (missing) {
        this.missingId = missing.id;
        this.$nextTick(() => {
          this.$el
            .querySelector(`[data-item-id="${missing.id}"]`)
            ?.scrollIntoView({behavior: 'smooth', block: 'center'});
        });
        return;
      }
      this.confirming = true;
    },
    onSend() {
      const payload = this.form.items
        .filter(
          (item) =>
            this.answers[item.id] && !isBlank(item.type, this.answers[item.id]),
        )
        .map((item) => {
          const answer = this.answers[item.id];
          if (CHOICE.includes(item.type)) {
            // In form order, whatever order they were ticked in
            const ids = item.options
              .map((o) => o.id)
              .filter((id) => answer.optionIds.includes(id));
            return {itemId: item.id, optionIds: ids};
          }
          if (TEXT.includes(item.type)) {
            return {itemId: item.id, text: answer.text.trim()};
          }
          if (item.type === 'SCALE') {
            return {itemId: item.id, scale: answer.scale};
          }
          return {itemId: item.id, yesNo: answer.yesNo};
        });
      this.confirming = false;
      this.$emit('submit', payload);
    },
    clearDraft() {
      this.draft.clear();
    },
  },
};
</script>

<style src="./form-filler.scss" lang="scss" scoped></style>
