<template>
  <div class="add-page">
    <div class="type-toggle">
      <button :class="{ active: type === 'expense' }" @click="type = 'expense'">Расход</button>
      <button :class="{ active: type === 'income' }" @click="type = 'income'">Доход</button>
    </div>
    <form @submit.prevent="submit">
      <input v-model="amount" type="number" step="0.01" placeholder="Сумма" required />
      <select v-model="account_id" required>
        <option value="">— Выберите счёт —</option>
        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
      </select>
      <div class="cat-grid">
        <div v-for="c in filteredCats" :key="c.id" class="cat-item"
          :class="{ selected: category_id === c.id }" @click="category_id = c.id">
          <span>{{ c.icon || '•' }}</span>
          <small>{{ c.name }}</small>
        </div>
      </div>
      <input v-model="date" type="date" required />
      <select v-model="tagIds" multiple>
        <option v-for="t in tags" :key="t.id" :value="t.id">{{ t.name }}</option>
      </select>
      <textarea v-model="comment" placeholder="Комментарий" rows="2" />
      <button type="submit">Добавить</button>
    </form>
    <p class="msg" v-if="msg">{{ msg }}</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'

const router = useRouter()
const type = ref('expense')
const amount = ref('')
const account_id = ref('')
const category_id = ref(null)
const date = ref(new Date().toISOString().slice(0, 10))
const comment = ref('')
const tagIds = ref([])
const accounts = ref([])
const categories = ref([])
const tags = ref([])
const msg = ref('')

const filteredCats = computed(() => categories.value.filter(c => c.type === type.value))

async function submit() {
  try {
    await api.createTransaction({
      account_id: account_id.value,
      category_id: category_id.value,
      type: type.value,
      amount: amount.value,
      date: date.value,
      comment: comment.value || undefined,
      tags: tagIds.value.length ? tagIds.value : undefined,
    })
    router.push('/')
  } catch (e) {
    msg.value = e.message
  }
}

onMounted(async () => {
  accounts.value = await api.getAccounts()
  categories.value = await api.getCategories()
  tags.value = await api.getTags()
})
</script>

<style scoped>
.add-page { display: flex; flex-direction: column; gap: 12px; }
.type-toggle { display: flex; gap: 8px; }
.type-toggle button { flex: 1; padding: 10px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); cursor: pointer; font-weight: 600; }
.type-toggle button.active { background: var(--primary); color: #fff; border-color: var(--primary); }
form { display: flex; flex-direction: column; gap: 12px; }
input, select, textarea { padding: 12px; border: 1px solid var(--border); border-radius: 10px; outline: none; }
input:focus, select:focus, textarea:focus { border-color: var(--primary); }
.cat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.cat-item { text-align: center; padding: 10px 4px; border: 2px solid transparent; border-radius: 12px; cursor: pointer; background: #f8fafc; }
.cat-item.selected { border-color: var(--primary); background: #eef2ff; }
.cat-item span { font-size: 24px; display: block; }
.cat-item small { font-size: 11px; color: var(--text-secondary); }
button[type=submit] { padding: 14px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; font-size: 16px; }
.msg { color: var(--danger); font-size: 14px; }
</style>