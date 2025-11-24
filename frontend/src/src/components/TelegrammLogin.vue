<!-- TelegramLogin.vue -->
<script setup>
import { onMounted } from 'vue';

const botUsername = 'secrethubclubbot';
const authUrl = 'https://secrethub.club/api/auth/telegram';

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
});
</script>

<template>
  <div id="telegram-login-container"></div>
</template>

<style scoped>
#telegram-login-container {
  margin-top: 2em;
}
</style>
