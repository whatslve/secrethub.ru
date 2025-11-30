import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './src')
        }
    },
    server: {
        host: '0.0.0.0',       // слушаем все интерфейсы контейнера
        port: 5173,            // порт dev-сервера
        strictPort: true,      // если порт занят — падаем
        hmr: {
            host: '192.168.1.117', // твой локальный IP (для HMR)
            port: 5173
        }
    }
})
