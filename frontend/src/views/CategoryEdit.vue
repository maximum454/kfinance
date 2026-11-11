<template>
  <div class="cat-edit">
    <PageHeader :title="isEdit ? 'Редактировать' : 'Новая категория'" />

    <div class="form">
      <input
        v-model="name"
        placeholder="Название категории"
        class="field"
        maxlength="30"
      />

      <div class="type-toggle">
        <button :class="{ active: type === 'expense' }" @click="type = 'expense'">Расход</button>
        <button :class="{ active: type === 'income' }" @click="type = 'income'">Доход</button>
      </div>

      <label class="section-label">Иконка</label>
      <div class="icon-grid">
        <div
          v-for="icon in firstIcons"
          :key="icon.id"
          class="icon-cell"
          :class="{ selected: icon.id === selectedIcon }"
          @click="selectedIcon = icon.id"
          v-html="icon.svg"
        />
        <button class="icon-cell icon-more" @click="openIcons">
          <span>•••</span>
          <small>ещё</small>
        </button>
      </div>

      <label class="section-label">Цвет</label>
      <div class="color-grid">
        <button
          v-for="c in presetColors"
          :key="c"
          class="color-cell"
          :class="{ selected: selectedColor === c }"
          :style="{ background: c }"
          @click="selectedColor = c"
        />
        <label class="color-cell color-custom" :class="{ selected: isCustomColor }">
          <input type="color" v-model="customColor" @input="selectedColor = customColor" />
          <span>🎨</span>
        </label>
      </div>

      <button class="save-btn" @click="save" :disabled="saving">
        {{ saving ? 'Сохранение…' : 'Сохранить' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { api } from '../api'
import { iconGroups, ALL_ICONS } from '../data/icons'
import PageHeader from '../components/PageHeader.vue'

const router = useRouter()
const route = useRoute()

const isEdit = computed(() => !!route.params.id)
const name = ref('')
const type = ref('expense')
const selectedIcon = ref('')
const selectedColor = ref('#059669')
const customColor = ref('#059669')
const saving = ref(false)

const presetColors = [
  '#ef4444', '#f97316', '#eab308', '#22c55e',
  '#06b6d4', '#3b82f6', '#8b5cf6',
]

const isCustomColor = computed(() => !presetColors.includes(selectedColor.value))

const allIcons = computed(() => iconGroups.flatMap(g => g.icons))
const firstIcons = computed(() => allIcons.value.slice(0, 7))

function openIcons() {
  const ret = isEdit.value ? `/categories/${route.params.id}/edit` : '/categories/create'
  router.push('/icons?return=' + encodeURIComponent(ret) + '&select=' + selectedIcon.value)
}

onMounted(async () => {
  const icon = route.query.icon
  if (icon && ALL_ICONS.has(icon)) {
    selectedIcon.value = icon
  }

  if (isEdit.value) {
    try {
      const cats = await api.getCategories()
      const cat = cats.find(c => String(c.id) === route.params.id)
      if (cat) {
        name.value = cat.name
        type.value = cat.type
        selectedIcon.value = cat.icon || ''
        selectedColor.value = cat.color || '#059669'
        customColor.value = selectedColor.value
      }
    } catch (e) {
      console.error(e)
    }
  }
})

async function save() {
  if (!name.value.trim()) return
  saving.value = true
  try {
    const data = {
      name: name.value.trim(),
      type: type.value,
      icon: selectedIcon.value || null,
      color: selectedColor.value || null,
    }
    if (isEdit.value) {
      await api.updateCategory(route.params.id, data)
    } else {
      await api.createCategory(data)
    }
    router.push('/categories')
  } catch (e) {
    alert(e.message)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.cat-edit {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.field {
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 12px;
  font-size: 16px;
  outline: none;
}

.field:focus {
  border-color: var(--primary);
}

.type-toggle {
  display: flex;
  gap: 8px;
  justify-content: space-around;
  padding: 14px 0;
  background-color: var(--primary);
  border-radius: 16px;
}

.type-toggle button {
  padding: 6px 16px;
  border: none;
  cursor: pointer;
  font-size: 18px;
  opacity: .6;
  background-color: transparent;
  color: #fff;
  text-transform: uppercase;
  font-weight: 600;
}

.type-toggle button.active {
  opacity: 1;
  border-bottom: 2px solid #fff;
}

.section-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-secondary);
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
  padding: 10px;
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

.icon-more {
  flex-direction: column;
  gap: 2px;
  color: var(--text-secondary);
  font-size: 12px;
}

.icon-more span {
  font-size: 20px;
  letter-spacing: 2px;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.color-cell {
  aspect-ratio: 1;
  border-radius: 50%;
  border: 3px solid transparent;
  cursor: pointer;
  transition: all 0.15s;
}

.color-cell:hover {
  transform: scale(1.1);
}

.color-cell.selected {
  border-color: var(--text);
  box-shadow: 0 0 0 2px var(--surface), 0 0 0 4px var(--text);
}

.color-custom {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border-radius: 50%;
  overflow: hidden;
  position: relative;
}

.color-custom input[type="color"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}

.color-custom span {
  font-size: 20px;
  pointer-events: none;
}

.save-btn {
  padding: 14px;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
}

.save-btn:disabled {
  opacity: 0.6;
}
</style>