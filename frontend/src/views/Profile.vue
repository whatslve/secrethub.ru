<template>
  <div class="max-w-2xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Редактирование профиля</h1>
    <form class="flex flex-col gap-3">
      <input type="text" placeholder="Имя" class="input"/>
      <input type="text" placeholder="Статус / Игра" class="input"/>
      <button type="button" class="btn-green">Сохранить</button>
    </form>
  </div>
</template>

<script setup lang="ts">
// Заглушка
</script>

<style scoped>
.input {
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 0.375rem;
}
.btn-green {
  background-color: #34d399;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
}
</style>
