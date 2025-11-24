<template>
  <div>
    <!--
      Контейнер для Telegram-виджета всегда присутствует в DOM.
      Управляем видимостью через v-show, чтобы его можно было
      безопасно перерисовывать (вставлять/удалять script) без ошибок.
    -->
    <div id="telegram-login-container" v-show="!user"></div>

    <!-- Блок авторизованного пользователя. v-show оставляет элемент в DOM -->
    <div class="user-block" v-show="user">
      <!-- Безопасный вывод имени: шаблон рендерит только, когда user !== null -->
      <p>Привет, {{ user?.name }}!</p>
      <button @click="logout">Выйти</button>
    </div>
  </div>
</template>

<script setup>
/*
  Компонент: Telegram Login Widget (callback-mode)
  -----------------------------------------------
  Ключевые идеи (в порядке важности):
  1) Глобальные callback'и (window.onTelegramAuth и window.TelegramLoginWidget.onAuth)
     регистрируются *до* вставки внешнего скрипта виджета. Это обязательно: виджет будет
     пытаться вызвать именно эти имена.
  2) Перед вставкой нового виджета мы очищаем контейнер и удаляем старые callback'и,
     чтобы не оставлять "подвешенные" обработчики.
  3) Отправка данных на бэк — сначала пробуем axios (api), затем fallback через fetch
     на несколько потенциальных URL'ов (удобно при разных baseURL конфигурациях).
  4) При логауте делаем server-side попытку invalidate токена (если есть роут /logout),
     но главное — очищаем client-side: localStorage, axios header, user state.
  5) Для корректного ререндеринга виджета используем маркер script[data-tg-widget] и
     удаляем предыдущие скрипты и iframe из контейнера.
*/

import { ref, onMounted, onBeforeUnmount } from 'vue';
import api, { setAuthToken } from '@/plugins/axios.js';

// Настройки виджета: бот и URL скрипта.
// Можно менять TELEGRAM_BOT на имя своего бота.
const TELEGRAM_BOT = 'secrethubclubbot';
const WIDGET_SCRIPT_URL = 'https://telegram.org/js/telegram-widget.js?22';

// Состояние пользователя (reactive)
const user = ref(null);

// Ссылка на последний вставленный <script> виджета (если нужен доступ)
let widgetScriptEl = null;

/* ----------------------
   Утилиты
   ---------------------- */

/**
 * Возвращает глубокую копию объекта, безопасную для логики (без циклов и функций).
 * Используется при подготовке данных отправки и preview, чтобы избежать побочных эффектов.
 */
function safeCopy(obj) {
  try {
    return JSON.parse(JSON.stringify(obj));
  } catch {
    return obj;
  }
}

/* ----------------------
   Удаление старого виджета и глобальных callback'ов
   ---------------------- */

/**
 * Удаляет всё, что виджет мог создать в контейнере:
 * - скрипты с маркером data-tg-widget
 * - iframe (если остался)
 * - очищает контейнер
 * - удаляет глобальные обработчики, чтобы избежать дублирования
 */
function removeExistingWidget() {
  const container = document.getElementById('telegram-login-container');
  if (!container) return;

  // Удаляем отмеченные скрипты (если были)
  const existingScripts = container.querySelectorAll('script[data-tg-widget]');
  existingScripts.forEach(el => el.remove());

  // Очищаем контейнер полностью (удаляет iframe и прочее)
  container.innerHTML = '';

  // Удаляем глобальные callback'и (без throw)
  try { delete window.onTelegramAuth; } catch (e) { window.onTelegramAuth = undefined; }
  try { delete window.TelegramLoginWidget; } catch (e) { window.TelegramLoginWidget = undefined; }

  widgetScriptEl = null;
}

/* ----------------------
   Главный handler: нажатие кнопки в виджете (Telegram вернёт user payload)
   ---------------------- */

/**
 * Функция, которую вызывает Telegram (через window.onTelegramAuth или TelegramLoginWidget.onAuth).
 * Она:
 *  - проверяет корректность payload,
 *  - пытается отправить его на бекенд через axios (api.post('/auth/telegram')),
 *  - если axios не сработал — выполняет fallback через fetch на ряд возможных URL'ов,
 *  - при успешном ответе сохраняет token и user в localStorage, вызывает setAuthToken(token) и сохраняет user в reactive state.
 */
async function onTelegramAuthCallback(telegramUser) {
  // Быстрая валидация полезной нагрузки
  if (!telegramUser || !telegramUser.id) {
    return;
  }

  // Подготовка preview (без передачи hash впоследствии)
  const preview = safeCopy(telegramUser);
  if (preview.hash) preview.hash = '[REDACTED]';

  // Попытка 1: axios (api) — предполагается, что api уже настроен с baseURL '/api' или корнем
  try {
    const res = await api.post('/auth/telegram', telegramUser);
    if (res?.data?.token && res?.data?.user) {
      setAuthToken(res.data.token);
      localStorage.setItem('token', res.data.token);
      localStorage.setItem('user', JSON.stringify(res.data.user));
      user.value = res.data.user;
      return;
    }
    // если ответ есть, но без токена — продолжаем на fallback
  } catch (axErr) {
    // axios мог упасть — переходим к fetch fallback
  }

  // Попытка 2: fetch fallback на ряд возможных URL'ов
  const candidateUrls = [
    '/auth/telegram',           // относительный корень (если фронт и бэк proxied)
    '/api/auth/telegram',       // традиционный API путь
    (api?.defaults?.baseURL ? api.defaults.baseURL.replace(/\/$/, '') + '/auth/telegram' : null),
    (api?.defaults?.baseURL ? api.defaults.baseURL.replace(/\/$/, '') + '/api/auth/telegram' : null)
  ].filter(Boolean);

  for (const url of candidateUrls) {
    try {
      const fRes = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(telegramUser),
        credentials: 'include' // если используется cookie-based auth (Sanctum)
      });
      const text = await fRes.text();
      let jsonBody = null;
      try { jsonBody = text ? JSON.parse(text) : null; } catch { jsonBody = text; }

      if (fRes.ok && jsonBody?.token && jsonBody?.user) {
        setAuthToken(jsonBody.token);
        localStorage.setItem('token', jsonBody.token);
        localStorage.setItem('user', JSON.stringify(jsonBody.user));
        user.value = jsonBody.user;
        return;
      }
      // иначе пробуем следующий URL
    } catch (fe) {
      // fetch провалился — пробуем следующий URL
    }
  }

  // Если дошли сюда — ни один из способов не дал токен; функция завершается без изменения state
}

/* ----------------------
   Регистрация глобальных callback'ов — обязательно до загрузки скрипта виджета.
   ---------------------- */

/**
 * Регистрируем window.onTelegramAuth и window.TelegramLoginWidget.onAuth.
 * Виджет может вызывать любое из этих имён — регистрируем оба для совместимости.
 */
function registerGlobalCallback() {
  window.onTelegramAuth = (u) => { onTelegramAuthCallback(u); };
  window.TelegramLoginWidget = { onAuth: (u) => onTelegramAuthCallback(u) };
}

/* ----------------------
   Вставка скрипта виджета в DOM
   ---------------------- */

/**
 * Вставляет <script> виджета в контейнер.
 * Метод:
 *  - удаляет старые артефакты,
 *  - регистрирует глобальные callback'и,
 *  - создаёт <script data-tg-widget> и добавляет атрибуты для виджета.
 * Возвращает true/false в зависимости от успешности вставки.
 */
function insertWidgetScript() {
  const container = document.getElementById('telegram-login-container');
  if (!container) return false;

  // Удаляем предыдущий виджет и callback'и
  removeExistingWidget();

  // Регистрируем глобальные функции *перед* загрузкой внешнего скрипта
  registerGlobalCallback();

  // Создаём сам скрипт виджета
  const script = document.createElement('script');

  // Важно: используем постоянный URL виджета. Можно при необходимости добавлять cache-buster.
  script.src = WIDGET_SCRIPT_URL;
  script.async = true;

  // Маркер, чтобы мы могли легко найти и удалить этот скрипт позже
  script.setAttribute('data-tg-widget', '1');

  // Стандартные атрибуты виджета
  script.setAttribute('data-telegram-login', TELEGRAM_BOT);
  script.setAttribute('data-size', 'large');
  script.setAttribute('data-request-access', 'write');

  /*
    Callback-mode: Telegram ожидaет, что в атрибуте data-onauth будет инструкция вызвать глобальную
    функцию с аргументом user, например: data-onauth="onTelegramAuth(user)".
    Поэтому мы регистрируем window.onTelegramAuth (см. registerGlobalCallback выше).
  */
  script.setAttribute('data-onauth', 'onTelegramAuth(user)');

  // По желанию можно скрыть userpic: script.setAttribute('data-userpic', 'false');

  // Поведение при загрузке скрипта: проверяем, создал ли он iframe (виджет часто вставляет iframe)
  script.onload = () => {
    // проверка iframe делается "про запас" — не логируем ничего на клиенте
    setTimeout(() => {
      try {
        const iframe = container.querySelector('iframe');
        // если нужно — можно выставить флаг состояния или отправить телеметрию на бэк
        // но в этом компоненте мы просто выполняем проверку молча
      } catch (e) {
        // игнорируем ошибки проверки iframe
      }
    }, 300);
  };

  // Обработчик ошибок загрузки: можно реализовать fallback URL или retry
  script.onerror = () => {
    // в текущей реализации retry не делается — можно реализовать при желании
  };

  // Вставляем скрипт в контейнер
  container.appendChild(script);
  widgetScriptEl = script;
  return true;
}

/* ----------------------
   Logout
   ---------------------- */

/**
 * Logout:
 *  - опционально шлём серверный POST /logout (если такая логика реализована),
 *  - очищаем client-side state: localStorage, токен axios, reactive user,
 *  - удаляем виджет и вставляем заново для возможности повторной авторизации.
 */
async function logout() {
  // Попытка server-side logout (если нет роута — ловим ошибку и продолжаем)
  try {
    await api.post('/logout');
  } catch (e) {
    // если роут не существует или сервер отдал ошибку — продолжаем очистку клиентской сессии
  }

  // Client-side очистка
  try {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setAuthToken(null);
    user.value = null;

    // Удаляем старый виджет и глобальные callback'и
    removeExistingWidget();

    // Повторно вставляем виджет, чтобы кнопка "Войти" снова появилась
    insertWidgetScript();
  } catch (e) {
    // игнорируем ошибки очистки
  }
}

/* ----------------------
   Тестовый вызов из консоли (опционально)
   ---------------------- */

/**
 * window.__tg_test_invoke() — удобная тестовая утилита для development.
 * В production её можно удалить; оставлена здесь для удобства тестирования.
 */
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
  // вызываем глобальный обработчик, если он зарегистрирован
  try {
    if (window.onTelegramAuth) window.onTelegramAuth(sample);
    else if (window.TelegramLoginWidget && window.TelegramLoginWidget.onAuth) window.TelegramLoginWidget.onAuth(sample);
  } catch (e) {
    // игнорируем ошибки тестовой отправки
  }
};

/* ----------------------
   Lifecycle hooks
   ---------------------- */

/**
 * На unmount делаем аккуратный cleanup: удаляем скрипт, global callbacks и т.д.
 */
onBeforeUnmount(() => {
  try {
    removeExistingWidget();
    if (widgetScriptEl) widgetScriptEl.remove();
    try { delete window.onTelegramAuth; } catch (e) { window.onTelegramAuth = undefined; }
    try { delete window.TelegramLoginWidget; } catch (e) { window.TelegramLoginWidget = undefined; }
  } catch (e) {
    // игнорируем ошибки cleanup
  }
});

/**
 * При монтировании компонента:
 * 1) Сначала пытаемся восстановить сессию из localStorage (token + user).
 *    Если восстановление успешно — не вставляем виджет (пользователь уже авторизован).
 * 2) Если восстановления нет — вставляем виджет (callback-mode).
 */
onMounted(() => {
  // Попытка восстановить сессию из localStorage
  try {
    const sToken = localStorage.getItem('token');
    const sUser = localStorage.getItem('user');
    if (sToken && sUser) {
      try {
        user.value = JSON.parse(sUser);
        setAuthToken(sToken);
        // Если восстановили — не вставляем виджет
        return;
      } catch (e) {
        // Если данные повреждены — очищаем и продолжаем вставку виджета
        localStorage.removeItem('token');
        localStorage.removeItem('user');
      }
    }
  } catch (e) {
    // Если localStorage недоступен — продолжаем вставку виджета
  }

  // Вставляем виджет в контейнер (callback-mode).
  insertWidgetScript();
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
  border: 1px solid #3855da;
  background: #2542dc;
  border-radius: 4px;
}
</style>
