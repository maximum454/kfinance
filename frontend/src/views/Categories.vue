<template>
  <div class="mgmt">
    <PageHeader title="Категории" />
    <form @submit.prevent="addCat" class="add-form">
      <input v-model="newName" placeholder="Название" required />
      <select v-model="newType"><option value="expense">Расход</option><option value="income">Доход</option></select>
      <input v-model="newIcon" placeholder="иконка" maxlength="2" class="icon-input" />
      <input v-model="newColor" type="color" class="color-input" />
      <button>+</button>
    </form>

    <div v-for="c in cats" :key="c.id" class="item" :class="{ editing: editId === c.id }">
      <template v-if="editId === c.id">
        <input v-model="editName" placeholder="Название" />
        <select v-model="editType"><option value="expense">Расход</option><option value="income">Доход</option></select>
        <input v-model="editIcon" maxlength="2" class="icon-input" />
        <input v-model="editColor" type="color" class="color-input" />
        <button class="save" @click="save(c.id)">✓</button>
        <button class="cancel" @click="editId = null">✕</button>
      </template>
      <template v-else>
        <span class="cat-dot" :style="{ background: c.color || '#ccc' }">{{ c.icon || '•' }}</span>
        <span class="cat-name" @click="startEdit(c)">{{ c.name }}</span>
        <span class="badge" :class="c.type">{{ c.type === 'expense' ? 'Расход' : 'Доход' }}</span>
        <button class="del" @click="del(c.id)">✕</button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import PageHeader from '../components/PageHeader.vue'

const cats = ref([])
const newName = ref('')
const newType = ref('expense')
const newIcon = ref('')
const newColor = ref('#059669')

const editId = ref(null)
const editName = ref('')
const editType = ref('expense')
const editIcon = ref('')
const editColor = ref('#059669')

async function load() { cats.value = await api.getCategories() }

async function addCat() {
  await api.createCategory({ name: newName.value, type: newType.value, icon: newIcon.value || null, color: newColor.value || null })
  newName.value = ''; newIcon.value = ''; newColor.value = '#059669'
  load()
}

function startEdit(c) {
  editId.value = c.id
  editName.value = c.name
  editType.value = c.type
  editIcon.value = c.icon || ''
  editColor.value = c.color || '#059669'
}

async function save(id) {
  await api.updateCategory(id, { name: editName.value, type: editType.value, icon: editIcon.value || null, color: editColor.value || null })
  editId.value = null
  load()
}

async function del(id) { await api.deleteCategory(id); load() }

onMounted(load)
</script>

<style scoped>
.mgmt { display: flex; flex-direction: column; gap: 8px; }
.add-form { display: flex; gap: 6px; flex-wrap: wrap; }
.add-form input, .add-form select { padding: 8px; border: 1px solid var(--border); border-radius: 8px; }
.add-form input:first-child { flex: 1; min-width: 120px; }
.icon-input { width: 48px; text-align: center; }
.color-input { width: 40px; height: 40px; padding: 2px; cursor: pointer; }
.add-form button { padding: 8px 16px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; }
.item { display: flex; align-items: center; gap: 6px; background: var(--surface); padding: 8px 12px; border-radius: 10px; flex-wrap: wrap; }
.item.editing { background: #ecfdf5; border: 1px solid var(--primary); }
.item input, .item select { padding: 6px 8px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px; }
.item input:first-child { flex: 1; min-width: 80px; }
.cat-dot { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.cat-name { flex: 1; font-size: 14px; cursor: pointer; padding: 2px 0; }
.cat-name:hover { color: var(--primary); }
.item .badge { font-size: 11px; padding: 2px 8px; border-radius: 10px; }
.badge.expense { background: #fef2f2; color: #ef4444; }
.badge.income { background: #f0fdf4; color: #10b981; }
.save { background: #10b981; color: #fff; border: none; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 14px; }
.cancel { background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 14px; padding: 4px; }
.del { background: none; border: none; color: var(--danger); cursor: pointer; font-size: 16px; padding: 4px; }
</style>