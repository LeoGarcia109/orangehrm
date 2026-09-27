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
    class="ohrm-jobfit__factor"
    :class="{'is-muted': modelValue.weight === 0}"
  >
    <span class="ohrm-jobfit__factor-label">{{ label }}</span>
    <span class="ohrm-jobfit__track">
      <span
        class="ohrm-jobfit__range"
        :style="{
          left: `${modelValue.min}%`,
          width: `${modelValue.max - modelValue.min}%`,
        }"
      ></span>
    </span>
    <span class="ohrm-jobfit__edges">
      <label>
        {{ $t('attendance.jobfit_min') }}
        <input
          type="number"
          class="ohrm-builder__input"
          data-edge="min"
          min="0"
          max="100"
          step="5"
          :value="modelValue.min"
          @change="onEdge('min', $event.target.value)"
        />
      </label>
      <label>
        {{ $t('attendance.jobfit_max') }}
        <input
          type="number"
          class="ohrm-builder__input"
          data-edge="max"
          min="0"
          max="100"
          step="5"
          :value="modelValue.max"
          @change="onEdge('max', $event.target.value)"
        />
      </label>
    </span>
    <span class="ohrm-builder__segmented ohrm-jobfit__weight">
      <button
        v-for="weight in [0, 1, 2]"
        :key="weight"
        type="button"
        :data-weight="weight"
        :class="{'is-selected': modelValue.weight === weight}"
        @click="emitValue({weight})"
      >
        {{ $t(`attendance.jobfit_weight_${weight}`) }}
      </button>
    </span>
  </div>
</template>

<script>
/**
 * BR: one factor of a job title's profile -- the range the job wants on
 * 0-100, painted on a track, and how much the factor matters. Edges snap to
 * fives; typing a minimum above the maximum swaps them.
 */
export default {
  name: 'FactorRangeRow',
  props: {
    label: {type: String, required: true},
    // {min, max, weight}
    modelValue: {type: Object, required: true},
  },
  emits: ['update:modelValue'],
  methods: {
    onEdge(edge, raw) {
      const number = Number(raw);
      const value = Number.isFinite(number)
        ? Math.min(100, Math.max(0, Math.round(number / 5) * 5))
        : this.modelValue[edge];
      const next = {...this.modelValue, [edge]: value};
      if (next.min > next.max) {
        [next.min, next.max] = [next.max, next.min];
      }
      this.$emit('update:modelValue', next);
    },
    emitValue(change) {
      this.$emit('update:modelValue', {...this.modelValue, ...change});
    },
  },
};
</script>

<style src="../../pages/forms/br-forms.scss" lang="scss" scoped></style>
<style lang="scss" scoped>
@import '../../pages/mobile/mobile-tokens';

.ohrm-jobfit__factor {
  display: grid;
  grid-template-columns: 11rem minmax(8rem, 1fr) auto auto;
  align-items: center;
  gap: 0.75rem;
  padding: 0.45rem 0;
  border-bottom: 1px solid #f3f3f3;

  &.is-muted {
    .ohrm-jobfit__factor-label,
    .ohrm-jobfit__track,
    .ohrm-jobfit__edges {
      opacity: 0.45;
    }
  }
}

.ohrm-jobfit__factor-label {
  font-size: 0.88rem;
  font-weight: 600;
  color: #2c2c2c;
}

.ohrm-jobfit__track {
  position: relative;
  height: 0.7rem;
  border-radius: 999px;
  background: #eeeeee;
}

.ohrm-jobfit__range {
  position: absolute;
  top: -0.15rem;
  bottom: -0.15rem;
  min-width: 0.3rem;
  border: 1.5px solid #ba7517;
  border-radius: 999px;
  background: rgba(250, 199, 117, 0.6);
}

.ohrm-jobfit__edges {
  display: inline-flex;
  gap: 0.5rem;

  label {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.75rem;
    color: #5c5c5c;
  }

  input {
    width: 4.2rem;
    min-height: 2.2rem;
    padding: 0 0.4rem;
  }
}

.ohrm-jobfit__weight button {
  min-height: 2.2rem;
  padding: 0 0.7rem;
  font-size: 0.78rem;
}

@media (max-width: 800px) {
  .ohrm-jobfit__factor {
    grid-template-columns: 1fr;
    gap: 0.4rem;
  }
}
</style>
