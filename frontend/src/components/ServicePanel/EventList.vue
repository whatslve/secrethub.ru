<template>
  <div>
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">События</h1>
      <button
          @click="$router.push({ name: 'EventCreate' })"
          class="px-5 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition-colors duration-200"
      >
        Создать ивент
      </button>
    </div>

    <div class="overflow-x-auto rounded-lg shadow-lg">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
        <tr>
          <th class="px-4 py-2 text-left">Название</th>
          <th class="px-4 py-2 text-left">Дата</th>
          <th class="px-4 py-2 text-left">Игра</th>
          <th class="px-4 py-2 text-left">Статус</th>
          <th class="px-4 py-2 text-left">Действия</th>
        </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 divide-y divide-gray-200 dark:divide-gray-700">
        <tr v-for="(event, index) in events" :key="index" class="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
          <td class="px-4 py-2">{{ event.name }}</td>
          <td class="px-4 py-2">{{ event.date }}</td>
          <td class="px-4 py-2">{{ event.game }}</td>
          <td class="px-4 py-2">{{ event.status }}</td>
          <td class="px-4 py-2 flex gap-2">
            <button
                @click="$router.push({ name: 'EventEdit', params: { id: index } })"
                class="px-2 py-1 bg-yellow-400 text-black rounded hover:bg-yellow-500 transition-colors duration-200"
            >
              Ред.
            </button>
            <button
                @click="alert('Удаление будет через API')"
                class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition-colors duration-200"
            >
              Удал.
            </button>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const events = ref([
  { name: 'Fun Game', date: '26.11.2025', game: 'Dota 2', status: 'Открыт' },
  { name: 'Mini Project Meeting', date: '27.11.2025', game: 'Web', status: 'Закрыт' }
])
</script>
