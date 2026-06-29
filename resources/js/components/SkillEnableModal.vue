<template>
  <ModalDialog v-model="visible" title="Manage Skills">
    <div class="space-y-4">
      <div v-if="loading" class="text-sm text-gray-400 py-4 text-center">Loading skills...</div>
      <div v-else-if="skills.length === 0" class="text-sm text-gray-400 py-4 text-center">No skills available in the registry.</div>
      <div v-else class="max-h-80 overflow-y-auto space-y-1">
        <label
          v-for="skill in skills"
          :key="skill.slug"
          class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer"
          :class="{ 'bg-indigo-50 border-indigo-200': selected[skill.slug] }"
        >
          <input
            type="checkbox"
            :checked="selected[skill.slug]"
            @change="selected[skill.slug] = !selected[skill.slug]"
            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          />
          <div class="min-w-0">
            <div class="text-sm font-medium text-gray-900">{{ skill.name ?? skill.slug }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ skill.description }}</div>
            <span class="inline-block text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 mt-1">{{ skill.category }}</span>
          </div>
        </label>
      </div>
    </div>

    <template #footer>
      <button
        @click="save"
        :disabled="saving"
        class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ saving ? 'Saving...' : 'Save' }}
      </button>
      <button @click="visible = false" class="px-4 py-2 border border-gray-200 text-sm rounded-lg hover:bg-gray-50">
        Cancel
      </button>
    </template>
  </ModalDialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import apiClient from '@/api/axios'
import ModalDialog from '@/components/ModalDialog.vue'

const props = defineProps<{
  agentId: number
  modelValue: boolean
  currentSkills: any[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  saved: []
}>()

const visible = ref(props.modelValue)
const loading = ref(false)
const saving = ref(false)
const skills = ref<any[]>([])
const selected = ref<Record<string, boolean>>({})

watch(() => props.modelValue, async (v) => {
  visible.value = v
  if (v) await loadSkills()
})

watch(visible, (v) => emit('update:modelValue', v))

async function loadSkills() {
  loading.value = true
  try {
    const res = await apiClient.get('/agents/skills/available')
    skills.value = res.data?.data ?? []
    const currentMap: Record<string, boolean> = {}
    for (const s of props.currentSkills) {
      currentMap[s.skill_slug] = s.is_enabled
    }
    for (const skill of skills.value) {
      if (skill.slug in currentMap) {
        selected.value[skill.slug] = currentMap[skill.slug]
      } else {
        selected.value[skill.slug] = false
      }
    }
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const assignments = Object.entries(selected.value).map(([slug, is_enabled]) => ({
      skill_slug: slug,
      is_enabled,
    }))
    await apiClient.post(`/agents/${props.agentId}/skills`, { assignments })
    emit('saved')
    visible.value = false
  } finally {
    saving.value = false
  }
}
</script>
