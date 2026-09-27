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

/**
 * BR: small rules the job-profile comparison screens share. The fit itself is
 * computed on the server (JobFit.php); these only read what it sends.
 */

// One colour per person on the radar: at most six at a time
export const SERIES_COLORS = [
  '#1D9E75',
  '#D85A30',
  '#534AB7',
  '#185FA5',
  '#D4537E',
  '#BA7517',
];

export const MAX_PEOPLE = 20;
export const MAX_ON_CHART = 6;

/**
 * A person as the comparison names them: the employee once hired ("e9"),
 * otherwise the candidate ("c4").
 */
export function subjectKey({candidateId, employeeId}) {
  return employeeId ? `e${employeeId}` : `c${candidateId}`;
}

/**
 * Who takes a duel line: the higher fit -- the one closer to the job's range,
 * not the larger score. A tie, or a missing side, takes nobody.
 */
export function duelWinner(a, b) {
  if (a === null || a === undefined || b === null || b === undefined) {
    return null;
  }
  if (a === b) return null;
  return a > b ? 'A' : 'B';
}

/**
 * "c1,e2" from the address, without blanks, junk or repeats.
 */
export function parseSubjects(value) {
  const keys = String(value ?? '')
    .split(',')
    .map((k) => k.trim())
    .filter((k) => /^[ce][1-9]\d*$/.test(k));
  return [...new Set(keys)];
}

export function formatSplit(t, behaviorWeight) {
  return t('attendance.jobfit_weight_split')
    .replace('{b}', behaviorWeight)
    .replace('{c}', 100 - behaviorWeight);
}
