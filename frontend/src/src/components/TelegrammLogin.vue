<template>
  <div>
    <div id="telegram-login-container" v-show="!user"></div>
    <div class="user-block" v-show="user">
      <p>Привет, {{ user.name }}!</p>
      <button @click="logout">Выйти</button>
    </div>
  </div>
</template>

<script setup>
/*
  Полностью рабочий компонент Telegram Login Widget (callback-mode)
  - Очень подробные логи (метка [TG_AUTH])
  - Объявляем window.onTelegramAuth ДО вставки скрипта (ключевой момент)
  - Пытаемся отправить на бэк через axios, при ошибке делаем fetch-fallback
  - Сохраняем token/user в localStorage и устанавливаем setAuthToken(token)
  - Предусмотрен logout
*/

import { ref, onMounted, onBeforeUnmount } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js'; // предполагаем, что плагин есть

const TAG = '[TG_AUTH]';
const log = (...args) => console.log(TAG, new Date().toISOString(), ...args);

const TELEGRAM_BOT = 'secrethubclubbot'; // <- поменяй на свой бот
const WIDGET_SCRIPT_URL = 'https://telegram.org/js/telegram-widget.js?22';

const user = ref(null);
let widgetScriptEl = null;

// --- Утилиты ---
function safeJson(obj) {
  try { return JSON.parse(JSON.stringify(obj)); } catch { return obj; }
}

function removeExistingWidget() {
  try {
    const container = document.getElementById('telegram-login-container');
    if (!container) return;
    // удаляем старые скрипты маркеры
    const existing = container.querySelectorAll('script[data-tg-widget]');
    existing.forEach(el => el.remove());
    // очищаем контейнер (оставляем пустым)
    container.innerHTML = '';
    // clean global callbacks (don't throw)
    try { delete window.onTelegramAuth } catch(e) { window.onTelegramAuth = undefined; }
    try { delete window.TelegramLoginWidget } catch(e) { window.TelegramLoginWidget = undefined; }
    widgetScriptEl = null;
    log('Removed existing widget and callbacks');
  } catch (e) {
    log('removeExistingWidget error:', e);
  }
}

// --- core: callback (объявим до вставки скрипта) ---
async function onTelegramAuthCallback(telegramUser) {
  log('onTelegramAuth callback fired, payload:', safeJson(telegramUser));

  if (!telegramUser || !telegramUser.id) {
    log('Invalid telegram payload — missing id. Payload:', safeJson(telegramUser));
    return;
  }

  // Preview to logs (redact hash)
  const preview = safeJson(telegramUser);
  if (preview.hash) preview.hash = '[REDACTED]';
  log('Prepared payload preview:', preview);

  // First try axios (relative path — ваш axios может иметь baseURL /api)
  try {
    log('Attempting axios POST to /auth/telegram');
    const res = await api.post('/auth/telegram', telegramUser);
    log('Axios POST success — status:', res.status, 'data:', res.data);

    if (res.data?.token && res.data?.user) {
      setAuthToken(res.data.token);
      localStorage.setItem('token', res.data.token);
      localStorage.setItem('user', JSON.stringify(res.data.user));
      user.value = res.data.user;
      log('Login success (axios). User saved:', res.data.user);
      return;
    } else {
      log('Axios returned without token/user:', res.data);
    }
  } catch (axErr) {
    log('Axios POST failed:', axErr?.response?.status || '(no status)', axErr?.response?.data || axErr.message || axErr);
  }

  // Fallback: try fetch to several likely URLs
  const fallbackUrls = [
    '/auth/telegram',
    '/api/auth/telegram',
    // если в api.defaults.baseURL установлен например '/api'
    (api?.defaults?.baseURL ? api.defaults.baseURL.replace(/\/$/, '') + '/auth/telegram' : null),
    (api?.defaults?.baseURL ? api.defaults.baseURL.replace(/\/$/, '') + '/api/auth/telegram' : null)
  ].filter(Boolean);

  for (const url of fallbackUrls) {
    try {
      log('Fetch fallback trying URL:', url);
      const fRes = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(telegramUser),
        credentials: 'include' // если Sanctum по cookie
      });
      const text = await fRes.text();
      let jsonBody = null;
      try { jsonBody = text ? JSON.parse(text) : null; } catch(e) { jsonBody = text; }
      log('Fetch result', url, 'status:', fRes.status, 'body:', jsonBody);

      if (fRes.ok && jsonBody?.token && jsonBody?.user) {
        log('Fetch returned token+user — saving session');
        setAuthToken(jsonBody.token);
        localStorage.setItem('token', jsonBody.token);
        localStorage.setItem('user', JSON.stringify(jsonBody.user));
        user.value = jsonBody.user;
        return;
      } else {
        log('Fetch did not return token/user for', url, 'body:', jsonBody);
      }
    } catch (fe) {
      log('Fetch attempt failed for', url, fe?.message || fe);
    }
  }

  log('All attempts to send payload to backend finished — no successful auth');
}

// Экспортируем на window именно имя, которого требует виджет
function registerGlobalCallback() {
  // обязательно устанавливать ДО вставки скрипта виджета
  try {
    window.onTelegramAuth = (u) => onTelegramAuthCallback(u);
    // для совместимости: Telegram иногда использует объект TelegramLoginWidget
    window.TelegramLoginWidget = { onAuth: (u) => onTelegramAuthCallback(u) };
    log('Registered global callbacks: window.onTelegramAuth and window.TelegramLoginWidget.onAuth');
  } catch (e) {
    log('registerGlobalCallback error:', e);
  }
}

// --- вставка скрипта ---
function insertWidgetScript() {
  try {
    const container = document.getElementById('telegram-login-container');
    if (!container) {
      log('insertWidgetScript: container not found');
      return false;
    }

    // очистка перед вставкой
    removeExistingWidget();

    // регистрируем глобальные функции *перед* загрузкой скрипта
    registerGlobalCallback();

    const script = document.createElement('script');
    script.src = WIDGET_SCRIPT_URL;
    script.async = true;
    script.setAttribute('data-tg-widget', '1'); // маркер
    script.setAttribute('data-telegram-login', TELEGRAM_BOT);
    script.setAttribute('data-size', 'large');
    script.setAttribute('data-request-access', 'write');
    // callback-mode — обязательно onauth, аргумент must be (user)
    script.setAttribute('data-onauth', 'onTelegramAuth(user)');
    // optional: hide userphoto if wanted
    // script.setAttribute('data-userpic', 'false');

    script.onload = () => {
      log('Widget script loaded (onload). src:', script.src);
      // через таймаут проверим, создал ли виджет iframe
      setTimeout(() => {
        try {
          const iframe = container.querySelector('iframe');
          log('Widget iframe present?', !!iframe, 'iframe:', iframe);
        } catch (e) {
          log('Iframe check error:', e);
        }
      }, 300);
    };

    script.onerror = (e) => {
      log('Widget script failed to load (onerror):', e);
    };

    container.appendChild(script);
    widgetScriptEl = script;
    log('Appended Telegram widget script:', script.src);
    return true;
  } catch (e) {
    log('insertWidgetScript error:', e);
    return false;
  }
}

// --- Logout ---
async function logout() {
  log('Logout');
  try {
    // optional backend logout
    await api.post('/logout');
    log('Backend logout request sent (if route exists)');
  } catch (e) {
    log('Backend logout failed or not implemented:', e?.response?.status, e?.response?.data || e?.message || e);
  }

  // client-side clear
  try {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setAuthToken(null);
    user.value = null;
    removeExistingWidget();
    // reinject widget so user can login again
    insertWidgetScript();
    log('Client session cleared and widget re-rendered');
  } catch (e) {
    log('logout cleanup error:', e);
  }
}

// --- helper для ручного теста из консоли ---
window.__tg_test_invoke = function fakeAuth() {
  const sample = {
    id: 100155858,
    first_name: 'Vladislav',
    last_name: 'Pro',
    username: 'Londonvievv',
    photo_url: 'https://t.me/i/userpic/320/..jpg',
    auth_date: String(Math.floor(Date.now() / 1000)),
    hash: 'TEST_HASH'
  };
  log('Manual test invoke (window.__tg_test_invoke) sending sample:', sample);
  // вызовем глобальную handler'у
  try {
    if (window.onTelegramAuth) window.onTelegramAuth(sample);
    else if (window.TelegramLoginWidget && window.TelegramLoginWidget.onAuth) window.TelegramLoginWidget.onAuth(sample);
    else log('No global handler registered');
  } catch (e) {
    log('Manual test invoke error:', e);
  }
};

// --- lifecycle ---
onBeforeUnmount(() => {
  try {
    removeExistingWidget();
    if (widgetScriptEl) widgetScriptEl.remove();
    // clean global
    try { delete window.onTelegramAuth; } catch(e) { window.onTelegramAuth = undefined; }
    try { delete window.TelegramLoginWidget; } catch(e) { window.TelegramLoginWidget = undefined; }
    log('Component unmounted - cleaned');
  } catch (e) {
    log('onBeforeUnmount error:', e);
  }
});

onMounted(() => {
  log('Component mounted');

  // try restore session
  try {
    const sToken = localStorage.getItem('token');
    const sUser = localStorage.getItem('user');
    if (sToken && sUser) {
      try {
        user.value = JSON.parse(sUser);
        setAuthToken(sToken);
        log('Restored user from localStorage:', user.value);
        return; // если восстановили — не вставляем виджет
      } catch (e) {
        log('Error parsing stored user/token, clearing:', e);
        localStorage.removeItem('token'); localStorage.removeItem('user');
      }
    }
  } catch (e) {
    log('Error reading localStorage:', e);
  }

  // вставляем виджет (callback-mode)
  const ok = insertWidgetScript();
  if (!ok) log('insertWidgetScript returned false');
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
