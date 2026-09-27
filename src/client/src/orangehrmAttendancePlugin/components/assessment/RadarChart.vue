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
  <svg
    class="ohrm-radar"
    :viewBox="`0 0 ${size} ${size}`"
    :width="size"
    :height="size"
    role="img"
  >
    <polygon
      v-for="ring in rings"
      :key="ring"
      class="ohrm-radar__ring"
      :points="polygon(axes.map(() => ring))"
    />
    <line
      v-for="(axis, i) in axes"
      :key="`axis-${axis.key}`"
      class="ohrm-radar__axis"
      :class="{'is-muted': axis.muted}"
      :x1="cx"
      :y1="cy"
      :x2="point(i, 100)[0]"
      :y2="point(i, 100)[1]"
    />
    <path
      v-if="bandPath"
      class="ohrm-radar__band"
      :d="bandPath"
      fill-rule="evenodd"
    />
    <template v-if="series">
      <polygon
        v-for="person in series"
        :key="`series-${person.key}`"
        class="ohrm-radar__series"
        :points="polygon(clamp(person.values))"
        :stroke="person.color"
        :fill="person.color"
      />
    </template>
    <template v-else>
      <polygon class="ohrm-radar__shape" :points="polygon(values)" />
      <circle
        v-for="(axis, i) in axes"
        :key="`dot-${axis.key}`"
        class="ohrm-radar__dot"
        :cx="point(i, values[i])[0]"
        :cy="point(i, values[i])[1]"
        r="3.5"
      />
    </template>
    <text
      v-for="(axis, i) in axes"
      :key="`label-${axis.key}`"
      class="ohrm-radar__label"
      :class="{'is-muted': axis.muted}"
      :x="labelPoint(i)[0]"
      :y="labelPoint(i)[1]"
      :text-anchor="anchor(i)"
      dominant-baseline="middle"
    >
      {{ axis.essential ? `${axis.label} ★` : axis.label }}
    </text>
  </svg>
</template>

<script>
/**
 * BR: a spider chart of 0-100 scores, in plain SVG -- no chart library, and
 * it prints as sharp vectors in the PDF. The first axis points straight up
 * and the rest go clockwise.
 *
 * One person: `axes[].value`. Several people (the comparison): `series`, one
 * outline each in its own colour, over the job's range shaded as a ring
 * between its minimum and its maximum (`band`, aligned with `axes`; null for
 * an axis without a range). `axes[].muted` dashes an ignored axis and
 * `axes[].essential` stars its label.
 */
export default {
  name: 'RadarChart',
  props: {
    // [{key, label, value 0..100}]
    axes: {type: Array, required: true},
    size: {type: Number, default: 300},
    // [{key, color, values: [0..100 per axis]}]
    series: {type: Array, default: null},
    // [{min, max} | null per axis]
    band: {type: Array, default: null},
  },
  data() {
    return {rings: [25, 50, 75, 100]};
  },
  computed: {
    cx() {
      return this.size / 2;
    },
    cy() {
      return this.size / 2;
    },
    // Room around the chart for the axis labels
    radius() {
      return this.size / 2 - 58;
    },
    values() {
      return this.clamp(this.axes.map((a) => a.value));
    },
    bandPath() {
      if (!this.band || !this.band.some(Boolean)) return null;
      const edge = (side) =>
        this.axes.map((a, i) => this.point(i, this.band[i]?.[side] ?? 0));
      const path = (points) => `M${points.map((p) => p.join(',')).join('L')}Z`;
      // The inner outline runs the other way, so even-odd leaves it empty
      return `${path(edge('max'))} ${path(edge('min').reverse())}`;
    },
  },
  methods: {
    clamp(values) {
      return values.map((v) => Math.min(100, Math.max(0, Number(v) || 0)));
    },
    angle(i) {
      return (2 * Math.PI * i) / this.axes.length - Math.PI / 2;
    },
    point(i, value) {
      const r = (this.radius * value) / 100;
      return [
        +(this.cx + r * Math.cos(this.angle(i))).toFixed(2),
        +(this.cy + r * Math.sin(this.angle(i))).toFixed(2),
      ];
    },
    polygon(values) {
      return values.map((v, i) => this.point(i, v).join(',')).join(' ');
    },
    labelPoint(i) {
      const r = this.radius + 16;
      return [
        this.cx + r * Math.cos(this.angle(i)),
        this.cy + r * Math.sin(this.angle(i)),
      ];
    },
    anchor(i) {
      const x = Math.cos(this.angle(i));
      if (Math.abs(x) < 0.2) return 'middle';
      return x > 0 ? 'start' : 'end';
    },
  },
};
</script>

<style scoped>
.ohrm-radar {
  display: block;
  max-width: 100%;
  height: auto;
  overflow: visible;
}
.ohrm-radar__ring {
  fill: none;
  stroke: #e3e3e3;
  stroke-width: 1;
}
.ohrm-radar__axis {
  stroke: #e3e3e3;
  stroke-width: 1;
}
.ohrm-radar__shape {
  fill: rgba(255, 123, 29, 0.22);
  stroke: #ff7b1d;
  stroke-width: 2;
  stroke-linejoin: round;
}
.ohrm-radar__axis.is-muted {
  stroke-dasharray: 3 3;
}
.ohrm-radar__band {
  fill: rgba(250, 199, 117, 0.35);
  stroke: #ba7517;
  stroke-width: 1;
  stroke-dasharray: 4 2;
}
.ohrm-radar__series {
  fill-opacity: 0.08;
  stroke-width: 2;
  stroke-linejoin: round;
}
.ohrm-radar__dot {
  fill: #ff7b1d;
}
.ohrm-radar__label {
  font-size: 11px;
  font-weight: 700;
  fill: #4a4a4a;
}
.ohrm-radar__label.is-muted {
  fill: #9a9a9a;
  font-weight: 400;
}
</style>
