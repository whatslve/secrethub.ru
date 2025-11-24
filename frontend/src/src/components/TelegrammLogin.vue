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
function renderWidget() {
  log('Rendering Telegram widget');

  const container = document.getElementById('telegram-login-container');
  container.innerHTML = '';

  // IMPORTANT — колбэк ДОЛЖЕН быть в window ДО вставки скрипта
  window.onTelegramAuth = async (telegramUser) => {
    log('TG CALLBACK', telegramUser);

    try {
      const res = await api.post('/auth/telegram', telegramUser);
      log('Backend OK:', res.data);

      const { token, user: returned } = res.data;
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(returned));

      setAuthToken(token);
      user.value = returned;
    } catch (e) {
      log('Backend error:', e?.response?.data || e);
    }
  };

  const s = document.createElement('script');
  s.src = 'https://telegram.org/js/telegram-widget.js?7';
  s.async = true;
  s.setAttribute('data-telegram-login', TELEGRAM_BOT);
  s.setAttribute('data-size', 'large');
  s.setAttribute('data-request-access', 'write');
  s.setAttribute('data-onauth', 'onTelegramAuth(user)');

  container.appendChild(s);
  log('Widget appended', s.src);
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
