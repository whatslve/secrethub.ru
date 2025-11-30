import { createRouter, createWebHistory } from 'vue-router'
import Home from '@/views/Home.vue'
import Admin from '@/views/Admin.vue'
import EventForm from '@/views/EventForm.vue'
import Profile from '@/views/Profile.vue'
import ServicesHub from '@/views/ServicesHub.vue'
import TelegramLogin from '@/components/TelegrammLogin.vue'

const routes = [
    { path: '/', name: 'home', component: Home },
    { path: '/login', name: 'login', component: TelegramLogin },

    // Админка
    { path: '/admin', name: 'admin', component: Admin, meta: { requiresAuth: true } },

    { path: '/admin/events/:id/edit', name: 'event-edit', component: EventForm, props: true, meta: { requiresAuth: true } },
    {
        path: '/event/create',
        name: 'EventCreate',
        component: () => EventForm, // страница редактирования профиля
        meta: { requiresAuth: true }
    },
    {
        path: '/profile/edit',
        name: 'ProfileEdit',
        component: () => Profile, // страница редактирования профиля
        meta: { requiresAuth: true }
    },
    // Хаб сервисов
    { path: '/services', name: 'services', component: ServicesHub, meta: { requiresAuth: true } },

    // Ловим все прочие пути
    { path: '/:catchAll(.*)', redirect: '/' },
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// Проверка авторизации
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')

    if (to.meta.requiresAuth && !token) {
        return next('/login')
    }

    next()
})

export default router
