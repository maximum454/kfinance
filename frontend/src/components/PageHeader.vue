<template>
  <div class="page-header">
    <button class="page-header__back" aria-label="Назад" @click="goBack">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <h1 class="page-header__title">{{ title }}</h1>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

defineProps({
  title: {type: String, required: true},
})

const router = useRouter()

function goBack() {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/')
  }
}
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: -16px -16px 0;
  padding: 16px;
  background-color: var(--primary);
}

.page-header__back {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: var(--primary);
  color: var(--surface);
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.page-header__back svg {
  width: 20px;
  height: 20px;
}

.page-header__title {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: var(--surface);
}
</style>