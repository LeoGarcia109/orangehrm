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
 * A comparison as ProfileComparisonAPI sends it, for the component tests:
 * the job "Frentista" wants Conscientiousness 60-90 (essential), Extraversion
 * 50-85, ignores Openness, and has one essential competency (minimum 4).
 */

const RANGES = {
  'BIG5|E': [50, 85, 1],
  'BIG5|A': [0, 100, 1],
  'BIG5|C': [60, 90, 2],
  'BIG5|N': [0, 100, 1],
  'BIG5|O': [0, 100, 0],
  'DISC|D': [0, 100, 1],
  'DISC|I': [0, 100, 1],
  'DISC|S': [0, 100, 1],
  'DISC|C': [0, 100, 1],
};

export const profile = {
  behaviorWeight: 50,
  factors: Object.entries(RANGES).map(([key, [min, max, weight]]) => {
    const [instrument, factor] = key.split('|');
    return {instrument, factor, min, max, weight};
  }),
  competencies: [{id: 7, name: 'Atendimento', weight: 2, minLevel: 4}],
};

function person(key, name, scores, extra) {
  const factors = profile.factors.map((f) => {
    const score = scores[f.instrument][f.factor];
    const distance =
      score < f.min ? f.min - score : score > f.max ? score - f.max : 0;
    return {
      instrument: f.instrument,
      factor: f.factor,
      score,
      distance,
      fit: Math.max(0, 100 - 2.5 * distance),
      color: distance === 0 ? 'IN' : distance <= 10 ? 'NEAR' : 'FAR',
    };
  });
  return {
    key,
    type: key[0],
    id: Number(key.slice(1)),
    name,
    subunit: key[0] === 'e' ? 'Posto Acácia' : null,
    assessmentId: Number(key.slice(1)) + 100,
    scores,
    factors,
    ...extra,
  };
}

export const people = [
  person(
    'c1',
    'Ana Souza',
    {
      BIG5: {E: 70, A: 78, C: 82, N: 66, O: 55},
      DISC: {D: 45, I: 64, S: 70, C: 60},
    },
    {
      ratings: {7: 4},
      competencies: [{id: 7, rating: 4, fit: 75, belowMin: false}],
      behavior: 100,
      competency: 75,
      overall: 87.5,
      partial: false,
      alerts: [],
    },
  ),
  person(
    'e2',
    'Bruno Lima',
    {
      BIG5: {E: 88, A: 62, C: 58, N: 48, O: 72},
      DISC: {D: 72, I: 80, S: 40, C: 50},
    },
    {
      ratings: {},
      competencies: [{id: 7, rating: null, fit: null, belowMin: false}],
      behavior: 81.3,
      competency: null,
      overall: 81.3,
      partial: true,
      alerts: ['ESSENTIAL_FACTOR'],
    },
  ),
];

export const result = {
  jobTitle: {id: 5, name: 'Frentista'},
  profile,
  people,
};

// Seven people, for the six-on-the-chart limit
export function many(count) {
  return Array.from({length: count}, (_, i) => ({
    ...people[i % 2],
    key: `c${i + 1}`,
    name: `Pessoa ${i + 1}`,
  }));
}
