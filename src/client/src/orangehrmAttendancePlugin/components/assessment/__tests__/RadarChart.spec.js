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
});
