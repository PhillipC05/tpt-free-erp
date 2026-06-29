<template>
  <div class="p-6 space-y-6">
    <div v-if="loading" class="text-center py-12 text-gray-400">Loading...</div>
    <template v-else-if="agent">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <button @click="$router.push('/agents')" class="text-gray-400 hover:text-gray-600">←</button>
        <div class="flex-1">
          <h1 class="text-2xl font-bold text-gray-900">{{ agent.name }}</h1>
          <p class="text-sm text-gray-500">{{ typeLabels[agent.agent_type] }} · {{ agent.is_active ? 'Active' : 'Inactive' }}</p>
        </div>
        <div class="flex gap-2">
          <button @click="toggleActive" class="px-3 py-1.5 border border-gray-200 text-sm rounded-lg hover:bg-gray-50">
            {{ agent.is_active ? 'Deactivate' : 'Activate' }}
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="flex gap-6">
          <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
            class="py-3 text-sm font-medium border-b-2 -mb-px transition-colors flex items-center gap-1.5"
            :class="activeTab === tab.key ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
            <span>{{ tab.label }}</span>
            <span v-if="tab.key === 'executions' && hasRunningExecutions" class="inline-block w-2 h-2 rounded-full bg-blue-500 animate-pulse" />
          </button>
        </nav>
      </div>

      <!-- Skills Tab -->
      <div v-if="activeTab === 'skills'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-900">Enabled Skills</h2>
          <button @click="showSkillBrowser = true" class="text-sm text-indigo-600 hover:text-indigo-700">+ Enable Skill</button>
        </div>
        <div v-if="skills.length === 0" class="text-sm text-gray-400 py-4">No skills enabled. Browse the catalog to add skills.</div>
        <div v-for="skill in skills" :key="skill.skill_slug" class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-medium text-sm text-gray-900">{{ skill.skill_meta?.name ?? skill.skill_slug }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full" :class="skill.is_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                  {{ skill.is_enabled ? 'Enabled' : 'Disabled' }}
                </span>
              </div>
              <p class="text-xs text-gray-400 mt-0.5">{{ skill.skill_meta?.description }}</p>
            </div>
            <div class="flex gap-2">
              <button @click="runSkill(skill.skill_slug)" class="text-xs px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">Run</button>
              <button @click="toggleSkill(skill)" class="text-xs px-3 py-1 border border-gray-200 rounded hover:bg-gray-50">
                {{ skill.is_enabled ? 'Disable' : 'Enable' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <SkillEnableModal
        :agent-id="Number(agentId)"
        v-model="showSkillBrowser"
        :current-skills="skills"
        @saved="loadAll"
      />

      <!-- Executions Tab -->
      <div v-if="activeTab === 'executions'" class="space-y-3">
        <h2 class="font-semibold text-gray-900">Execution History</h2>
        <div v-if="executions.length === 0" class="text-sm text-gray-400 py-4">No executions yet.</div>
        <div v-for="exec in executions" :key="exec.id" class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <span class="font-mono text-xs text-gray-500">#{{ exec.id }}</span>
              <span class="ml-2 font-medium text-sm">{{ exec.skill_slug }}</span>
              <span class="ml-2 text-xs px-2 py-0.5 rounded-full" :class="statusColors[exec.status]">{{ exec.status }}</span>
            </div>
            <div class="text-xs text-gray-400">
              {{ exec.duration_ms ? exec.duration_ms + 'ms' : '' }}
              {{ exec.tokens_used ? '· ' + exec.tokens_used + ' tokens' : '' }}
            </div>
          </div>
          <div v-if="exec.error_message" class="mt-2 text-xs text-red-500">{{ exec.error_message }}</div>
          <div class="text-xs text-gray-400 mt-1">{{ new Date(exec.created_at).toLocaleString() }}</div>
        </div>
      </div>

      <!-- Tokens Tab -->
      <div v-if="activeTab === 'tokens'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-900">API Tokens</h2>
          <button @click="showTokenCreate = true" class="text-sm text-indigo-600 hover:text-indigo-700">+ New Token</button>
        </div>
        <div v-if="newToken" class="bg-green-50 border border-green-200 rounded-lg p-4">
          <p class="text-sm font-medium text-green-800 mb-2">Token created — copy it now, it won't be shown again</p>
          <code class="text-xs bg-white border border-green-200 rounded px-3 py-2 block break-all">{{ newToken }}</code>
          <button @click="newToken = ''" class="text-xs text-green-600 mt-2 hover:underline">Dismiss</button>
        </div>
        <div v-for="token in agent.tokens ?? []" :key="token.id" class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <span class="font-medium text-sm">{{ token.name }}</span>
              <span v-if="token.expires_at" class="ml-2 text-xs text-gray-400">Expires {{ new Date(token.expires_at).toLocaleDateString() }}</span>
            </div>
            <button @click="revokeToken(token.id)" class="text-xs text-red-500 hover:text-red-700">Revoke</button>
          </div>
        </div>
        <!-- Token create modal -->
        <div v-if="showTokenCreate" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <h3 class="font-semibold mb-4">New API Token</h3>
            <input v-model="tokenForm.name" type="text" class="w-full border rounded-lg px-3 py-2 text-sm mb-4" placeholder="Token name (e.g. Production Agent)" />
            <div class="flex gap-3">
              <button @click="createToken" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Create</button>
              <button @click="showTokenCreate = false" class="px-4 py-2 border rounded-lg text-sm">Cancel</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Schedules Tab -->
      <div v-if="activeTab === 'schedules'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-900">Schedules</h2>
          <button @click="showScheduleCreate = true" class="text-sm text-indigo-600 hover:text-indigo-700">+ New Schedule</button>
        </div>
        <div v-if="schedules.length === 0" class="text-sm text-gray-400 py-4">No schedules configured.</div>
        <div v-for="sched in schedules" :key="sched.id" class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <span class="font-medium text-sm">{{ sched.skill_slug }}</span>
              <code class="ml-2 text-xs bg-gray-100 px-2 py-0.5 rounded">{{ sched.cron_expression }}</code>
            </div>
            <div class="flex items-center gap-3">
              <span :class="sched.is_active ? 'text-green-500' : 'text-gray-400'" class="text-xs">{{ sched.is_active ? 'Active' : 'Paused' }}</span>
              <button @click="deleteSchedule(sched.id)" class="text-xs text-red-400 hover:text-red-600">Delete</button>
            </div>
          </div>
          <div class="text-xs text-gray-400 mt-1">Next run: {{ sched.next_run_at ? new Date(sched.next_run_at).toLocaleString() : 'Not scheduled' }}</div>
        </div>
      </div>

      <ScheduleCreateModal
        :agent-id="Number(agentId)"
        v-model="showScheduleCreate"
        @saved="loadAll"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import SkillEnableModal from '@/components/SkillEnableModal.vue'
import ScheduleCreateModal from '@/components/ScheduleCreateModal.vue'

const route = useRoute()
const agentId = route.params.id
const agent = ref<any>(null)
const skills = ref<any[]>([])
const executions = ref<any[]>([])
const schedules = ref<any[]>([])
const loading = ref(true)
const activeTab = ref('skills')
const showSkillBrowser = ref(false)
const showScheduleCreate = ref(false)
const showTokenCreate = ref(false)
const newToken = ref('')
const tokenForm = ref({ name: '' })
const pollInterval = ref<ReturnType<typeof setInterval> | null>(null)

const hasRunningExecutions = computed(() =>
  executions.value.some(e => e.status === 'running' || e.status === 'queued')
)

const tabs = [
  { key: 'skills', label: 'Skills' },
  { key: 'executions', label: 'Executions' },
  { key: 'tokens', label: 'API Tokens' },
  { key: 'schedules', label: 'Schedules' },
]

const typeLabels: Record<string, string> = {
  local: 'Local (Ollama)', openrouter: 'OpenRouter', api: 'API', human_subcontractor: 'Human Subcontractor'
}
const statusColors: Record<string, string> = {
  queued: 'bg-yellow-100 text-yellow-700', running: 'bg-blue-100 text-blue-700',
  completed: 'bg-green-100 text-green-700', failed: 'bg-red-100 text-red-700'
}

async function fetchExecutions() {
  try {
    const execRes = await axios.get(`/api/v1/agents/${agentId}/executions`)
    executions.value = execRes.data.data ?? []
  } catch {
    // silently ignore
  }
  if (hasRunningExecutions.value) {
    if (!pollInterval.value) {
      pollInterval.value = setInterval(fetchExecutions, 5000)
    }
  } else {
    if (pollInterval.value) {
      clearInterval(pollInterval.value)
      pollInterval.value = null
    }
  }
}

async function loadAll() {
  loading.value = true
  try {
    const [agentRes, skillsRes, execRes, schedRes] = await Promise.all([
      axios.get(`/api/v1/agents/${agentId}`),
      axios.get(`/api/v1/agents/${agentId}/skills`),
      axios.get(`/api/v1/agents/${agentId}/executions`),
      axios.get(`/api/v1/agents/${agentId}/schedules`),
    ])
    agent.value = agentRes.data.data
    skills.value = skillsRes.data.data ?? []
    executions.value = execRes.data.data ?? []
    schedules.value = schedRes.data.data ?? []
  } finally {
    loading.value = false
  }
  // Kick off polling if any execution is active after initial load
  if (hasRunningExecutions.value) {
    if (!pollInterval.value) {
      pollInterval.value = setInterval(fetchExecutions, 5000)
    }
  }
}

async function toggleActive() {
  await axios.put(`/api/v1/agents/${agentId}`, { is_active: !agent.value.is_active })
  loadAll()
}

async function toggleSkill(skill: any) {
  await axios.put(`/api/v1/agents/${agentId}/skills/${skill.skill_slug}`, { is_enabled: !skill.is_enabled })
  loadAll()
}

async function runSkill(slug: string) {
  await axios.post(`/api/v1/agents/${agentId}/skills/${slug}/run`, { input: {} })
  activeTab.value = 'executions'
  setTimeout(loadAll, 1000)
}

async function createToken() {
  const { data } = await axios.post(`/api/v1/agents/${agentId}/tokens`, tokenForm.value)
  newToken.value = data.data?.plain_token ?? ''
  showTokenCreate.value = false
  tokenForm.value = { name: '' }
  loadAll()
}

async function revokeToken(tokenId: number) {
  await axios.delete(`/api/v1/agents/${agentId}/tokens/${tokenId}`)
  loadAll()
}

async function deleteSchedule(schedId: number) {
  await axios.delete(`/api/v1/agents/${agentId}/schedules/${schedId}`)
  loadAll()
}

onMounted(loadAll)

onUnmounted(() => {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
    pollInterval.value = null
  }
})
</script>
