<template>
  <div class="flex gap-6 p-6 max-w-7xl mx-auto">
    <aside class="w-72 flex-shrink-0 space-y-6">
      <UserCard />
      <ActivityFeed />
    </aside>

    <main class="flex-1 space-y-6">
      <EventList />
    </main>

    <aside class="w-72 flex-shrink-0 space-y-6">
      <ServiceHub />
    </aside>
  </div>
</template>

<script setup lang="ts">
import UserCard from '@/components/ServicePanel/UserCard.vue'
import ActivityFeed from '@/components/ServicePanel/ActivityFeed.vue'
import EventList from '@/components/ServicePanel/EventList.vue'
import ServiceHub from '@/components/ServicePanel/ServiceHub.vue'
</script>
