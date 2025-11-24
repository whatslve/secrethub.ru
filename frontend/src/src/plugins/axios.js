import axios from 'axios';

// Создаем экземпляр с базовым URL вашего бекенда
const api = axios.create({
    baseURL: 'https://secrethub.club/api',
    withCredentials: true, // если нужно для cookie
});

// Функция для установки токена
export function setAuthToken(token) {
    if (token) {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        localStorage.setItem('authToken', token); // сохраняем для перезагрузки
    } else {
        delete api.defaults.headers.common['Authorization'];
        localStorage.removeItem('authToken');
    }
}

// При загрузке страницы пробуем восстановить токен
const savedToken = localStorage.getItem('authToken');
if (savedToken) setAuthToken(savedToken);

export default api;
