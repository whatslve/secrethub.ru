<template>
  <div>
    <!-- Контейнер виджета появляется только если пользователь не авторизован -->
    <div v-if="!user" id="telegram-login-container"></div>

    <!-- Отображение приветствия -->
    <div v-else>
      <p>Привет, {{ user.name }}!</p>
      <button @click="logout">Выйти</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js';

const user = ref(null);

// Вспомогательная функция для очистки сессии
function logout() {
  localStorage.removeItem('user');
  localStorage.removeItem('token');
  setAuthToken(null);
  user.value = null;
  console.log('Сессия сброшена');
  // Перезагрузим виджет
  insertTelegramWidget();
}

// Функция вставки виджета
function insertTelegramWidget() {
  const container = document.getElementById('telegram-login-container');
  if (!container) return;

  // Очищаем старый виджет, если есть
  container.innerHTML = '';

  const script = document.createElement('script');
  script.src = 'https://telegram.org/js/telegram-widget.js?7';
  script.async = true;
  script.setAttribute('data-telegram-login', 'secrethubclubbot'); // замените на свой бот
  script.setAttribute('data-size', 'large');
  script.setAttribute('data-request-access', 'write');
  container.appendChild(script);

  // Подключаем функцию обратного вызова
  window.TelegramLoginWidget = {
    onAuth: async (telegramUser) => {
      console.log('Данные от Telegram виджета:', telegramUser); // логируем входящие данные
      try {
        const res = await api.post('/auth/telegram', telegramUser);
        const token = res.data.token;
        const userData = res.data.user;

        // Логи токена и пользователя
        console.log('Токен:', token);
        console.log('Пользователь:', userData);

        // Устанавливаем токен для axios
        setAuthToken(token);

        // Сохраняем в локальное хранилище
        localStorage.setItem('user', JSON.stringify(userData));
        localStorage.setItem('token', token);

        user.value = userData;
      } catch (err) {
        console.error('Ошибка авторизации:', err.response?.data || err.message);
      }
    }
  };
}

// При монтировании проверяем, есть ли сохранённый пользователь
onMounted(() => {
  const storedUser = localStorage.getItem('user');
  const storedToken = localStorage.getItem('token');
  if (storedUser && storedToken) {
    user.value = JSON.parse(storedUser);
    setAuthToken(storedToken);
    console.log('Восстановлен пользователь из localStorage:', user.value);
  } else {
    // Если нет сохранённого пользователя, вставляем виджет
    insertTelegramWidget();
  }
});
</script>

<style scoped>
#telegram-login-container {
  margin-top: 2em;
}
button {
  margin-top: 1em;
}
</style>
