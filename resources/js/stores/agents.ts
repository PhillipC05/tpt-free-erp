import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export interface AgentProfile {
  id: number
  name: string
  description: string | null
  agent_type: 'local' | 'openrouter' | 'api' | 'human_subcontractor'
  provider_config: Record<string, any> | null
  is_active: boolean
  created_by: number | null
  executions_count?: number
  skill_assignments_count?: number
  created_at: string
}

export interface AgentExecution {
  id: number
  agent_profile_id: number
  skill_slug: string
  triggered_by: number | null
  trigger_type: string
  input: Record<string, any> | null
  output: Record<string, any> | null
  status: 'queued' | 'running' | 'completed' | 'failed'
  tokens_used: number | null
  model_used: string | null
  duration_ms: number | null
  error_message: string | null
  created_at: string
}

export interface SkillDefinition {
  slug: string
  name: string
  category: string
  description: string
  model_tier: 'fast' | 'standard' | 'powerful'
  cost_tier: 'low' | 'medium' | 'high'
  estimated_tokens: number
  enabled_by_default: boolean
  required_permissions: string[]
  affected_modules: string[]
  tags: string[]
}

export const useAgentsStore = defineStore('agents', () => {
  const agents = ref<AgentProfile[]>([])
  const catalog = ref<SkillDefinition[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Polling state for running executions
  const pollingIntervals = ref<Map<number, ReturnType<typeof setInterval>>>(new Map())

  const activeAgents = computed(() => agents.value.filter(a => a.is_active))
  const agentById = computed(() => (id: number) => agents.value.find(a => a.id === id))

  async function fetchAgents(filters: Record<string, string> = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await axios.get('/api/v1/agents', { params: filters })
      agents.value = data.data ?? []
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to load agents'
    } finally {
      loading.value = false
    }
  }

  async function fetchCatalog() {
    if (catalog.value.length > 0) return // cached
    try {
      const { data } = await axios.get('/api/v1/agents/skills/available')
      catalog.value = data.data ?? []
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to load skill catalog'
    }
  }

  async function createAgent(payload: Partial<AgentProfile>): Promise<AgentProfile | null> {
    try {
      const { data } = await axios.post('/api/v1/agents', payload)
      const agent = data.data as AgentProfile
      agents.value.unshift(agent)
      return agent
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to create agent'
      return null
    }
  }

  async function updateAgent(id: number, payload: Partial<AgentProfile>): Promise<boolean> {
    try {
      const { data } = await axios.put(`/api/v1/agents/${id}`, payload)
      const idx = agents.value.findIndex(a => a.id === id)
      if (idx !== -1) agents.value[idx] = { ...agents.value[idx], ...data.data }
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to update agent'
      return false
    }
  }

  async function deleteAgent(id: number): Promise<boolean> {
    try {
      await axios.delete(`/api/v1/agents/${id}`)
      agents.value = agents.value.filter(a => a.id !== id)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to delete agent'
      return false
    }
  }

  async function runSkill(agentId: number, slug: string, input: Record<string, any> = {}): Promise<{ execution_id: number } | null> {
    try {
      const { data } = await axios.post(`/api/v1/agents/${agentId}/skills/${slug}/run`, { input })
      return data.data
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to trigger skill'
      return null
    }
  }

  /**
   * Poll execution status until it reaches a terminal state (completed/failed).
   * Calls the callback with updated execution on each poll.
   */
  function pollExecution(
    agentId: number,
    executionId: number,
    onUpdate: (exec: AgentExecution) => void,
    intervalMs = 3000,
  ): () => void {
    const key = executionId
    clearExecutionPoll(key)

    const interval = setInterval(async () => {
      try {
        const { data } = await axios.get(`/api/v1/agents/${agentId}/executions/${executionId}`)
        const exec = data.data as AgentExecution
        onUpdate(exec)

        if (exec.status === 'completed' || exec.status === 'failed') {
          clearExecutionPoll(key)
        }
      } catch {
        clearExecutionPoll(key)
      }
    }, intervalMs)

    pollingIntervals.value.set(key, interval)

    return () => clearExecutionPoll(key)
  }

  function clearExecutionPoll(executionId: number) {
    const interval = pollingIntervals.value.get(executionId)
    if (interval) {
      clearInterval(interval)
      pollingIntervals.value.delete(executionId)
    }
  }

  function clearAllPolls() {
    pollingIntervals.value.forEach(interval => clearInterval(interval))
    pollingIntervals.value.clear()
  }

  return {
    agents,
    catalog,
    loading,
    error,
    activeAgents,
    agentById,
    fetchAgents,
    fetchCatalog,
    createAgent,
    updateAgent,
    deleteAgent,
    runSkill,
    pollExecution,
    clearAllPolls,
  }
})
