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
  <div class="orangehrm-background-container">
    <div class="ohrm-br-my-forms">
      <oxd-text tag="h6" class="orangehrm-main-title ohrm-br-my-forms__title">
        {{ $t('attendance.form_my_forms') }}
      </oxd-text>
      <mobile-forms :open-form-id="formId" />
    </div>
  </div>
</template>

<script>
import MobileForms from '../mobile/MobileForms.vue';

/**
 * BR: "Meus formularios" on the desktop -- the same list and the same
 * FormFiller as the phone's "Provas" tab, so both answer exactly alike.
 * ?form=ID (a notice's "Responder") opens one directly.
 */
export default {
  name: 'BrMyForms',
  components: {'mobile-forms': MobileForms},
  props: {
    formId: {type: Number, default: null},
  },
};
</script>

<style lang="scss" scoped>
.ohrm-br-my-forms {
  max-width: 720px;
  margin: 0 auto;

  &__title {
    margin-bottom: 1rem;
  }
}
</style>
