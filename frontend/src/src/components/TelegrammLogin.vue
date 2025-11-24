<script setup>
import { ref, onMounted } from 'vue'
import api, { setAuthToken } from '@/plugins/axios.js'

const user = ref(null)

const log = (...args) =>
    console.log('[TG_AUTH]', new Date().toISOString(), ...args)

onMounted(() => {
  log('Component mounted — starting init')

  const storedUser = localStorage.getItem('user')
  const storedToken = localStorage.getItem('token')

  if (storedUser && storedToken) {
    user.value = JSON.parse(storedUser)
    setAuthToken(storedToken)
    log('Loaded user from localStorage:', user.value)
  }

  const container = document.getElementById('telegram-login-container')

  if (!container) {
    log('❌ Container not found!')
    return
  }

  // глобальный хэндлер
  window.onTelegramAuth = async (telegramUser) => {
    log('Received telegramUser:', telegramUser)

    if (!telegramUser || !telegramUser.id) {
      log('❌ Telegram payload invalid or empty')
      return
    }

    try {
      log('Sending payload to backend:', telegramUser)

      const res = await api.post('/auth/telegram', telegramUser)

      log('Backend response:', res.data)

      const token = res.data.token
      setAuthToken(token)
      user.value = res.data.user

      localStorage.setItem('user', JSON.stringify(user.value))
      localStorage.setItem('token', token)

      log('Saved user and token to localStorage')
    } catch (err) {
      log('❌ Backend error:', err.response?.data || err.message)
    }
  }

  log('Defined window.onTelegramAuth')

  // вставляем виджет
  const script = document.createElement('script')
  script.src = 'https://telegram.org/js/telegram-widget.js?7'
  script.async = true
  script.setAttribute('data-telegram-login', 'secrethubclubbot')
  script.setAttribute('data-size', 'large')
  script.setAttribute('data-userpic', 'false')
  script.setAttribute('data-auth-url', 'javascript:onTelegramAuth(user)')
  script.setAttribute('data-request-access', 'write')

  container.appendChild(script)
  log('Widget appended')
})
</script>
