<template>
  <div>
    <div id="telegram-login-container"></div>
    <p v-if="user">Привет, {{ user.first_name }}!</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js';

const botUsername = 'secrethubclubbot';
const authUrl = 'https://secrethub.club/api/auth/telegram';

const user = ref(null);

onMounted(() => {
  const script = document.createElement('script');
  script.src = 'https://telegram.org/js/telegram-widget.js?7';
  script.async = true;
  script.setAttribute('data-telegram-login', botUsername);
  script.setAttribute('data-size', 'large');
  script.setAttribute('data-auth-url', authUrl);
  script.setAttribute('data-request-access', 'write');
  script.setAttribute('data-userpic', 'false');
  document.getElementById('telegram-login-container').appendChild(script);

  // Telegram виджет вызывает эту функцию после авторизации
  window.TelegramLoginWidget = {
    onAuth: async function(telegramUser) {
      try {
        // Отправляем данные на backend, получаем Sanctum/Bearer токен
        const res = await api.post('/auth/telegram', telegramUser);
        const token = res.data.token;

        // Устанавливаем токен для всех axios-запросов
        setAuthToken(token);

        // Сохраняем данные пользователя для UI
        user.value = res.data.user;
        console.log('Авторизованы через Telegram:', user.value);
      } catch (err) {
        console.error('Ошибка авторизации:', err);
      }
    }
  };
});
</script>

<style scoped>
#telegram-login-container {
  margin-top: 2em;
}
</style>
