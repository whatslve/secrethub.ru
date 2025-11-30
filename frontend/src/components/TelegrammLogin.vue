<template>
  <div>
    <div id="telegram-login-container" v-if="!authStore.user"></div>

    <div class="user-block" v-else>
      <p>Привет, {{ authStore.user?.name }}!</p>
      <button @click="handleLogout">Выйти</button>
    </div>

    <FakeLogin v-if="!authStore.user" @notify="showNotification" />


    <!-- Уведомление -->
    <Notification v-if="notificationMessage"
                  :message="notificationMessage"
                  :type="notificationType" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/plugins/axios'
import FakeLogin from '@/components/FakeLogin.vue'
import Notification from '@/components/ServicePanel/Notification.vue'

type NotificationType = 'success' | 'error'

const authStore = useAuthStore()
const notificationMessage = ref('')
const notificationType = ref<NotificationType>('success')

const TELEGRAM_BOT = 'secrethubclubbot'
const WIDGET_SCRIPT_URL = 'https://telegram.org/js/telegram-widget.js?22'
let widgetScriptEl: HTMLScriptElement | null = null

function clearWidget() {
  const container = document.getElementById('telegram-login-container')
  if (container) container.innerHTML = ''
  if (widgetScriptEl) {
    widgetScriptEl.remove()
    widgetScriptEl = null
  }
  try { (window as any).onTelegramAuth = undefined } catch {}
  try { (window as any).TelegramLoginWidget = undefined } catch {}
}

async function onTelegramAuth(telegramUser: any) {
  if (!telegramUser?.id) {
    showNotification('Неверный ответ от Telegram', 'error')
    return
  }

  try {
    const res = await api.post('/auth/telegram', telegramUser)
    if (res?.data?.token && res?.data?.user) {
      authStore.setUserData(res.data.token, res.data.user)
      showNotification(`Вход выполнен как ${res.data.user.name}`, 'success')
      return
    }
  } catch (e) {
    // fallthrough to fetch fallback
  }

  try {
    const r = await fetch('/auth/telegram', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(telegramUser),
      credentials: 'include'
    })
    const json = await r.json().catch(() => null)
    if (r.ok && json?.token && json?.user) {
      authStore.setUserData(json.token, json.user)
      showNotification(`Вход выполнен как ${json.user.name}`, 'success')
      return
    } else {
      showNotification('Вход не удался', 'error')
    }
  } catch (err) {
    showNotification('Ошибка при авторизации', 'error')
  }
}

function insertWidget() {
  const container = document.getElementById('telegram-login-container')
  if (!container) return
  clearWidget()

  ;(window as any).onTelegramAuth = onTelegramAuth
  ;(window as any).TelegramLoginWidget = { onAuth: onTelegramAuth }

  const script = document.createElement('script')
  script.src = WIDGET_SCRIPT_URL
  script.async = true
  script.setAttribute('data-tg-widget', '1')
  script.setAttribute('data-telegram-login', TELEGRAM_BOT)
  script.setAttribute('data-size', 'large')
  script.setAttribute('data-request-access', 'write')
  script.setAttribute('data-onauth', 'onTelegramAuth(user)')

  container.appendChild(script)
  widgetScriptEl = script
}

async function handleLogout() {
  try {
    const ok = await authStore.logout()
    if (ok) {
      clearWidget()
      insertWidget()
      showNotification('Вы вышли из аккаунта', 'success')
    } else {
      showNotification('Ошибка выхода', 'error')
    }
  } catch (e) {
    showNotification('Ошибка выхода', 'error')
  }
}

function showNotification(message: string, type: NotificationType = 'success') {
  notificationMessage.value = message
  notificationType.value = type
  // автоматически скрываем сообщение через 2 секунды
  setTimeout(() => {
    notificationMessage.value = ''
  }, 2000)
}

onMounted(() => { if (!authStore.user) insertWidget() })
onBeforeUnmount(() => clearWidget())
</script>

<style scoped>
#telegram-login-container { margin-top: 1.5rem }
.user-block { display: flex; align-items: center; gap: 1rem }
button { padding: 0.4rem 0.8rem; border: 1px solid #3855da; background: #2542dc; color: #fff; border-radius: 4px }
</style>
