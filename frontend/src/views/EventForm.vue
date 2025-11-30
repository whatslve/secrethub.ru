<template>
  <div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">{{ isEdit ? 'Редактировать' : 'Создать' }} ивент</h1>
    <form class="flex flex-col gap-3">
      <input v-model="name" type="text" placeholder="Название" class="input"/>
      <input v-model="date" type="date" class="input"/>
      <input v-model="game" type="text" placeholder="Игра" class="input"/>
      <select v-model="status" class="input">
        <option>Открыт</option>
        <option>Закрыт</option>
      </select>
      <div class="flex gap-3">
        <button type="button" @click="save" class="btn-green">{{ isEdit ? 'Сохранить' : 'Создать' }}</button>
        <button type="button" @click="goBack" class="btn-red">Отмена</button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const isEdit = !!route.params.id

const name = ref('')
const date = ref('')
const game = ref('')
const status = ref('Открыт')

// пример загрузки данных для редактирования (можно позже заменить на API)
onMounted(() => {
  if (isEdit) {
    // Заглушка: получаем "событие" по id
    name.value = 'Фан-ивент'
    date.value = '2025-11-26'
    game.value = 'Dota 2'
    status.value = 'Открыт'
  }
})

function save() {
  // Здесь потом будет вызов API
  alert(`${isEdit ? 'Сохранено' : 'Создано'}: ${name.value}`)
  // После сохранения редиректим на список ивентов
  router.push({ name: 'admin' })
}

function goBack() {
  router.push({ name: 'admin' })
}
</script>

<style scoped>
.input {
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 0.375rem;
  background-color: white;
}
.btn-green {
  background-color: #34d399;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
}
.btn-red {
  background-color: #f87171;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
}
</style>
