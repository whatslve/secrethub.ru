<template>
  <div>
    <div id="telegram-login-container"></div>
    <p v-if="currentUser">Привет, {{ currentUser.name }}!</p>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import api, { setAuthToken, currentUser } from '@/plugins/axios.js';

const botUsername = 'secrethubclubbot';
const authUrl = 'https://secrethub.club/api/auth/telegram';

onMounted(() => {
  const script = document.createElement('script');
  script.src = 'https://telegram.org/js/telegram-widget.js?7';
  script.async = true;
  script.setAttribute('data-telegram-login', botUsername);
  script.setAttribute('data-size', 'large');
  script.setAttribute('data-auth-url', '');
  script.setAttribute('data-request-access', 'write');
  script.setAttribute('data-userpic', 'false');
  document.getElementById('telegram-login-container').appendChild(script);

  window.TelegramLoginWidget = {
    onAuth: async function(telegramUser) {
      try {
        const res = await api.post('/auth/telegram', telegramUser);
        const token = res.data.token;
        setAuthToken(token);
        currentUser.value = res.data.user;
        console.log('Авторизованы через Telegram:', currentUser.value);
      } catch (err) {
        console.error('Ошибка авторизации:', err.response?.data || err);
      }
    }
  };
});
</script>
