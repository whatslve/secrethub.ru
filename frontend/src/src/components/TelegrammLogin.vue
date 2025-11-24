<template>
  <div>
    <div id="telegram-login-container"></div>
    <p v-if="user">Привет, {{ user.name }}!</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js';
onMounted(() => {
  const container = document.getElementById('telegram-login-container');
  if (container) {
    const observer = new MutationObserver((mutations) => {
      console.log('Mutations:', mutations);
    });
    observer.observe(container, { childList: true });
  } else {
    console.error('Telegram container not found!');
  }
});
const user = ref(null);
const container = document.getElementById('telegram-login-container');
console.log('container:', container); // должно быть не null

onMounted(() => {
  // Определяем глобальную callback-функцию
  window.onTelegramAuth = async (telegramUser) => {
    console.log('Telegram raw user:', telegramUser);
    try {
      const res = await api.post('/auth/telegram', telegramUser);
      const token = res.data.token;
      setAuthToken(token);
      console.log('API response:', res.data);
      user.value = res.data.user;
    } catch (err) {
      console.error('Ошибка авторизации:', err.response?.data || err.message);
    }
  };

  const script = document.createElement('script');
  script.src = 'https://telegram.org/js/telegram-widget.js?22';
  script.async = true;
  script.setAttribute('data-telegram-login', 'secrethubclubbot');
  script.setAttribute('data-size', 'large');
  script.setAttribute('data-request-access', 'write');
  script.setAttribute('data-onauth', 'onTelegramAuth'); // вот этот атрибут

  document.getElementById('telegram-login-container').appendChild(script);
});
</script>
