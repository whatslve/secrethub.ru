<template>
  <div>
    <!-- Контейнер виджета показываем только если нет авторизованного user -->
    <div v-if="!user" id="telegram-login-container"></div>

    <!-- Приветствие + выход -->
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

const TELEGRAM_BOT = 'secrethubclubbot'; // <- поменяй на свой, если нужно
const POLL_INTERVAL_MS = 500;
const POLL_MAX_ATTEMPTS = 12; // ~6 секунд

const user = ref(null);
let widgetScript = null;
let pollTimer = null;

// Устанавливаем токен/удаляем его в axios через setAuthToken (предполагаем, что плагин реализует это)
function clearSessionClientSide() {
  localStorage.removeItem('user');
  localStorage.removeItem('token');
  setAuthToken(null);
  user.value = null;
  log('Cleared local session');
}

// Удаляем вставленный скрипт (если есть)
function removeWidgetScript() {
  try {
    const container = document.getElementById('telegram-login-container');
    if (container) {
      const existing = container.querySelector('script[data-tg-widget]');
      if (existing) {
        existing.remove();
        log('Removed existing widget script element');
      }
      // очистим контейнер излишков
      container.innerHTML = '';
    }
    widgetScript = null;
  } catch (e) {
    log('Error removing widget script:', e);
  }
}

// Основная функция вставки виджета
function createAndInsertWidget() {
  try {
    const container = document.getElementById('telegram-login-container');
    if (!container) {
      log('createAndInsertWidget: container not found');
      return false;
    }

    // Удалим старое содержимое, если есть
    container.innerHTML = '';

    // Регистрируем глобальную callback-функцию ДО вставки скрипта
    window.TelegramLoginWidget = {
      onAuth: async (telegramUser) => {
        log('onAuth called (raw):', telegramUser);
        try {
          log('Posting to backend /auth/telegram ...');
          const res = await api.post('/auth/telegram', telegramUser);
          // Логируем full response (status + data). Не отправляй сюда приватные токены в чат.
          log('Backend response status:', res.status);
          log('Backend response data:', res.data);

          const token = res.data.token;
          const returnedUser = res.data.user;

          if (!token || !returnedUser) {
            log('Backend did not return token/user. Response:', res.data);
            return;
          }

          // Устанавливаем токен для axios
          setAuthToken(token);
          // Сохраняем user и token
          localStorage.setItem('user', JSON.stringify(returnedUser));
          localStorage.setItem('token', token);
          user.value = returnedUser;

          log('Login success. User set locally:', returnedUser);
        } catch (err) {
          log('Error during backend auth POST:', err?.response?.status, err?.response?.data || err.message || err);
        }
      }
    };

    // для совместимости: некоторые интеграции используют data-onauth="onTelegramAuth"
    // создаём глобальную функцию-алиас (без перезаписи, если уже есть)
    if (typeof window.onTelegramAuth !== 'function') {
      window.onTelegramAuth = (u) => window.TelegramLoginWidget.onAuth(u);
      log('Defined global alias window.onTelegramAuth');
    }

    // Создаём скрипт виджета
    const script = document.createElement('script');
    script.src = 'https://telegram.org/js/telegram-widget.js?22';
    script.async = true;
    script.setAttribute('data-telegram-login', TELEGRAM_BOT);
    script.setAttribute('data-size', 'large');
    script.setAttribute('data-request-access', 'write');
    // НЕ ставим data-auth-url чтобы не было redirect
    // Маркер чтобы потом легко найти / удалить
    script.setAttribute('data-tg-widget', '1');

    container.appendChild(script);
    widgetScript = script;

    log('Widget script appended to container');
    return true;
  } catch (e) {
    log('createAndInsertWidget error:', e);
    return false;
  }
}

// Будем пытаться вставить виджет с небольшим polling-ом, если контейнер ещё не существует
function ensureWidgetInsertedWithRetry() {
  removeWidgetScript(); // очистка на всякий случай
  let attempts = 0;
  if (pollTimer) clearInterval(pollTimer);
  pollTimer = setInterval(() => {
    attempts++;
    const ok = createAndInsertWidget();
    if (ok) {
      clearInterval(pollTimer);
      pollTimer = null;
      log('Widget inserted after', attempts, 'attempt(s)');
    } else if (attempts >= POLL_MAX_ATTEMPTS) {
      clearInterval(pollTimer);
      pollTimer = null;
      log('Widget insert failed after max attempts:', POLL_MAX_ATTEMPTS);
    } else {
      log('Widget insert attempt', attempts, 'failed — retrying...');
    }
  }, POLL_INTERVAL_MS);
}

// Logout: вызывается кнопкой. Чистим и заново вставляем виджет
async function logout() {
  log('Logout triggered');
  // Попробуем вызвать backend logout (опционально) — если защищено Sanctum, будет 401 если нет токена
  try {
    await api.post('/logout');
    log('Backend logout OK');
  } catch (e) {
    log('Backend logout failed or not implemented:', e?.response?.status, e?.response?.data || e.message || e);
  }

  // Клиентская очистка
  clearSessionClientSide();

  // Удаляем старый скрипт и заново ставим виджет
  removeWidgetScript();
  ensureWidgetInsertedWithRetry();
}

// При размонтировании — чистим таймеры и глобальные объекты
onBeforeUnmount(() => {
  try {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
    removeWidgetScript();
    try { delete window.TelegramLoginWidget; } catch (e) { window.TelegramLoginWidget = undefined; }
    try { delete window.onTelegramAuth; } catch (e) { window.onTelegramAuth = undefined; }
    log('Component unmounted, cleaned up');
  } catch (e) {
    log('onBeforeUnmount cleanup error:', e);
  }
});

// При монтировании — восстанавливаем сессию из localStorage или вставляем виджет
onMounted(() => {
  log('Component mounted — starting init');

  try {
    const storedUser = localStorage.getItem('user');
    const storedToken = localStorage.getItem('token');

    if (storedUser && storedToken) {
      try {
        user.value = JSON.parse(storedUser);
        setAuthToken(storedToken);
        log('Restored user from localStorage:', user.value);
        // Если восстановился — не вставляем виджет (кнопка скрыта)
        return;
      } catch (e) {
        log('Error parsing stored user/token, clearing and continuing:', e);
        clearSessionClientSide();
      }
    }
  } catch (e) {
    log('Error reading localStorage:', e);
  }

  // Если пользователь не найден — вставляем виджет (с retry)
  ensureWidgetInsertedWithRetry();
});
</script>

<style scoped>
#telegram-login-container {
  margin-top: 1.5rem;
}
.user-block {
  display: flex;
  gap: 1rem;
  align-items: center;
}
button {
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  background: #efefef;
  border: 1px solid #ccc;
}
</style>
