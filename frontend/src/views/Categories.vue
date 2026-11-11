<template>
  <div class="cat-page">
    <PageHeader title="Категории"/>

    <div class="type-toggle">
      <button :class="{ active: type === 'expense' }" @click="type = 'expense'">Расход</button>
      <button :class="{ active: type === 'income' }" @click="type = 'income'">Доход</button>
    </div>

    <div class="cat-grid">
      <div
        v-for="c in filteredCats"
        :key="c.id"
        class="cat-card"
        @click="edit(c)"
      >
        <span class="cat-icon" :style="{ background: c.color || '#ccc' }" v-html="iconSvg(c.icon)"></span>
        <span class="cat-name">{{ c.name }}</span>
      </div>

      <button class="cat-card cat-add" @click="create">
        <span class="cat-icon add-icon">+</span>
        <span class="cat-name">Создать</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'
import { ALL_ICONS } from '../data/icons'
import PageHeader from '../components/PageHeader.vue'

const router = useRouter()
const type = ref('expense')
const cats = ref([])

const filteredCats = computed(() => cats.value.filter(c => c.type === type.value))

function iconSvg(iconId) {
  return iconId && ALL_ICONS.has(iconId) ? ALL_ICONS.get(iconId) : ''
}

async function load() {
  cats.value = await api.getCategories()
}

function edit(c) {
  router.push(`/categories/${c.id}/edit`)
}

function create() {
  router.push('/categories/create')
}

onMounted(load)
</script>

<style scoped>
.cat-page {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.type-toggle {
  display: flex;
  gap: 8px;
  justify-content: space-around;
  margin: -16px -16px 0;
  padding: 20px 0;
  background-color: var(--primary);
  border-radius: 0 0 30px 30px;
}

.type-toggle button {
  padding: 6px 16px;
  border: none;
  cursor: pointer;
  font-size: 20px;
  opacity: .6;
  background-color: transparent;
  color: #fff;
  text-transform: uppercase;
}

.type-toggle button.active {
  color: #fff;
  opacity: 1;
  border-bottom: 2px solid var(--border);
}

.cat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.cat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 14px 6px;
  background: var(--surface);
  border: none;
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.15s;
  text-decoration: none;
  color: var(--text);
}

.cat-card:hover {
  background: #ecfdf5;
  transform: translateY(-2px);
}

.cat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  color: #fff;
}

.cat-icon svg {
  width: 24px;
  height: 24px;
  color: #fff;
}

.cat-name {
  font-size: 12px;
  font-weight: 500;
  text-align: center;
  line-height: 1.2;
  word-break: break-word;
}

.cat-add {
  background: #f1f5f9;
  border: 2px dashed var(--border);
  justify-content: center;
}

.cat-add:hover {
  border-color: var(--primary);
  background: #ecfdf5;
}

.add-icon {
  background: var(--primary);
  font-size: 24px;
  font-weight: 600;
}
</style>