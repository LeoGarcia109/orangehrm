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

import {
  SERIES_COLORS,
  duelWinner,
  formatSplit,
  parseSubjects,
  subjectKey,
} from '../jobFit';

/**
 * Small rules the comparison screens share.
 */
describe('jobFit', () => {
  it('has a distinct colour for each of the six people on the chart', () => {
    expect(new Set(SERIES_COLORS).size).toBe(6);
  });

  it('keys a person as the employee once hired, else the candidate', () => {
    expect(subjectKey({candidateId: 4, employeeId: null})).toBe('c4');
    expect(subjectKey({candidateId: 4, employeeId: 9})).toBe('e9');
    expect(subjectKey({candidateId: null, employeeId: 9})).toBe('e9');
  });

  it('gives the duel line to whoever is closer to the job', () => {
    expect(duelWinner(80, 75)).toBe('A');
    expect(duelWinner(60, 75)).toBe('B');
    expect(duelWinner(75, 75)).toBeNull();
    expect(duelWinner(null, 50)).toBeNull();
    expect(duelWinner(50, undefined)).toBeNull();
  });

  it('reads the selection from the address', () => {
    expect(parseSubjects('c1, e2,,x3,c1')).toEqual(['c1', 'e2']);
    expect(parseSubjects('')).toEqual([]);
    expect(parseSubjects(null)).toEqual([]);
  });

  it('fills the weight split sentence', () => {
    const t = () => '{b}% comportamental · {c}% competências';
    expect(formatSplit(t, 70)).toBe('70% comportamental · 30% competências');
  });
});
