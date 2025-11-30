<template>
  <header :class="headerClasses" class="z-50">
    <div class="max-w-6xl mx-auto h-full px-4 relative flex items-center">
      <!-- ЛОГО (слева) -->
      <router-link to="/" class="flex-shrink-0 cursor-pointer text-xl font-bold">
        SecretHub
      </router-link>

      <!-- МЕНЮ (по центру) -->
      <nav class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 pointer-events-auto">
        <ul class="flex gap-6 text-sm">
          <li>
            <router-link :to="{ name: 'home' }" class="transition-colors duration-200" :class="linkClasses">Главная</router-link>
          </li>
          <li>
            <router-link :to="{ name: 'admin' }" class="transition-colors duration-200" :class="linkClasses">Админка</router-link>
          </li>
        </ul>
      </nav>

      <!-- ПРАВЫЙ БЛОК (справа) -->
      <div class="ml-auto flex items-center gap-3">
        <button
            @click="toggleTheme"
            :aria-pressed="theme === 'dark'"
            class="px-2 py-1 rounded border transition-colors duration-200"
            :class="toggleBtnClasses"
        >
          {{ theme === 'dark' ? '🌙' : '☀️' }}
        </button>

        <router-link
            v-if="!authStore.user"
            :to="{ name: 'login' }"
            class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200"
        >
          Войти
        </router-link>

        <button
            v-else
            @click="handleLogout"
            class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 transition-colors duration-200"
        >
          Выйти
        </button>
      </div>
    </div>

    <!-- Нотификация -->
    <Notification :message="notif.message" :type="notif.type" />
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import Notification from '@/components/ServicePanel/Notification.vue'

const authStore = useAuthStore()

// тема страницы: 'light' или 'dark'
const theme = ref<'light' | 'dark'>(localStorage.getItem('theme') === 'dark' ? 'dark' : 'light')

onMounted(() => {
  applyTheme()
})

function toggleTheme() {
  theme.value = theme.value === 'light' ? 'dark' : 'light'
  localStorage.setItem('theme', theme.value)
  applyTheme()
}
function applyTheme() {
  if (theme.value === 'dark') document.documentElement.classList.add('dark')
  else document.documentElement.classList.remove('dark')
}

/* -------------- нотификация -------------- */
const notif = ref({ message: '', type: 'success' as 'success' | 'error' })
function showNotification(message: string, type: 'success' | 'error' = 'success') {
  // обновляем props компонента Notification — он сам покажет и скроет сообщение
  notif.value.message = message
  notif.value.type = type
}

/* -------------- следим за авторизацией -------------- */
/*
  Когда authStore.user меняется:
  - если появился пользователь (prev undefined/null -> user) -> вход успешен
  - если пропал (user -> null) -> выход (или деавторизация)
*/
let prevUser = authStore.user
watch(
    () => authStore.user,
    (cur) => {
      if (cur && !prevUser) {
        showNotification('Вход выполнен', 'success')
      } else if (!cur && prevUser) {
        showNotification('Вы вышли', 'success')
      }
      prevUser = cur
    }
)

/* -------------- Logout handler с нотификацией -------------- */
async function handleLogout() {
  try {
    const ok = await authStore.logout()
    if (ok) {
      showNotification('Вы вышли', 'success')
    } else {
      showNotification('Ошибка выхода', 'error')
    }
  } catch (e) {
    showNotification('Ошибка выхода', 'error')
  }
}

/* ------------------ Классы (контраст хедера и цвета ссылок) ------------------ */
const headerClasses = computed(() => {
  const base = 'fixed top-0 left-0 w-full h-14 z-50 shadow-sm transition-colors duration-200'
  return theme.value === 'dark' ? `${base} bg-white text-gray-900` : `${base} bg-gray-900 text-white`
})
const linkClasses = computed(() => (theme.value === 'dark' ? 'text-gray-900 hover:underline' : 'text-blue-500 hover:underline'))
const toggleBtnClasses = computed(() => (theme.value === 'dark' ? 'border-gray-300 bg-gray-50 text-gray-900' : 'border-gray-700 bg-gray-800 text-white'))
</script>

<style scoped> #telegram-login-container { margin-top: 1.5rem } .user-block { display: flex; align-items: center; gap: 1rem } button { padding: 0.4rem 0.8rem; border: 1px solid #3855da; background: #2542dc; color: #fff; border-radius: 4px } </style>