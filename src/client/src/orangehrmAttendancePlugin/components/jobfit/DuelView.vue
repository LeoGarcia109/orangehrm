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
  <div class="ohrm-duel">
    <div class="ohrm-duel__pickers ohrm-compare__controls">
      <select v-model="a" class="ohrm-builder__input" data-side="a">
        <option v-for="p in result.people" :key="p.key" :value="p.key">
          {{ p.name }}
        </option>
      </select>
      <span class="ohrm-duel__versus">×</span>
      <select v-model="b" class="ohrm-builder__input" data-side="b">
        <option v-for="p in result.people" :key="p.key" :value="p.key">
          {{ p.name }}
        </option>
      </select>
    </div>

    <div v-if="personA && personB" class="ohrm-duel__head">
      <div
        v-for="side in sides"
        :key="side.id"
        class="ohrm-duel__fighter"
        :class="`ohrm-duel__fighter--${side.id}`"
      >
        <span class="ohrm-duel__name">{{ side.person.name }}</span>
        <small>
          {{ $t(`attendance.jobfit_type_${side.person.type}`) }} ·
          {{ $t('attendance.jobfit_overall') }}
          {{ Math.round(side.person.overall) }}%
        </small>
        <span class="ohrm-duel__hp" :class="`ohrm-duel__hp--${side.id}`">
          <span
            class="ohrm-duel__hp-fill"
            :style="{width: `${side.person.overall}%`, background: side.color}"
          ></span>
        </span>
      </div>
      <span class="ohrm-duel__x">×</span>
    </div>

    <template v-if="personA && personB">
      <template v-for="group in groups" :key="group.title">
        <h4 class="ohrm-duel__group">{{ group.title }}</h4>
        <div
          v-for="line in group.lines"
          :key="line.key"
          class="ohrm-duel__row"
          :data-row="line.key"
        >
          <span class="ohrm-duel__arrow">
            <i
              v-if="line.winner === 'A'"
              class="oxd-icon bi-caret-up-fill ohrm-duel__win ohrm-duel__win--a"
            ></i>
          </span>
          <span
            v-for="side in sides"
            :key="side.id"
            class="ohrm-duel__side"
            :class="`ohrm-duel__side--${side.id}`"
            :style="side.id === 'b' ? {order: 3} : {order: 1}"
          >
            <template v-if="line.kind === 'factor'">
              <span class="ohrm-duel__cells">
                <span
                  v-for="cell in cells(line.values[side.id], side.id)"
                  :key="cell.index"
                  class="ohrm-duel__cell"
                  :class="{'is-on': cell.on}"
                  :style="cell.on ? {background: side.color} : {}"
                ></span>
                <span
                  v-if="line.band"
                  class="ohrm-duel__band"
                  :style="bandStyle(line.band, side.id)"
                ></span>
              </span>
            </template>
            <span v-else class="ohrm-duel__stars">
              <i
                v-for="star in stars(line.values[side.id], side.id)"
                :key="star.index"
                class="oxd-icon"
                :class="[
                  star.on ? 'bi-star-fill' : 'bi-star',
                  {'is-min': star.index === line.minLevel},
                ]"
                :style="star.on ? {color: side.color} : {}"
              ></i>
            </span>
          </span>
          <span class="ohrm-duel__label" :style="{order: 2}">
            {{ line.label }}
            <small v-if="line.kind === 'factor'">
              {{ decimal(line.values.a) }} · {{ decimal(line.values.b) }}
            </small>
          </span>
          <span class="ohrm-duel__arrow" :style="{order: 4}">
            <i
              v-if="line.winner === 'B'"
              class="oxd-icon bi-caret-up-fill ohrm-duel__win ohrm-duel__win--b"
            ></i>
          </span>
        </div>
      </template>

      <div class="ohrm-duel__legend">
        <span class="ohrm-duel__legend-band"></span>
        {{ $t('attendance.jobfit_job_range') }}
        <i class="oxd-icon bi-caret-up-fill"></i>
        {{ $t('attendance.jobfit_closer') }}
        <span>{{ $t('attendance.jobfit_essential_mark') }}</span>
      </div>
    </template>
  </div>
</template>

<script>
import {
  SERIES_COLORS,
  duelWinner,
} from '@/orangehrmAttendancePlugin/utils/jobFit';

/**
 * BR: X against Y, in the bars of an RPG character sheet -- X grows to the
 * left, Y to the right, and the job's range sits over each bar as a gold
 * frame. The arrow on a line goes to whoever is closer to the job (the higher
 * fit), which is not always the bigger bar. Competencies are HR's 1-5 stars.
 */
export default {
  name: 'DuelView',
  props: {
    result: {type: Object, required: true},
  },
  data() {
    const [first, second] = this.result.people;
    return {a: first?.key ?? null, b: second?.key ?? null};
  },
  computed: {
    personA() {
      return this.result.people.find((p) => p.key === this.a) ?? null;
    },
    personB() {
      return this.result.people.find((p) => p.key === this.b) ?? null;
    },
    sides() {
      return [
        {id: 'a', person: this.personA, color: SERIES_COLORS[0]},
        {id: 'b', person: this.personB, color: SERIES_COLORS[1]},
      ];
    },
    groups() {
      const factorLines = (instrument) =>
        this.result.profile.factors
          .filter((f) => f.instrument === instrument)
          .map((f) => {
            const fa = this.fitOf(this.personA, f);
            const fb = this.fitOf(this.personB, f);
            return {
              kind: 'factor',
              key: `${f.instrument}-${f.factor}`,
              label: `${this.$t(
                `attendance.assessment_f_${instrument.toLowerCase()}_${f.factor.toLowerCase()}`,
              )}${f.weight === 2 ? ' ★' : ''}`,
              values: {a: fa?.score ?? null, b: fb?.score ?? null},
              band: f.weight === 0 ? null : {min: f.min, max: f.max},
              winner: f.weight === 0 ? null : duelWinner(fa?.fit, fb?.fit),
            };
          });
      const groups = [
        {
          title: this.$t('attendance.assessment_big5'),
          lines: factorLines('BIG5'),
        },
        {
          title: this.$t('attendance.assessment_disc'),
          lines: factorLines('DISC'),
        },
      ];
      if (this.result.profile.competencies.length) {
        groups.push({
          title: this.$t('attendance.jobfit_competencies'),
          lines: this.result.profile.competencies.map((c) => {
            const ra = this.personA.ratings[c.id] ?? null;
            const rb = this.personB.ratings[c.id] ?? null;
            return {
              kind: 'competency',
              key: `comp-${c.id}`,
              label: `${c.name}${c.weight === 2 ? ' ★' : ''}`,
              values: {a: ra, b: rb},
              minLevel: c.minLevel,
              winner: duelWinner(ra, rb),
            };
          }),
        });
      }
      return groups;
    },
  },
  methods: {
    fitOf(person, factor) {
      return person.factors.find(
        (f) => f.instrument === factor.instrument && f.factor === factor.factor,
      );
    },
    // Ten blocks, one per ten points (rounded); X's bar runs right to left
    cells(score, side) {
      const list = Array.from({length: 10}, (_, index) => ({
        index,
        on: score !== null && score >= (index + 1) * 10 - 5,
      }));
      return side === 'a' ? list.reverse() : list;
    },
    bandStyle(band, side) {
      const left = side === 'a' ? 100 - band.max : band.min;
      return {left: `${left}%`, width: `${band.max - band.min}%`};
    },
    stars(rating, side) {
      const list = Array.from({length: 5}, (_, i) => ({
        index: i + 1,
        on: rating !== null && i < rating,
      }));
      return side === 'a' ? list.reverse() : list;
    },
    decimal(value) {
      return value === null ? '—' : String(value).replace('.', ',');
    },
  },
};
</script>

<style src="../../pages/forms/br-forms.scss" lang="scss" scoped></style>
<style src="../../pages/jobfit/jobfit.scss" lang="scss" scoped></style>
