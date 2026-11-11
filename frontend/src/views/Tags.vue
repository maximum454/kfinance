<template>
  <div class="mgmt">
    <PageHeader title="Теги" />
    <form @submit.prevent="addT" class="add-form">
      <input v-model="newName" placeholder="Название тега" required />
      <button>+</button>
    </form>
    <div v-for="t in tags" :key="t.id" class="item">
      <span># {{ t.name }}</span>
      <button class="del" @click="del(t.id)">✕</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'
import PageHeader from '../components/PageHeader.vue'
const tags = ref([])
const newName = ref('')
async function load() { tags.value = await api.getTags() }
async function addT() { await api.createTag({ name: newName.value }); newName.value = ''; load() }
async function del(id) { await api.deleteTag(id); load() }
onMounted(load)
</script>
<style scoped>
.mgmt { display: flex; flex-direction: column; gap: 8px; }
.add-form { display: flex; gap: 8px; }
.add-form input { flex: 1; padding: 8px; border: 1px solid var(--border); border-radius: 8px; }
.add-form button { padding: 8px 16px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 18px; }
.item { display: flex; align-items: center; gap: 8px; background: var(--surface); padding: 10px 12px; border-radius: 10px; }
.del { background: none; border: none; color: var(--danger); cursor: pointer; font-size: 16px; margin-left: auto; }
</style>