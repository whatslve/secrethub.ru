<template>
  <div v-if="visible" :class="notificationClasses" class="fixed top-20 right-6 px-4 py-2 rounded shadow-lg transition-all duration-300">
    {{ message }}
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'

const props = defineProps<{
  message: string
  type?: 'success' | 'error'
}>()

const visible = ref(false)

watch(
    () => props.message,
    (val) => {
      if (val) {
        visible.value = true
        setTimeout(() => {
          visible.value = false
        }, 3000)
      }
    }
)

const notificationClasses = computed(() => {
  return props.type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white'
})
</script>
