<template>
  <PageHeader title="Добавление операции" />
  <div class="add-page">
    <div class="type-toggle">
      <button :class="{ active: type === 'expense' }" @click="type = 'expense'">Расход</button>
      <button :class="{ active: type === 'income' }" @click="type = 'income'">Доход</button>
    </div>
    <form @submit.prevent="submit" novalidate>
      <div class="amount-field" :class="{ invalid: errors.amount }">
        <input ref="amountEl" :value="amount" @input="onAmountInput" @keydown="onAmountKeydown" type="text" inputmode="decimal" placeholder="0" aria-label="Сумма" autofocus />
        <span class="amount-cur">KZT</span>
        <IconRenderer name="calculator" size="24" class="amount-calc" />
      </div>
      <h3>Счёт</h3>
      <select ref="accountEl" v-model="account_id" :class="{ invalid: errors.account }">
        <option value="">— Выберите счёт —</option>
        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
      </select>
      <h3 :class="{ 'label-invalid': errors.category }">Категории</h3>
      <div ref="categoryEl" class="cat-grid" :class="{ invalid: errors.category }">
        <div v-for="c in filteredCats" :key="c.id" class="cat-item"
             :class="{ selected: category_id === c.id }" @click="category_id = c.id">
          <IconRenderer v-if="c.icon && isLucideIcon(c.icon)" :name="c.icon" size="24" />
          <span v-else class="cat-fallback">{{ c.icon || '•' }}</span>
          <small>{{ c.name }}</small>
        </div>
      </div>
      <div class="date-pick">
        <button v-for="b in quickButtons" :key="b.label + b.value" type="button"
                class="date-btn" :class="{ active: date === b.value }" @click="pickQuick(b.value, !!b.custom)">
          <strong>{{ fmt(b.value) }}</strong>
          <small>{{ b.label }}</small>
        </button>
        <div class="date-cal">
          <IconRenderer name="calendar" size="20" />
          <input type="date" v-model="customDate" :max="todayStr" aria-label="Выбрать дату"/>
        </div>
      </div>
      <select v-model="tagIds" multiple>
        <option v-for="t in tags" :key="t.id" :value="t.id">{{ t.name }}</option>
      </select>
      <textarea v-model="comment" placeholder="Комментарий" rows="2"/>
      <button class="add-submit" type="submit">Добавить</button>
    </form>
    <p class="msg" v-if="msg">{{ msg }}</p>
  </div>
</template>

<script setup>
import {ref, computed, onMounted, watch} from 'vue'
import {useRouter, useRoute} from 'vue-router'
import {api} from '../api'
import PageHeader from '../components/PageHeader.vue'
import IconRenderer from '../components/IconRenderer.vue'
import { ALL_ICONS } from '../data/icons'

function isLucideIcon(id) {
  return id && ALL_ICONS.has(id)
}

const router = useRouter()
const route = useRoute()
const type = ref(['income', 'expense'].includes(route.query.type) ? route.query.type : 'expense')
const amount = ref('')
const account_id = ref('')
const category_id = ref(null)
const errors = ref({amount: false, account: false, category: false})
const amountEl = ref(null)
const accountEl = ref(null)
const categoryEl = ref(null)

function toLocalStr(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function shiftDays(n) {
  const d = new Date()
  d.setDate(d.getDate() + n)
  return toLocalStr(d)
}

const todayStr = toLocalStr(new Date())
const date = ref(todayStr)
const customDate = ref(null)

const quickValues = computed(() => ({
  today: todayStr,
  yesterday: shiftDays(-1),
  day_before: shiftDays(-2),
}))

const quickButtons = computed(() => {
  if (customDate.value) {
    return [
      {value: quickValues.value.today, label: 'сегодня'},
      {value: quickValues.value.yesterday, label: 'вчера'},
      {value: customDate.value, label: 'выбрано', custom: true},
    ]
  }
  return [
    {value: quickValues.value.today, label: 'сегодня'},
    {value: quickValues.value.yesterday, label: 'вчера'},
    {value: quickValues.value.day_before, label: 'позавчера'},
  ]
})

function fmt(v) {
  return `${+v.slice(8, 10)}.${+v.slice(5, 7)}`
}

function pickQuick(v, isCustom) {
  if (!isCustom) customDate.value = null
  date.value = v
}

watch(customDate, v => {
  if (v) date.value = v
})

const comment = ref('')
const tagIds = ref([])
const accounts = ref([])
const categories = ref([])
const tags = ref([])
const msg = ref('')

const filteredCats = computed(() => categories.value.filter(c => c.type === type.value))

function onAmountKeydown(e) {
  if (e.ctrlKey || e.metaKey || e.altKey) return
  const control = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Tab', 'Enter']
  if (control.includes(e.key)) return
  if (/^\d$/.test(e.key) || e.key === '.' || e.key === ',') return
  e.preventDefault()
}

function onAmountInput(e) {
  let v = e.target.value.replace(/[^\d.,]/g, '').replace(',', '.')
  const dots = v.split('.')
  if (dots.length > 2) v = dots[0] + '.' + dots.slice(1).join('')
  const [int, dec] = v.split('.')
  const formattedInt = int.replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
  amount.value = dec !== undefined ? `${formattedInt}.${dec}` : formattedInt
}

function amountRaw() {
  return amount.value.replace(/\s/g, '')
}

function validate() {
  errors.value.amount = !amount.value || +amountRaw() <= 0
  errors.value.account = !account_id.value
  errors.value.category = !category_id.value

  const firstInvalid = [
    errors.value.amount && amountEl.value,
    errors.value.account && accountEl.value,
    errors.value.category && categoryEl.value,
  ].find(Boolean)

  if (firstInvalid) {
    firstInvalid.scrollIntoView({behavior: 'smooth', block: 'center'})
    return false
  }

  return true
}

async function submit() {
  if (!validate()) return
  try {
    await api.createTransaction({
      account_id: account_id.value,
      category_id: category_id.value,
      type: type.value,
      amount: amountRaw(),
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
  amountEl.value?.focus()
  accounts.value = await api.getAccounts()
  categories.value = await api.getCategories()
  tags.value = await api.getTags()
  if (accounts.value.length && !account_id.value) {
    account_id.value = accounts.value[0].id
  }
})

watch(amount, () => {
  errors.value.amount = false
})
watch(account_id, () => {
  errors.value.account = false
})
watch(category_id, () => {
  errors.value.category = false
})
</script>

<style scoped>
.add-page {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-bottom: 90px;
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
  border-color: var(--primary);
  opacity: 1;
  border-bottom: 2px solid var(--border);
}

form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

input, select, textarea {
  padding: 12px;
  border: 1px solid var(--border);
  border-radius: 10px;
  outline: none;
}

input:focus, select:focus, textarea:focus {
  border-color: var(--primary);
}

.invalid {
  border-color: var(--danger) !important;
}

.amount-field {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 14px 12px;
  background: #fff;
}

.amount-field:focus-within {
  border-color: var(--primary);
}

.amount-field input {
  flex: 1;
  min-width: 0;
  border: none;
  padding: 0;
  font-size: 26px;
  font-weight: 600;
  outline: none;
}

.amount-field input:focus {
  border-color: transparent;
}

.amount-cur {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-secondary);
  white-space: nowrap;
}

.amount-calc {
  min-width: 24px;
  color: var(--primary);
}

.label-invalid {
  color: var(--danger);
}

.cat-grid.invalid {
  outline: 2px solid var(--danger);
  outline-offset: 3px;
  border-radius: 12px;
}

.cat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.cat-item {
  text-align: center;
  padding: 10px 4px;
  border: 2px solid transparent;
  border-radius: 12px;
  cursor: pointer;
  background: #f8fafc;
}

.cat-item.selected {
  border-color: var(--primary);
  background: #ecfdf5;
}

.cat-item svg {
  display: block;
  margin: 0 auto;
  width: 24px;
  height: 24px;
  color: var(--text);
}

.cat-fallback {
  display: block;
  font-size: 24px;
  line-height: 1;
}

.cat-item small {
  font-size: 11px;
  color: var(--text-secondary);
}

.date-pick {
  display: flex;
  gap: 8px;
}

.date-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  padding: 10px 4px;
  border: 2px solid var(--border);
  border-radius: 12px;
  background: #f8fafc;
  cursor: pointer;
}

.date-btn strong {
  font-size: 15px;
  font-weight: 600;
}

.date-btn small {
  font-size: 11px;
  color: var(--text-secondary);
}

.date-btn.active {
  border-color: var(--primary);
  background: #ecfdf5;
  color: var(--primary);
}

.date-btn.active small {
  color: var(--primary);
}

.date-cal {
  position: relative;
  width: 52px;
  min-width: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--border);
  border-radius: 12px;
  background: #f8fafc;
  color: var(--text-secondary);
  cursor: pointer;
}

.date-cal svg {
  width: 20px;
  height: 20px;
  pointer-events: none;
}

.date-cal input[type=date] {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  padding: 0;
  border: none;
  opacity: 0;
  cursor: pointer;
}

button[type=submit] {
  padding: 14px;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  font-size: 16px;
  margin: 0 16px;
  position: fixed;
  bottom: 40px;
  left: 0;
  right: 0;
}

.msg {
  color: var(--danger);
  font-size: 14px;
}
</style>