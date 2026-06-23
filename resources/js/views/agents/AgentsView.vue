<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">AI Agents</h1>
        <p class="text-sm text-gray-500 mt-1">Manage automated agents and subcontractors</p>
      </div>
      <button @click="showCreate = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
        + New Agent
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-3">
      <select v-model="typeFilter" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Types</option>
        <option value="local">Local (Ollama)</option>
        <option value="openrouter">OpenRouter</option>
        <option value="api">API</option>
        <option value="human_subcontractor">Human Subcontractor</option>
      </select>
      <select v-model="activeFilter" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">All Status</option>
        <option value="true">Active</option>
        <option value="false">Inactive</option>
      </select>
    </div>

    <!-- Agent Cards -->
    <div v-if="loading" class="text-center py-12 text-gray-400">Loading agents...</div>
    <div v-else-if="agents.length === 0" class="text-center py-12 text-gray-400">
      No agents configured yet. Create your first agent to get started.
    </div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="agent in agents"
        :key="agent.id"
        class="bg-white border border-gray-200 rounded-xl p-5 hover:border-indigo-300 hover:shadow-sm transition-all cursor-pointer"
        @click="$router.push(`/agents/${agent.id}`)"
      >
        <div class="flex items-start justify-between mb-3">
          <div>
            <h3 class="font-semibold text-gray-900">{{ agent.name }}</h3>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1" :class="typeColors[agent.agent_type]">
              {{ typeLabels[agent.agent_type] }}
            </span>
          </div>
          <span class="w-2 h-2 rounded-full mt-1.5" :class="agent.is_active ? 'bg-green-400' : 'bg-gray-300'"></span>
        </div>
        <p v-if="agent.description" class="text-sm text-gray-500 mb-3 line-clamp-2">{{ agent.description }}</p>
        <div class="flex items-center gap-4 text-xs text-gray-400">
          <span>{{ agent.skill_assignments_count ?? 0 }} skills</span>
          <span>{{ agent.executions_count ?? 0 }} runs</span>
        </div>
      </div>
    </div>

    <!-- Create Agent Modal -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl w-full max-w-lg p-6">
        <h2 class="text-lg font-semibold mb-4">New Agent</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input v-model="form.name" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="e.g. Finance Assistant" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select v-model="form.agent_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
              <option value="local">Local (Ollama)</option>
              <option value="openrouter">OpenRouter</option>
              <option value="api">External API</option>
              <option value="human_subcontractor">Human Subcontractor</option>
            </select>
          </div>
          <div v-if="form.agent_type === 'local' || form.agent_type === 'openrouter'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
            <input v-model="form.provider_config.model" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" :placeholder="form.agent_type === 'local' ? 'llama3.1:8b' : 'meta-llama/llama-3.1-8b-instruct:free'" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="What does this agent do?"></textarea>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button @click="createAgent" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Create Agent</button>
          <button @click="showCreate = false" class="px-4 py-2 text-gray-600 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const agents = ref<any[]>([])
const loading = ref(true)
const showCreate = ref(false)
const typeFilter = ref('')
const activeFilter = ref('')

const form = ref({ name: '', agent_type: 'local', description: '', provider_config: { model: '' } })

const typeLabels: Record<string, string> = {
  local: 'Local (Ollama)', openrouter: 'OpenRouter', api: 'API', human_subcontractor: 'Human'
}
const typeColors: Record<string, string> = {
  local: 'bg-blue-100 text-blue-700', openrouter: 'bg-purple-100 text-purple-700',
  api: 'bg-orange-100 text-orange-700', human_subcontractor: 'bg-green-100 text-green-700'
}

async function loadAgents() {
  loading.value = true
  try {
    const params: Record<string, string> = {}
    if (typeFilter.value) params.type = typeFilter.value
    if (activeFilter.value) params.active = activeFilter.value
    const { data } = await axios.get('/api/v1/agents', { params })
    agents.value = data.data || []
  } finally {
    loading.value = false
  }
}

async function createAgent() {
  const payload = {
    name: form.value.name,
    agent_type: form.value.agent_type,
    description: form.value.description,
    provider_config: form.value.provider_config.model ? form.value.provider_config : null,
  }
  await axios.post('/api/v1/agents', payload)
  showCreate.value = false
  form.value = { name: '', agent_type: 'local', description: '', provider_config: { model: '' } }
  loadAgents()
}

watch([typeFilter, activeFilter], loadAgents)
onMounted(loadAgents)
</script>
