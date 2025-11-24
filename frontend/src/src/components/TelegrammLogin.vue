<template>
  <div>
    <div id="telegram-login-container"></div>
    <p v-if="user">Привет, {{ user.first_name || user.name }}!</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js';

const botUsername = 'secrethubclubbot'; // твой Telegram бот
const user = ref(null);

onMounted(() => {
  const script = document.createElement('script');
  script.src = 'https://telegram.org/js/telegram-widget.js?7';
  script.async = true;
  script.setAttribute('data-telegram-login', botUsername);
  script.setAttribute('data-size', 'large');
  // **НЕ УКАЗЫВАЕМ data-auth-url**, чтобы не было редиректа
  script.setAttribute('data-request-access', 'write');
  script.setAttribute('data-userpic', 'false');

  document.getElementById('telegram-login-container').appendChild(script);

  // Telegram вызовет эту функцию после авторизации
  window.TelegramLoginWidget = {
    onAuth: async (telegramUser) => {
      try {
        console.log('Данные от Telegram:', telegramUser);

        // Отправляем на бэк через Axios
        const res = await api.post('/auth/telegram', telegramUser);

        // Получаем токен
        const token = res.data.token;

        // Устанавливаем токен для всех последующих запросов
        setAuthToken(token);

        // Сохраняем пользователя
        user.value = res.data.user;

        console.log('Авторизация успешна, пользователь:', user.value);
      } catch (err) {
        console.error('Ошибка авторизации:', err.response?.data || err.message);
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
