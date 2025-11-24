<template>
  <div>
    <div id="telegram-login-container"></div>
    <p v-if="user">Привет, {{ user.name }}!</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js';

const user = ref(null);

onMounted(() => {
  const script = document.createElement('script');
  script.src = 'https://telegram.org/js/telegram-widget.js?7';
  script.async = true;
  script.setAttribute('data-telegram-login', 'secrethubclubbot');
  script.setAttribute('data-size', 'large');
  // НЕ ставим data-auth-url
  script.setAttribute('data-request-access', 'write');

  document.getElementById('telegram-login-container').appendChild(script);

  window.TelegramLoginWidget = {
    onAuth: async (telegramUser) => {
      try {
        const res = await api.post('/auth/telegram', telegramUser);
        const token = res.data.token;
        setAuthToken(token);
        user.value = res.data.user;
      } catch (err) {
        console.error('Ошибка авторизации:', err.response?.data || err.message);
      }
    }
  };
});
</script>
