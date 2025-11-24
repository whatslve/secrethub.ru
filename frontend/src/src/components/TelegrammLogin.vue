<template>
  <div>
    <div v-if="!user" id="telegram-login-container"></div>
    <p v-else>Привет, {{ user.name }}!</p>
    <p>Привет, {{ user }}!</p>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js';
const user = ref(null);

// Попробуем достать пользователя из localStorage при монтировании
onMounted(() => {
  const storedUser = localStorage.getItem('user');
  const storedToken = localStorage.getItem('token');
  if (storedUser && storedToken) {
    user.value = JSON.parse(storedUser);
    setAuthToken(storedToken);
  }

  // Наблюдаем за DOM, чтобы вставить Telegram виджет
  const observer = new MutationObserver((mutations, obs) => {
    const container = document.getElementById('telegram-login-container');
    if (container && !user.value) {
      const script = document.createElement('script');
      script.src = 'https://telegram.org/js/telegram-widget.js?7';
      script.async = true;
      script.setAttribute('data-telegram-login', 'secrethubclubbot');
      script.setAttribute('data-size', 'large');
      script.setAttribute('data-request-access', 'write');

      container.appendChild(script);

      if (storedUser && storedToken) {
        user.value = JSON.parse(storedUser);
        setAuthToken(storedToken);
        console.log('Loaded user from localStorage:', user.value);
      }

// После авторизации через Telegram
      window.TelegramLoginWidget = {
        onAuth: async (telegramUser) => {
          try {
            const res = await api.post('/auth/telegram', telegramUser);
            const token = res.data.token;

            setAuthToken(token);
            user.value = res.data.user;
            console.log('User after Telegram login:', user.value);

            localStorage.setItem('user', JSON.stringify(res.data.user));
            localStorage.setItem('token', token);
          } catch (err) {
            console.error('Ошибка авторизации:', err.response?.data || err.message);
          }
        }
      };

      obs.disconnect(); // больше не нужно наблюдать
    }
  });

  observer.observe(document.body, { childList: true, subtree: true });
});
</script>

<style scoped>
#telegram-login-container {
  margin-top: 2em;
}
</style>
