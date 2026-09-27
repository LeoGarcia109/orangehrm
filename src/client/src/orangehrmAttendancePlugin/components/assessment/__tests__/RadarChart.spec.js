/**
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
 */

import {mount} from '@vue/test-utils';
import RadarChart from '../RadarChart.vue';

/**
 * The profile's spider chart, in plain SVG so it stays sharp when printed.
 * The geometry is what matters: 100 reaches the outer ring on its own axis,
 * 0 sits at the centre, and the first axis points straight up.
 */
describe('RadarChart', () => {
  const axes = [
    {key: 'E', label: 'Extroversão', value: 100},
    {key: 'A', label: 'Amabilidade', value: 50},
    {key: 'C', label: 'Conscienciosidade', value: 0},
    {key: 'N', label: 'Estabilidade', value: 75},
    {key: 'O', label: 'Abertura', value: 25},
  ];
  const points = (wrapper) =>
    wrapper
      .find('.ohrm-radar__shape')
      .attributes('points')
      .trim()
      .split(' ')
      .map((p) => p.split(',').map(Number));

  it('draws one vertex per axis', () => {
    expect(points(mount(RadarChart, {props: {axes}}))).toHaveLength(5);
  });

  it('puts the first axis straight up and 100 on the outer ring', () => {
    const wrapper = mount(RadarChart, {props: {axes, size: 300}});
    const [x, y] = points(wrapper)[0];
    const {cx, cy, radius} = wrapper.vm;

    expect(x).toBeCloseTo(cx, 1);
    expect(y).toBeCloseTo(cy - radius, 1);
  });

  it('puts 0 at the centre', () => {
    const wrapper = mount(RadarChart, {props: {axes, size: 300}});
    const [x, y] = points(wrapper)[2];

    expect(x).toBeCloseTo(wrapper.vm.cx, 1);
    expect(y).toBeCloseTo(wrapper.vm.cy, 1);
  });

  it('labels every axis and draws the reference rings', () => {
    const wrapper = mount(RadarChart, {props: {axes}});

    expect(wrapper.findAll('.ohrm-radar__label').map((l) => l.text())).toEqual(
      axes.map((a) => a.label),
    );
    expect(wrapper.findAll('.ohrm-radar__ring')).toHaveLength(4);
  });

  it('clamps values outside 0-100', () => {
    const wrapper = mount(RadarChart, {
      props: {
        axes: [{key: 'D', label: 'D', value: 140}, ...axes.slice(1, 4)],
        size: 300,
      },
    });
    const [, y] = points(wrapper)[0];

    expect(y).toBeCloseTo(wrapper.vm.cy - wrapper.vm.radius, 1);
  });

  describe('several people and the job range', () => {
    const series = [
      {key: 'c1', color: '#1D9E75', values: [80, 60, 40, 70, 20]},
      {key: 'e2', color: '#D85A30', values: [30, 50, 90, 60, 10]},
    ];
    const band = [{min: 60, max: 90}, {min: 50, max: 85}, null, null, null];

    it('draws one outline per person, in their colour', () => {
      const wrapper = mount(RadarChart, {props: {axes, series}});
      const shapes = wrapper.findAll('polygon.ohrm-radar__series');

      expect(shapes).toHaveLength(2);
      expect(shapes.map((s) => s.attributes('stroke'))).toEqual([
        '#1D9E75',
        '#D85A30',
      ]);
      expect(wrapper.find('.ohrm-radar__shape').exists()).toBe(false);
    });

    it('shades the job range as a ring between min and max', () => {
      const wrapper = mount(RadarChart, {props: {axes, series, band}});
      const d = wrapper.find('path.ohrm-radar__band').attributes('d');

      expect(d.match(/M/g)).toHaveLength(2);
      expect(
        wrapper.find('path.ohrm-radar__band').attributes('fill-rule'),
      ).toBe('evenodd');
    });

    it('has no band without a range', () => {
      const wrapper = mount(RadarChart, {props: {axes, series}});
      expect(wrapper.find('path.ohrm-radar__band').exists()).toBe(false);
    });

    it('dashes ignored axes and stars essential ones', () => {
      const wrapper = mount(RadarChart, {
        props: {
          axes: [
            {...axes[0], essential: true},
            {...axes[1], muted: true},
            ...axes.slice(2),
          ],
          series,
        },
      });
      const lines = wrapper.findAll('.ohrm-radar__axis');

      expect(lines[1].classes()).toContain('is-muted');
      expect(lines[0].classes()).not.toContain('is-muted');
      expect(wrapper.findAll('.ohrm-radar__label')[0].text()).toBe(
        'Extroversão ★',
      );
    });
  });
});
