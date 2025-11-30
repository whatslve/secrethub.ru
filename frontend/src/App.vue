<template>
  <div>
    <Header />

    <div class="page-wrap">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import Header from '@/components/Header.vue'
</script>

<style>
.page-wrap {
  padding-top: 60px; /* чтобы контент не залезал под шапку */
}
</style>
