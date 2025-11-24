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

const log = (...args) =>
    console.log('[TG_AUTH]', new Date().toISOString(), ...args)


// --------------------------------------------------
// ФУНКЦИЯ: вставляет виджет телеграма
// --------------------------------------------------
const renderTelegramWidget = () => {
  const container = document.getElementById('telegram-login-container')

  if (!container) {
    log('❌ Container not found!')
    return
  }

  // очищаем контейнер, если там что-то было
  container.innerHTML = ''

  // создаём скрипт телеграма
  const script = document.createElement('script')
  script.src = 'https://telegram.org/js/telegram-widget.js?7'
  script.async = true
  script.setAttribute('data-telegram-login', 'secrethubclubbot')
  script.setAttribute('data-size', 'large')
  script.setAttribute('data-userpic', 'false')
  script.setAttribute('data-onauth', 'onTelegramAuth(user)')
  script.setAttribute('data-request-access', 'write')

  container.appendChild(script)
  log('Widget appended')
}


// --------------------------------------------------
// ФУНКЦИЯ: выход из аккаунта
// --------------------------------------------------
const logout = () => {
  log('Logout clicked')

  // вычищаем хранилище
  localStorage.removeItem('user')
  localStorage.removeItem('token')

  // сбрасываем axios
  setAuthToken(null)

  // сбрасываем Vue-состояние
  user.value = null

  // снова вставляем кнопку Telegram
  renderTelegramWidget()
}


// --------------------------------------------------
// КОГДА ПРИШЕЛ PAYLOAD ОТ TELEGRAM
// --------------------------------------------------
window.onTelegramAuth = async (telegramUser) => {
  log('Received telegramUser:', telegramUser)

  if (!telegramUser || !telegramUser.id) {
    log('❌ Telegram payload invalid')
    return
  }

  try {
    const res = await api.post('/auth/telegram', telegramUser)
    log('Backend response:', res.data)

    const token = res.data.token
    const u = res.data.user

    setAuthToken(token)
    user.value = u

    localStorage.setItem('user', JSON.stringify(u))
    localStorage.setItem('token', token)

    log('Saved user and token')
  } catch (err) {
    log('❌ Backend error:', err.response?.data || err.message)
  }
}


// --------------------------------------------------
// КОМПОНЕНТ МОНТИРУЕТСЯ
// --------------------------------------------------
onMounted(() => {
  log('Component mounted')

  // Если есть сохранённый пользователь
  const storedUser = localStorage.getItem('user')
  const storedToken = localStorage.getItem('token')

  if (storedUser && storedToken) {
    user.value = JSON.parse(storedUser)
    setAuthToken(storedToken)
    log('Loaded saved user')
    return
  }

  // Иначе просто вставляем виджет
  renderTelegramWidget()
})
</script>
