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
  <section class="ohrm-mobile__inbox">
    <div v-if="isLoading" class="ohrm-mobile__history-empty">
      {{ $t('attendance.history_loading') }}
    </div>

    <div v-else-if="!items.length" class="ohrm-mobile__history-empty">
      {{ $t('attendance.no_announcements') }}
    </div>

    <article
      v-for="item in items"
      v-else
      :key="item.id"
      class="ohrm-mobile__notice"
      :class="{'ohrm-mobile__notice--pending': item.pendingAck}"
    >
      <header class="ohrm-mobile__notice-head" @click="onOpen(item)">
        <span class="ohrm-mobile__notice-title">
          <i
            class="oxd-icon"
            :class="item.readAt ? 'bi-envelope-open' : 'bi-envelope-fill'"
          ></i>
          {{ item.title }}
        </span>
        <span class="ohrm-mobile__notice-date">{{ item.publishedAt }}</span>
      </header>

      <div v-if="expandedId === item.id" class="ohrm-mobile__notice-body">
        <p class="ohrm-mobile__notice-text">{{ item.body }}</p>

        <div v-if="item.acknowledgedAt" class="ohrm-mobile__notice-acked">
          <i class="oxd-icon bi-check-circle-fill"></i>
          {{ $t('attendance.announcement_acknowledged') }}
          {{ item.acknowledgedAt }}
        </div>
        <button
          v-else-if="item.requiresAck"
          class="ohrm-mobile__notice-ack"
          :disabled="acking === item.id"
          @click="onAcknowledge(item)"
        >
          {{ $t('attendance.announcement_ack') }}
        </button>
      </div>

      <div v-else-if="item.pendingAck" class="ohrm-mobile__notice-pending">
        {{ $t('attendance.announcement_pending_ack') }}
      </div>
    </article>
  </section>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';

export default {
  name: 'MobileAnnouncements',
  emits: ['pending-changed'],
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/attendance/br/announcements',
    );
    return {http};
  },
  data() {
    return {
      items: [],
      isLoading: true,
      expandedId: null,
      acking: null,
    };
  },
  beforeMount() {
    this.load();
  },
  methods: {
    load() {
      this.isLoading = true;
      return this.http
        .request({method: 'GET'})
        .then((response) => {
          this.items = response.data.data;
          this.$emit('pending-changed', response.data.meta?.pendingAck ?? 0);
        })
        .catch(() => {
          this.items = [];
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    onOpen(item) {
      if (this.expandedId === item.id) {
        this.expandedId = null;
        return;
      }
      this.expandedId = item.id;
      // Opening is what "read" means; only the first one is recorded.
      if (!item.readAt) {
        this.postReceipt(item.id, false).then(() => this.load());
      }
    },
    onAcknowledge(item) {
      this.acking = item.id;
      this.postReceipt(item.id, true)
        .then(() => this.load())
        .finally(() => {
          this.acking = null;
        });
    },
    postReceipt(announcementId, acknowledge) {
      return this.http
        .request({
          method: 'POST',
          url: '/api/v2/attendance/br/announcements/receipt',
          data: {announcementId, acknowledge},
        })
        .catch(() => null);
    },
  },
};
</script>
