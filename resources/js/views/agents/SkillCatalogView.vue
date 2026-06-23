<template>
  <div class="p-6 space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Skill Catalog</h1>
      <p class="text-sm text-gray-500 mt-1">All available AI skills. Enable them on specific agents from the agent detail page.</p>
    </div>

    <!-- Filters -->
    <div class="flex gap-3 flex-wrap">
      <input v-model="search" type="text" class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-64" placeholder="Search skills..." />
      <select v-model="categoryFilter" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Categories</option>
        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
      </select>
      <select v-model="tierFilter" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Model Tiers</option>
        <option value="fast">Fast</option>
        <option value="standard">Standard</option>
        <option value="powerful">Powerful</option>
      </select>
    </div>

    <!-- Skills Grid -->
    <div v-if="loading" class="text-center py-12 text-gray-400">Loading skills...</div>
    <div v-else-if="filteredSkills.length === 0" class="text-center py-12 text-gray-400">No skills match your filters.</div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="skill in filteredSkills" :key="skill.slug" class="bg-white border border-gray-200 rounded-xl p-5 hover:shadow-sm transition-shadow">
        <div class="flex items-start justify-between mb-2">
          <div>
            <span class="text-xs font-mono text-indigo-600">{{ skill.slug }}</span>
            <h3 class="font-semibold text-gray-900 mt-0.5">{{ skill.name }}</h3>
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full" :class="tierColors[skill.model_tier ?? 'standard']">{{ skill.model_tier ?? 'standard' }}</span>
        </div>
        <p class="text-sm text-gray-500 mb-3">{{ skill.description }}</p>
        <div class="flex items-center justify-between text-xs text-gray-400">
          <div class="flex gap-2">
            <span class="bg-gray-100 px-2 py-0.5 rounded">{{ skill.category }}</span>
            <span class="px-2 py-0.5 rounded" :class="costColors[skill.cost_tier ?? 'medium']">{{ skill.cost_tier ?? 'medium' }} cost</span>
          </div>
          <span>~{{ skill.estimated_tokens ?? '?' }} tokens</span>
        </div>
        <div v-if="skill.tags?.length" class="flex flex-wrap gap-1 mt-3">
          <span v-for="tag in skill.tags" :key="tag" class="text-xs bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full">{{ tag }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const skills = ref<any[]>([])
const loading = ref(true)
const search = ref('')
const categoryFilter = ref('')
const tierFilter = ref('')

const tierColors: Record<string, string> = {
  fast: 'bg-green-100 text-green-700', standard: 'bg-blue-100 text-blue-700', powerful: 'bg-purple-100 text-purple-700'
}
const costColors: Record<string, string> = {
  low: 'bg-green-100 text-green-700', medium: 'bg-yellow-100 text-yellow-700', high: 'bg-red-100 text-red-700'
}

const categories = computed(() => [...new Set(skills.value.map(s => s.category))].sort())

const filteredSkills = computed(() => {
  return skills.value.filter(s => {
    const q = search.value.toLowerCase()
    const matchSearch = !q || s.name.toLowerCase().includes(q) || s.slug.includes(q) || s.description?.toLowerCase().includes(q)
    const matchCat = !categoryFilter.value || s.category === categoryFilter.value
    const matchTier = !tierFilter.value || s.model_tier === tierFilter.value
    return matchSearch && matchCat && matchTier
  })
})

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/v1/agents/skills/available')
    skills.value = data.data ?? []
  } finally {
    loading.value = false
  }
})
</script>
