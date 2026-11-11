<template>
  <component :is="iconComp" v-if="iconComp" :size="size" />
</template>

<script setup>
import { computed } from 'vue'
import * as Icons from '@lucide/vue'

const props = defineProps({
  name: { type: String, default: '' },
  size: { type: Number, default: 24 },
})

function toPascal(kebab) {
  return kebab
    .split('-')
    .map(s => s.charAt(0).toUpperCase() + s.slice(1))
    .join('')
}

const iconComp = computed(() => {
  if (!props.name) return null
  const pascal = toPascal(props.name)
  return Icons[pascal] || null
})
</script>