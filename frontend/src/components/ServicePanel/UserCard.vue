<template>
  <div class="bg-gradient-to-b from-blue-500 to-blue-400 dark:from-gray-800 dark:to-gray-700 text-white dark:text-gray-100 rounded-xl p-5 shadow-lg transition-colors duration-300">
    <div class="flex flex-col items-center">
      <img src="https://avatars.githubusercontent.com/u/27950350?s=400" alt="Avatar" class="w-24 h-24 rounded-full border-4 border-white dark:border-gray-100 mb-3">
      <h2 class="font-bold text-xl mb-1">Владислав</h2>
      <p class="text-sm mb-3">Статус: Играет в Dota 2 🎮</p>
      <button
          @click="$router.push({ name: 'ProfileEdit' })"
          class="px-4 py-1 bg-white text-blue-600 dark:bg-gray-700 dark:text-white rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 mb-3"
      >
        Редактировать профиль
      </button>

      <div class="flex justify-center gap-2 mb-3">
        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded">Керри</span>
        <span class="bg-purple-500 text-white text-xs px-2 py-1 rounded">Саппорт</span>
      </div>
      <h3 class="font-semibold mb-2 text-sm">Ачивки</h3>
      <ul class="flex flex-wrap gap-2 text-xs">
        <li class="bg-yellow-400 text-black px-2 py-1 rounded shadow">100 игр</li>
        <li class="bg-pink-500 text-white px-2 py-1 rounded shadow">Топ стример</li>
      </ul>
    </div>
  </div>
</template>
