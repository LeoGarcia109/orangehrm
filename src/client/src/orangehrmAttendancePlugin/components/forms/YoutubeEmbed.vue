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
  <div class="ohrm-form__video">
    <iframe
      v-if="playing"
      :src="`https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1`"
      title="YouTube"
      allow="autoplay; encrypted-media; picture-in-picture"
      allowfullscreen
      referrerpolicy="strict-origin-when-cross-origin"
    ></iframe>
    <button
      v-else
      type="button"
      class="ohrm-form__video-poster"
      :style="{
        backgroundImage: `url(https://i.ytimg.com/vi/${videoId}/hqdefault.jpg)`,
      }"
      @click="playing = true"
    >
      <span class="ohrm-form__video-play">
        <i class="oxd-icon bi-play-fill"></i>
        {{ $t('attendance.form_watch_video') }}
      </span>
    </button>
  </div>
</template>

<script>
/**
 * A YouTube video that only loads when tapped: an embedded player pulls a few
 * megabytes the moment it renders, and this screen is opened on mobile data.
 * The id was validated on the server, so it is safe to put in the URL.
 */
export default {
  name: 'YoutubeEmbed',
  props: {
    videoId: {
      type: String,
      required: true,
    },
  },
  data() {
    return {playing: false};
  },
};
</script>

<style src="./form-filler.scss" lang="scss" scoped></style>
