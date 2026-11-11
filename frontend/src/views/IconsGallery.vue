<template>
  <div class="icons-page">
    <PageHeader title="Иконки" />

    <div v-for="group in iconGroups" :key="group.id" class="icon-section">
      <h2 class="icon-section__title">{{ group.title }}</h2>
      <div class="icon-grid">
        <button
          v-for="icon in group.icons"
          :key="icon.id"
          class="icon-cell"
          :class="{ selected: selected === icon.id }"
          @click="select(icon.id)"
          v-html="icon.svg"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { iconGroups } from '../data/icons'
import PageHeader from '../components/PageHeader.vue'

const route = useRoute()
const router = useRouter()
const selected = ref(route.query.select || null)

function select(id) {
  selected.value = id
  const ret = route.query.return || 'categories'
  router.push(ret.includes('?') ? ret + '&icon=' + id : ret + '?icon=' + id)
}
</script>

<style scoped>
.icons-page {
  padding-bottom: 40px;
}

.icon-section {
  margin-bottom: 24px;
}

.icon-section__title {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-secondary);
  margin: 0 0 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.icon-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.icon-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  aspect-ratio: 1;
  background: var(--surface);
  border: 2px solid transparent;
  border-radius: 12px;
  cursor: pointer;
  padding: 12px;
  transition: all 0.15s;
}

.icon-cell:hover {
  border-color: var(--primary);
  background: #ecfdf5;
}

.icon-cell.selected {
  border-color: var(--primary);
  background: #d1fae5;
}

.icon-cell svg {
  width: 28px;
  height: 28px;
  color: var(--text);
}
</style>