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
  <section
    class="ohrm-builder__item"
    :class="{'is-error': !!error, 'is-content': item.type === 'CONTENT'}"
  >
    <header class="ohrm-builder__item-head">
      <span class="ohrm-builder__item-index">{{ index + 1 }}</span>
      <span class="ohrm-builder__item-type">
        <i class="oxd-icon" :class="typeIcon"></i>
        {{ $t(`attendance.form_type_${item.type.toLowerCase()}`) }}
      </span>
      <div v-if="editable" class="ohrm-builder__item-actions">
        <button
          type="button"
          class="ohrm-builder__icon-btn ohrm-builder__move-up"
          :disabled="index === 0"
          title="↑"
          @click="$emit('move-up')"
        >
          <i class="oxd-icon bi-arrow-up"></i>
        </button>
        <button
          type="button"
          class="ohrm-builder__icon-btn ohrm-builder__move-down"
          :disabled="index === total - 1"
          title="↓"
          @click="$emit('move-down')"
        >
          <i class="oxd-icon bi-arrow-down"></i>
        </button>
        <button
          type="button"
          class="ohrm-builder__icon-btn ohrm-builder__dup"
          :title="$t('attendance.form_duplicate')"
          @click="$emit('duplicate')"
        >
          <i class="oxd-icon bi-copy"></i>
        </button>
        <button
          type="button"
          class="ohrm-builder__icon-btn ohrm-builder__remove"
          :title="$t('attendance.form_remove')"
          @click="$emit('remove')"
        >
          <i class="oxd-icon bi-trash"></i>
        </button>
      </div>
    </header>

    <textarea
      class="ohrm-builder__input ohrm-builder__prompt"
      rows="2"
      :placeholder="$t('attendance.form_prompt')"
      :value="item.prompt"
      :disabled="!editable"
      @input="patch({prompt: $event.target.value})"
    ></textarea>
    <input
      class="ohrm-builder__input ohrm-builder__help"
      type="text"
      :placeholder="$t('attendance.form_help_text')"
      :value="item.helpText || ''"
      :disabled="!editable"
      @input="patch({helpText: $event.target.value})"
    />

    <div v-if="isChoice" class="ohrm-builder__options">
      <div
        v-for="(option, i) in item.options"
        :key="option.key"
        class="ohrm-builder__option"
        :class="{'is-correct': quiz && option.isCorrect}"
      >
        <label v-if="quiz" class="ohrm-builder__correct-mark">
          <input
            class="ohrm-builder__correct"
            :type="item.type === 'SINGLE' ? 'radio' : 'checkbox'"
            :name="`ohrm-builder-correct-${item.key}`"
            :checked="option.isCorrect"
            :disabled="!editable"
            @change="onCorrect(i, $event.target.checked)"
          />
          <span>{{ $t('attendance.form_correct') }}</span>
        </label>
        <span v-else class="ohrm-builder__option-bullet">
          <i
            class="oxd-icon"
            :class="item.type === 'SINGLE' ? 'bi-circle' : 'bi-square'"
          ></i>
        </span>
        <input
          class="ohrm-builder__input ohrm-builder__option-label"
          type="text"
          maxlength="255"
          :placeholder="`${$t('attendance.form_option')} ${i + 1}`"
          :value="option.label"
          :disabled="!editable"
          @input="patchOption(i, {label: $event.target.value})"
        />
        <button
          v-if="editable && item.options.length > 2"
          type="button"
          class="ohrm-builder__icon-btn ohrm-builder__option-remove"
          :title="$t('attendance.form_remove')"
          @click="removeOption(i)"
        >
          <i class="oxd-icon bi-x-lg"></i>
        </button>
      </div>
      <button
        v-if="editable"
        type="button"
        class="ohrm-builder__link-btn ohrm-builder__add-option"
        @click="addOption"
      >
        <i class="oxd-icon bi-plus"></i>
        {{ $t('attendance.form_add_option') }}
      </button>
    </div>

    <div v-if="item.type === 'YES_NO' && quiz" class="ohrm-builder__yesno">
      <span class="ohrm-builder__hint"
        >{{ $t('attendance.form_correct') }}:</span
      >
      <label v-for="choice in [true, false]" :key="String(choice)">
        <input
          class="ohrm-builder__yesno-correct"
          type="radio"
          :name="`ohrm-builder-yesno-${item.key}`"
          :checked="item.correctYesNo === choice"
          :disabled="!editable"
          @change="patch({correctYesNo: choice})"
        />
        {{ choice ? $t('attendance.form_yes') : $t('attendance.form_no') }}
      </label>
    </div>

    <div class="ohrm-builder__media">
      <div class="ohrm-builder__media-field">
        <span class="ohrm-builder__label">{{
          $t('attendance.form_image')
        }}</span>
        <div v-if="item.imageId" class="ohrm-builder__thumb">
          <img
            :src="`${baseUrl}/attendance/brFormImage/${item.imageId}`"
            alt=""
          />
          <button
            v-if="editable"
            type="button"
            class="ohrm-builder__icon-btn"
            :title="$t('attendance.form_remove')"
            @click="patch({imageId: null})"
          >
            <i class="oxd-icon bi-x-lg"></i>
          </button>
        </div>
        <label
          v-else-if="editable"
          class="ohrm-builder__btn ohrm-builder__image-pick"
          :class="{'is-busy': uploading}"
        >
          <i class="oxd-icon bi-image"></i>
          {{ $t('attendance.form_image') }}
          <input
            class="ohrm-builder__image-input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            :disabled="uploading"
            @change="onImage"
          />
        </label>
        <span v-if="imageError" class="ohrm-builder__field-error">
          {{ imageError }}
        </span>
      </div>
      <div class="ohrm-builder__media-field">
        <span class="ohrm-builder__label">{{
          $t('attendance.form_youtube')
        }}</span>
        <input
          class="ohrm-builder__input ohrm-builder__youtube"
          type="url"
          placeholder="https://youtu.be/..."
          :value="item.youtubeUrl"
          :disabled="!editable"
          @input="onYoutube($event.target.value)"
        />
        <span v-if="youtubeInvalid" class="ohrm-builder__field-error">
          {{ $t('attendance.form_youtube_invalid') }}
        </span>
        <img
          v-else-if="item.youtubeId"
          class="ohrm-builder__video-thumb"
          :src="`https://i.ytimg.com/vi/${item.youtubeId}/mqdefault.jpg`"
          alt=""
        />
      </div>
    </div>

    <footer v-if="item.type !== 'CONTENT'" class="ohrm-builder__item-foot">
      <label class="ohrm-builder__toggle">
        <input
          type="checkbox"
          :checked="item.required"
          :disabled="!editable"
          @change="patch({required: $event.target.checked})"
        />
        {{ $t('attendance.form_required') }}
      </label>
      <label v-if="scored" class="ohrm-builder__points">
        {{ $t('attendance.form_points') }}
        <input
          class="ohrm-builder__input"
          type="number"
          min="0"
          max="999"
          step="0.5"
          :value="item.points"
          :disabled="!editable"
          @input="patch({points: Number($event.target.value)})"
        />
      </label>
    </footer>

    <p v-if="error" class="ohrm-builder__item-error">
      <i class="oxd-icon bi-exclamation-circle-fill"></i>
      {{ error }}
    </p>
  </section>
</template>

<script>
import {extractYoutubeId} from '@/orangehrmAttendancePlugin/utils/youtubeLink';

const ICONS = {
  CONTENT: 'bi-card-text',
  SINGLE: 'bi-ui-radios',
  MULTIPLE: 'bi-ui-checks',
  SHORT_TEXT: 'bi-input-cursor-text',
  LONG_TEXT: 'bi-text-paragraph',
  SCALE: 'bi-123',
  YES_NO: 'bi-toggles',
};
const MAX_IMAGE_BYTES = 2 * 1024 * 1024;
let optionKey = 0;

/**
 * BR: one block in the form builder. Never changes its item in place: every
 * edit goes up as a whole new item through `change`, and the page owns the list.
 */
export default {
  name: 'FormBuilderItem',
  props: {
    item: {type: Object, required: true},
    index: {type: Number, required: true},
    total: {type: Number, required: true},
    quiz: {type: Boolean, default: false},
    editable: {type: Boolean, default: true},
    error: {type: String, default: null},
    baseUrl: {type: String, default: ''},
    // (file) => Promise<{id}>: the page saves a new draft first when needed
    uploadImage: {type: Function, default: null},
  },
  emits: ['change', 'move-up', 'move-down', 'duplicate', 'remove'],
  data() {
    return {uploading: false, imageError: null};
  },
  computed: {
    isChoice() {
      return ['SINGLE', 'MULTIPLE'].includes(this.item.type);
    },
    scored() {
      return (
        this.quiz &&
        ['SINGLE', 'MULTIPLE', 'YES_NO', 'SHORT_TEXT', 'LONG_TEXT'].includes(
          this.item.type,
        )
      );
    },
    typeIcon() {
      return ICONS[this.item.type];
    },
    youtubeInvalid() {
      return !!this.item.youtubeUrl?.trim() && !this.item.youtubeId;
    },
  },
  methods: {
    patch(fields) {
      this.$emit('change', {...this.item, ...fields});
    },
    patchOption(i, fields) {
      const options = this.item.options.map((o, j) =>
        j === i ? {...o, ...fields} : o,
      );
      this.patch({options});
    },
    onCorrect(i, checked) {
      // A single choice has one right answer: marking one clears the rest
      const options = this.item.options.map((o, j) => {
        if (j === i) return {...o, isCorrect: checked};
        return this.item.type === 'SINGLE' ? {...o, isCorrect: false} : o;
      });
      this.patch({options});
    },
    addOption() {
      this.patch({
        options: [
          ...this.item.options,
          {key: `new-${++optionKey}`, label: '', isCorrect: false},
        ],
      });
    },
    removeOption(i) {
      this.patch({options: this.item.options.filter((o, j) => j !== i)});
    },
    onYoutube(value) {
      this.patch({youtubeUrl: value, youtubeId: extractYoutubeId(value)});
    },
    onImage(event) {
      const file = event.target.files?.[0];
      this.imageError = null;
      if (!file || !this.uploadImage) return;
      if (file.size > MAX_IMAGE_BYTES) {
        this.imageError = 'Máx. 2 MB';
        event.target.value = '';
        return;
      }
      this.uploading = true;
      this.uploadImage(file)
        .then((image) => this.patch({imageId: image.id}))
        .catch((message) => {
          this.imageError = message || this.$t('general.error');
        })
        .finally(() => {
          this.uploading = false;
          event.target.value = '';
        });
    },
  },
};
</script>

<style src="./br-forms.scss" lang="scss" scoped></style>
