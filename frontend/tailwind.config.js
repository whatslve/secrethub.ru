/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './index.html',
        './src/**/*.{vue,js,ts,jsx,tsx}'  // <-- все файлы проекта
    ],
    darkMode: 'class',  // включаем тёмную тему через класс 'dark'
    theme: {
        extend: {
            // здесь можно расширять цвета, шрифты и т.п.
        },
    },
    plugins: [],
}