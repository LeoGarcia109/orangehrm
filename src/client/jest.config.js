module.exports = {
  preset: '@vue/cli-plugin-unit-jest/presets/typescript-and-babel',
  transform: {
    '^.+\\.vue$': '@vue/vue3-jest',
  },
  coverageReporters: ['html'],
  // Mirrors the webpack `resolve.alias` entries in vue.config.js
  moduleNameMapper: {
    '^@ohrm/core/(.*)$': '<rootDir>/src/core/$1',
    '^@ohrm/components/(.*)$': '<rootDir>/src/core/components/$1',
  },
};
