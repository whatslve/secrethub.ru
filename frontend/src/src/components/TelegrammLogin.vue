<template>
  <div>
    <div v-if="!user" id="telegram-login-container"></div>

    <div v-else class="user-block">
      <p>Привет, {{ user.name }}!</p>
      <button @click="logout">Выйти</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api, { setAuthToken } from '@/plugins/axios.js'

const user = ref(null)
const log = (...args) => console.log('[TG_AUTH]', ...args)


// --------------------------------------------------
// — ГАРАНТИРОВАННО ПЕРЕРИСОВЫВАЕТ TELEGRAM-КНОПКУ —
// --------------------------------------------------
const renderTelegramWidget = () => {
  const container = document.getElementById('telegram-login-container')
  if (!container) return

  log('Rendering Telegram widget')

  // Полный сброс
  container.innerHTML = ''

  // ⚠️ Telegram кеширует одно и то же URL, поэтому добавляем "cache buster"
  const cacheBuster = Date.now()

  const script = document.createElement('script')
  script.src = `https://telegram.org/js/telegram-widget.js?7&t=${cacheBuster}`
  script.async = true

  script.setAttribute('data-telegram-login', 'secrethubclubbot')
  script.setAttribute('data-size', 'large')
  script.setAttribute('data-userpic', 'false')
  script.setAttribute('data-onauth', 'onTelegramAuth(user)')
  script.setAttribute('data-request-access', 'write')

  container.appendChild(script)

  log('Widget appended', script.src)
}


// --------------------------------------------------
// — LOGOUT —
// --------------------------------------------------
const logout = () => {
  log('Logout')

  // сброс vue
  user.value = null

  // сброс auth
  localStorage.removeItem('user')
  localStorage.removeItem('token')
  setAuthToken(null)

  // важный момент — убираем старый обработчик
  delete window.onTelegramAuth

  // ререндер кнопки
  setTimeout(() => {
    renderTelegramWidget()
  }, 50)
}


// --------------------------------------------------
// — ПРИШЁЛ PAYLOAD ОТ TELEGRAM —
// --------------------------------------------------
window.onTelegramAuth = async (telegramUser) => {
  log('TG payload:', telegramUser)

  try {
    const res = await api.post('/auth/telegram', telegramUser)

    const { token, user: userData } = res.data

    user.value = userData
    localStorage.setItem('user', JSON.stringify(userData))
    localStorage.setItem('token', token)
    setAuthToken(token)

    log('Auth success')

  } catch (err) {
    log('Backend error:', err.response?.data || err.message)
  }
}


// --------------------------------------------------
// — ON MOUNT —
// --------------------------------------------------
onMounted(() => {
  log('Component mounted')

  const savedUser = localStorage.getItem('user')
  const savedToken = localStorage.getItem('token')

  if (savedUser && savedToken) {
    user.value = JSON.parse(savedUser)
    setAuthToken(savedToken)
    return
  }

  renderTelegramWidget()
})
</script>
