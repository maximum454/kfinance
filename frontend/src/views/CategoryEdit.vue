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

      <div class="type-check" role="group" aria-label="Тип категории">
        <label class="type-option" :class="{ checked: type === 'expense' }">
          <input type="checkbox" :checked="type === 'expense'" @change="type = 'expense'" />
          <span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          <span class="label">Расход</span>
        </label>
        <label class="type-option" :class="{ checked: type === 'income' }">
          <input type="checkbox" :checked="type === 'income'" @change="type = 'income'" />
          <span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          <span class="label">Доход</span>
        </label>
      </div>

      <label class="section-label">Иконка</label>
      <div class="icon-grid">
        <div
          v-for="icon in firstIcons"
          :key="icon.id"
          class="icon-cell"
          :class="{ selected: icon.id === selectedIcon }"
          :style="icon.id === selectedIcon ? {
            backgroundColor: selectedColor + '80', // Полупрозрачный фон (12% прозрачности)
            borderColor: selectedColor,              // Цветная рамка
          } : {}"
          @click="selectedIcon = icon.id"
        >
          <IconRenderer :name="icon.lucide" :size="28" :style="{ color: icon.id === selectedIcon ? '#ffffff' : '' }" />
        </div>
        <button class="icon-cell icon-more" @click="openIcons">
          <span>•••</span>
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
import IconRenderer from '../components/IconRenderer.vue'

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
const firstIcons = computed(() => {
  const baseIcons = allIcons.value.slice(0, 15)
  // Если выбрана какая-то иконка
  if (selectedIcon.value) {
    // Проверяем, есть ли она уже в базовой семерке/пятнашке
    const exists = baseIcons.some(icon => icon.id === selectedIcon.value)

    // Если её там нет, но мы знаем её объект — добавляем/ставим на первое место
    if (!exists) {
      const foundIcon = allIcons.value.find(icon => icon.id === selectedIcon.value)
      if (foundIcon) {
        // Берем первые 14 штук и подставляем выбранную на первое место
        return [foundIcon, ...baseIcons.slice(0, 14)]
      }
    }
  }

  return baseIcons
})

function openIcons() {
  const ret = isEdit.value ? `/categories/${route.params.id}/edit` : '/categories/create'
  router.push('/icons?return=' + encodeURIComponent(ret) + '&select=' + selectedIcon.value)
}

onMounted(async () => {
  // 1. Сначала пытаемся получить иконку из query-параметра (если пришли со страницы выбора)
  const queryIcon = route.query.icon
  if (queryIcon && ALL_ICONS.has(queryIcon)) {
    selectedIcon.value = queryIcon
  }

  // 2. Если это режим редактирования, подгружаем данные категории
  if (isEdit.value) {
    try {
      const cats = await api.getCategories()
      const cat = cats.find(c => String(c.id) === route.params.id)
      if (cat) {
        name.value = cat.name
        type.value = cat.type
        // Загружаем иконку с сервера ТОЛЬКО ЕСЛИ в query-параметрах нет свежовыбранной иконки
        if (!queryIcon) {
          selectedIcon.value = cat.icon || ''
        }
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
  padding-bottom: 90px;
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
  grid-template-columns: repeat(8, 1fr);
  gap: 15px;
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
  position: fixed;
  bottom: 20px;
  left: 0;
  right: 0;
  margin: 0 16px;
}

.save-btn:disabled {
  opacity: 0.6;
}

/* Контейнер для переключателей */
.type-check {
  display: flex;
  gap: 12px;
  width: 100%;
}

/* Карточка опции (label) */
.type-option {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 12px 16px;
  background-color: #ffffff;
  border: 2px solid #e5e7eb;
  border-radius: 14px;
  cursor: pointer;
  user-select: none;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

/* Ховер на карточку */
.type-option:hover {
  border-color: #cbd5e1;
  background-color: #f8fafc;
}

/* Скрываем дефолтный инпут */
.type-option input[type="checkbox"] {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Кастомный кавадратик под галочку */
.type-option .box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border: 2px solid #cbd5e1;
  border-radius: 6px;
  background-color: #fff;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

/* SVG Галочка */
.type-option .box svg {
  width: 14px;
  height: 14px;
  color: #ffffff;
  stroke-dasharray: 24;
  stroke-dashoffset: 24;
  transition: stroke-dashoffset 0.25s ease 0.05s;
}

/* Текст внутри */
.type-option .label {
  font-size: 15px;
  font-weight: 600;
  color: #64748b;
  transition: color 0.2s ease;
}

/* Эффект нажатия (Active / Tap) */
.type-option:active {
  transform: scale(0.97);
}

/* ==========================================
   АКТИВНЫЕ СОСТОЯНИЯ (checked)
   ========================================== */

/* Общий checked стиль */
.type-option.checked {
  box-shadow: 0 6px 16px -4px rgba(0, 0, 0, 0.08);
}

.type-option.checked .box svg {
  stroke-dashoffset: 0;
}

/* 1. Стили для РАСХОДА (зеленый как в приложении или красный/оранжевый) */
.type-option.checked:has(input[type="checkbox"]:checked) {
  /* Если нужно сделать разные цвета для Расход/Доход: */
}

/* Если тип 'expense' (Расход) — Активный */
.type-option:first-child.checked {
  border-color: #ef4444;
  background-color: #fef2f2;
}
.type-option:first-child.checked .box {
  background-color: #ef4444;
  border-color: #ef4444;
}
.type-option:first-child.checked .label {
  color: #991b1b;
}

/* Если тип 'income' (Доход) — Активный */
.type-option:last-child.checked {
  border-color: #10b981;
  background-color: #ecfdf5;
}
.type-option:last-child.checked .box {
  background-color: #10b981;
  border-color: #10b981;
}
.type-option:last-child.checked .label {
  color: #065f46;
}
</style>