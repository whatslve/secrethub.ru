// frontend/src/plugins/axios.js
import axios from 'axios';
import { ref } from 'vue';

const API_BASE = 'https://secrethub.club/api';

const tokenKey = 'auth_token';

// реактивное хранилище пользователя
export const currentUser = ref(null);

// создаем инстанс axios
const api = axios.create({
    baseURL: API_BASE,
    headers: {
        'Content-Type': 'application/json',
    },
});

// функция установки токена
export function setAuthToken(token) {
    if (token) {
        localStorage.setItem(tokenKey, token);
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    } else {
        localStorage.removeItem(tokenKey);
        delete api.defaults.headers.common['Authorization'];
    }
}

// проверяем localStorage при загрузке страницы
const savedToken = localStorage.getItem(tokenKey);
if (savedToken) {
    setAuthToken(savedToken);
}

// обертка для автоматического обновления currentUser
api.interceptors.response.use(
    response => {
        if (response.data?.user) {
            currentUser.value = response.data.user;
        }
        return response;
    },
    error => {
        if (error.response?.status === 401) {
            // сброс токена при 401
            setAuthToken(null);
            currentUser.value = null;
        }
        return Promise.reject(error);
    }
);

export default api;
