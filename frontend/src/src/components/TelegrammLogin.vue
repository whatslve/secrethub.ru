<template>
  <div>
    <div v-if="!user" id="telegram-login-container"></div>
    <div v-else class="user-block">
      <p>Привет, {{ user.name }}!</p>
      <button @click="logout">Выйти</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js';

const TAG = '[TG_AUTH]';
const log = (...args) => console.log(TAG, new Date().toISOString(), ...args);

const TELEGRAM_BOT = 'secrethubclubbot'; // ← замени на имя своего бота
const POLL_INTERVAL_MS = 500;
const POLL_MAX_ATTEMPTS = 12;

const user = ref(null);
let pollTimer = null;

// Удаление сессии на клиенте
function clearSessionClientSide() {
  localStorage.removeItem('user');
  localStorage.removeItem('token');
  setAuthToken(null);
  user.value = null;
  log('Cleared client session');
}

// Удаление старого скрипта Telegram виджета
function removeWidgetScript() {
  const container = document.getElementById('telegram-login-container');
  if (container) {
    const existing = container.querySelector('script[data-tg-widget]');
    if (existing) {
      existing.remove();
      log('Removed old widget script');
    }
    container.innerHTML = '';
  }
}
fetch('/api/auth/telegram', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({test: 'ping'})
}).then(async r => {
  console.log('[TG_PING] status', r.status);
  const t = await r.text();
  try { console.log('[TG_PING] body', JSON.parse(t)); } catch(e) { console.log('[TG_PING] body text', t); }
}).catch(e => console.error('[TG_PING] fetch error', e));

// Создание и вставка скрипта виджета + регистрация onAuth
function createAndInsertWidget() {
  const container = document.getElementById('telegram-login-container');
  if (!container) {
    log('Container for Telegram widget not found');
    return false;
  }

  container.innerHTML = '';

  // Глобальная callback-функция для Telegram Login Widget
  window.TelegramLoginWidget = {
    onAuth: async (telegramUser) => {
      log('onAuth called with telegramUser:', telegramUser);

      try {
        log('Sending data to backend /auth/telegram …');
        const res = await api.post('/auth/telegram', telegramUser);

        log('Backend response status:', res.status);
        log('Backend response data:', res.data);

        const token = res.data.token;
        const returnedUser = res.data.user;

        if (!token || !returnedUser) {
          log('Invalid backend response: token or user missing', res.data);
          return;
        }

        setAuthToken(token);
        localStorage.setItem('user', JSON.stringify(returnedUser));
        localStorage.setItem('token', token);
        user.value = returnedUser;

        log('User set after login:', returnedUser);
      } catch (err) {
        log('Error in onAuth:', err?.response?.status, err?.response?.data || err.message || err);
      }
    }
  };

  // Альтернативная глобальная функция (для data-onauth)
  if (typeof window.onTelegramAuth !== 'function') {
    window.onTelegramAuth = (u) => window.TelegramLoginWidget.onAuth(u);
    log('Defined global alias onTelegramAuth');
  }

  const script = document.createElement('script');
  script.src = 'https://telegram.org/js/telegram-widget.js?22';
  script.async = true;
  script.setAttribute('data-telegram-login', TELEGRAM_BOT);
  script.setAttribute('data-size', 'large');
  script.setAttribute('data-request-access', 'write');
  script.setAttribute('data-tg-widget', '1');
  // Добавляем data-onauth, чтобы убедиться, что callback сработает
  script.setAttribute('data-onauth', 'onTelegramAuth');

  container.appendChild(script);
  log('Appended Telegram widget script');

  return true;
}

// Попытка вставить виджет с ретраями, если контейнера нет сразу
function ensureWidgetInsertedWithRetry() {
  removeWidgetScript();
  let attempts = 0;
  pollTimer = setInterval(() => {
    attempts++;
    const ok = createAndInsertWidget();
    if (ok) {
      log('Widget successfully inserted after', attempts, 'attempts');
      clearInterval(pollTimer);
      pollTimer = null;
    } else if (attempts >= POLL_MAX_ATTEMPTS) {
      log('Failed to insert widget after max attempts:', POLL_MAX_ATTEMPTS);
      clearInterval(pollTimer);
      pollTimer = null;
    } else {
      log('Retrying widget insert, attempt', attempts);
    }
  }, POLL_INTERVAL_MS);
}

// Logout: сброс сессии + вставка виджета заново
async function logout() {
  log('Logout initiated');
  try {
    await api.post('/logout');
    log('Backend logout OK');
  } catch (e) {
    log('Backend logout failed or not implemented:', e?.response?.status, e?.response?.data || e.message);
  }

  clearSessionClientSide();
  removeWidgetScript();
  ensureWidgetInsertedWithRetry();
}

// Очистка при размонтировании компонента
onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer);
  removeWidgetScript();
  delete window.TelegramLoginWidget;
  delete window.onTelegramAuth;
  log('Component unmounted, cleaned up');
});

// Инициализация при монтировании
onMounted(() => {
  log('Component mounted, init start');
  const sUser = localStorage.getItem('user');
  const sToken = localStorage.getItem('token');

  if (sUser && sToken) {
    try {
      user.value = JSON.parse(sUser);
      setAuthToken(sToken);
      log('Restored user from storage:', user.value);
      return; // пользователь уже есть, не вставляем виджет
    } catch (e) {
      log('Error parsing stored user/token:', e);
      clearSessionClientSide();
    }
  }

  // Если нет сохранённого user — вставляем виджет
  ensureWidgetInsertedWithRetry();
});
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
button {
  padding: 0.4rem 0.8rem;
  border: 1px solid #ccc;
  background: #eee;
  border-radius: 4px;
}
</style>
