import { defineStore } from 'pinia';
// Импортируем setAuthToken, предполагая, что он находится в '@/plugins/axios.js'
import { setAuthToken } from '@/plugins/axios.js';
import api from '@/plugins/axios.js';

export const useAuthStore = defineStore('auth', {
    state: () => {
        // Восстановление состояния при инициализации
        const token = localStorage.getItem('token') || null;
        let user = null;
        try {
            user = JSON.parse(localStorage.getItem('user')) || null;
        } catch (e) {
            // Если Local Storage поврежден, очищаем
            localStorage.removeItem('user');
            localStorage.removeItem('token');
        }

        // Устанавливаем токен для axios сразу, если он есть
        setAuthToken(token);

        return {
            token: token,
            user: user,
            // Геттер не нужен, состояние можно использовать напрямую
        };
    },

    actions: {
        /**
         * Сохраняет данные пользователя и токен после успешного входа (из любого источника: Telegram, FakeLogin и т.д.)
         * @param {string} token - JWT или другой токен
         * @param {object} userData - объект пользователя
         */
        setUserData(token, userData) {
            this.token = token;
            this.user = userData;

            // Сохраняем в Local Storage
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(userData));

            // Устанавливаем токен для axios
            setAuthToken(token);
        },

        /**
         * Выход из системы.
         * Опционально шлёт серверный POST /logout.
         */
        async logout() {
            // 1. Попытка server-side logout
            try {
                await api.post('/logout');
            } catch (e) {
                // Игнорируем ошибки, если роут не существует или сервер недоступен
            }

            // 2. Client-side очистка
            this.token = null;
            this.user = null;

            // Очищаем Local Storage
            localStorage.removeItem('token');
            localStorage.removeItem('user');

            // Сбрасываем токен axios
            setAuthToken(null);

            // Возвращаем true, чтобы компонент мог выполнить дополнительные действия (например, перерисовку виджета)
            return true;
        },
    },
});