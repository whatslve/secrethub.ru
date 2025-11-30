<template>
  <div class="bg-gradient-to-b from-purple-500 to-purple-400 dark:from-gray-800 dark:to-gray-700 text-white dark:text-gray-100 rounded-xl p-5 shadow-lg transition-colors duration-300">
    <h3 class="font-bold mb-3 text-lg">Сервисы</h3>
    <ul class="space-y-2 text-sm">
      <li @click="$router.push('/miniapp/1')" class="hover:underline cursor-pointer">Мини-приложение 1</li>
      <li @click="$router.push('/miniapp/2')" class="hover:underline cursor-pointer">Мини-приложение 2</li>
      <li @click="$router.push('/calendar')" class="hover:underline cursor-pointer">Календарь ивентов</li>
    </ul>
  </div>
</template>
