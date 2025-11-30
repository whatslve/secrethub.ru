<template>
  <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-xl p-4 shadow transition-colors duration-300">
    <h3 class="font-bold mb-3 text-lg">Лента активности</h3>
    <ul class="space-y-2 text-sm">
      <li @click="$router.push('/events')" class="hover:underline cursor-pointer">Иван создал ивент "Фановая игра"</li>
      <li @click="$router.push('/events')" class="hover:underline cursor-pointer">Мария подключилась к партии</li>
      <li @click="$router.push('/projects')" class="hover:underline cursor-pointer">Новый мини-проект опубликован</li>
    </ul>
  </div>
</template>
