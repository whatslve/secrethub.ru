<template>
  <div>
    <div v-if="showFakeButton">
      <button @click="loginFake" class="fake-btn">Локальный вход</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, defineEmits } from 'vue'
import api, { setAuthToken } from '@/plugins/axios.js'

const showFakeButton = import.meta.env.MODE === 'development'
const emit = defineEmits<{
  (e: 'notify', message: string, type?: 'success' | 'error'): void
}>()

async function loginFake() {
  try {
    const res = await api.post('/fake/auth')
    if (res.data?.token && res.data?.user) {
      localStorage.setItem('token', res.data.token)
      localStorage.setItem('user', JSON.stringify(res.data.user))
      setAuthToken(res.data.token)

      // уведомление
      emit('notify', `Вход выполнен как ${res.data.user.name}`, 'success')

      // редирект
      window.location.href = '/admin'
    }
  } catch (e) {
    emit('notify', 'Ошибка локального входа', 'error')
    console.error('Fake login failed', e)
  }
}
</script>

<style scoped>
.fake-btn {
  padding: 0.5rem 1rem;
  background: green;
  color: white;
  border-radius: 4px;
}
</style>
