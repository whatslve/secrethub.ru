<template>
  <div>
    <!-- виджет показываем только если нет user -->
    <div v-if="!user" id="telegram-login-container"></div>

    <!-- приветствие и кнопка выхода -->
    <div v-else class="user-block">
      <p>Привет, {{ user.name }}!</p>
      <button @click="logout" type="button">Выйти</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js';

// реактивный user (храним в localStorage под ключами 'user' и 'token' как у тебя было)
const user = ref(null);
let widgetScript = null;      // ссылка на DOM-элемент скрипта, чтобы удалить при unmount
const TELEGRAM_BOT = 'secrethubclubbot'; // замени на свой бот, если нужно

// Загрузка из localStorage при старте
onMounted(() => {
  try {
    const sUser = localStorage.getItem('user');
    const sToken = localStorage.getItem('token');
    if (sUser && sToken) {
      user.value = JSON.parse(sUser);
      setAuthToken(sToken);
      console.log('Loaded user from localStorage:', user.value);
      return; // авторизован — не инициализируем виджет
    }
  } catch (e) {
    console.warn('localStorage parsing error', e);
  }

  // Глобальная функция должна существовать до вставки скрипта
  window.TelegramLoginWidget = {
    onAuth: async (telegramUser) => {
      console.log('Telegram onAuth called (raw):', telegramUser);
      try {
        // Отправляем на бэк; убедись, что baseURL в api настроен верно (например /api)
        const res = await api.post('/auth/telegram', telegramUser);
        const token = res.data.token;
        const returnedUser = res.data.user;

        console.log('Backend response:', res.data);

        // Устанавливаем токен для axios и сохраняем user
        setAuthToken(token);
        user.value = returnedUser;

        // Сохраняем в localStorage
        localStorage.setItem('user', JSON.stringify(returnedUser));
        localStorage.setItem('token', token);

        // для чистоты URL (если есть параметры) — не обязательно но полезно:
        try { history.replaceState({}, '', window.location.pathname); } catch(e) {}

      } catch (err) {
        console.error('Ошибка авторизации (backend):', err.response?.data || err.message || err);
      }
    }
  };

  // Вставляем скрипт только если контейнер существует
  const container = document.getElementById('telegram-login-container');
  if (container) {
    // убираем старый скрипт, если случайно есть
    const existing = container.querySelector('script[data-telegram-login]');
    if (existing) existing.remove();

    widgetScript = document.createElement('script');
    widgetScript.src = 'https://telegram.org/js/telegram-widget.js?22';
    widgetScript.async = true;
    widgetScript.setAttribute('data-telegram-login', TELEGRAM_BOT);
    widgetScript.setAttribute('data-size', 'large');
    widgetScript.setAttribute('data-request-access', 'write');
    // callback вместо redirect:
    widgetScript.setAttribute('data-onauth', 'onTelegramAuth'); // fallback in case some browsers expect that
    // also make sure the widget will call window.TelegramLoginWidget.onAuth
    // append
    container.appendChild(widgetScript);
  } else {
    // На всякий случай — если контейнер ещё не в DOM (в редких случаях),
    // попробуем отложить вставку на следующий тик.
    nextTick(() => {
      const c = document.getElementById('telegram-login-container');
      if (c && !widgetScript) {
        widgetScript = document.createElement('script');
        widgetScript.src = 'https://telegram.org/js/telegram-widget.js?22';
        widgetScript.async = true;
        widgetScript.setAttribute('data-telegram-login', TELEGRAM_BOT);
        widgetScript.setAttribute('data-size', 'large');
        widgetScript.setAttribute('data-request-access', 'write');
        widgetScript.setAttribute('data-onauth', 'onTelegramAuth');
        c.appendChild(widgetScript);
      }
    });
  }
});

// watch — логируем любые изменения user, чтобы видеть события в реальном времени
watch(user, (n, o) => {
  console.log('user changed -> new:', n, 'old:', o);
});

// Удаление скрипта и глобальных переменных при размонтировании компонента
onBeforeUnmount(() => {
  try {
    // если вставляли, удаляем
    const container = document.getElementById('telegram-login-container');
    if (container && widgetScript) {
      widgetScript.remove();
      widgetScript = null;
    }
    // удаляем глобальные обработчики
    try { delete window.TelegramLoginWidget; } catch(e) { window.TelegramLoginWidget = undefined; }
  } catch (e) {
    // ignore
  }
});

// logout: чистим токен, localStorage и реактивный user.
// рекомендуем вызывать также backend /logout чтобы инвалидировать токен на сервере.
async function logout() {
  try {
    // попытка обратиться к бэку, чтобы уничтожить токен (если у тебя есть маршрут /api/logout)
    try {
      await api.post('/logout'); // optional: реализуй на сервере (см. пример ниже)
    } catch (e) {
      // если нет маршрута — просто продолжаем очищать клиент
      console.warn('Logout backend call failed or not implemented:', e?.response?.data || e.message || e);
    }

    // чистим localStorage и axios header
    localStorage.removeItem('user');
    localStorage.removeItem('token');
    setAuthToken(null);
    user.value = null;

    // Вставляем виджет заново (если нужно)
    const container = document.getElementById('telegram-login-container');
    if (container) {
      // очищаем контейнер и вставляем заново
      container.innerHTML = '';
      const script = document.createElement('script');
      script.src = 'https://telegram.org/js/telegram-widget.js?22';
      script.async = true;
      script.setAttribute('data-telegram-login', TELEGRAM_BOT);
      script.setAttribute('data-size', 'large');
      script.setAttribute('data-request-access', 'write');
      script.setAttribute('data-onauth', 'onTelegramAuth');
      container.appendChild(script);
      widgetScript = script;
    }

    console.log('Logged out, local session cleared');
  } catch (err) {
    console.error('Ошибка при logout:', err);
  }
}
</script>

<style scoped>
#telegram-login-container {
  margin-top: 1.5rem;
}
.user-block {
  display: flex;
  align-items: center;
  gap: 1rem;
}
</style>
