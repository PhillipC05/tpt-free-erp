<template>
  <ModalDialog v-model="visible" title="Create Schedule">
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
        <input
          v-model="form.name"
          type="text"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
          placeholder="e.g. Daily Report"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Skill</label>
        <select v-model="form.skill_slug" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
          <option value="">Select a skill...</option>
          <option v-for="skill in skills" :key="skill.slug" :value="skill.slug">{{ skill.name ?? skill.slug }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Schedule</label>
        <div class="flex flex-wrap gap-2 mb-2">
          <button
            v-for="preset in cronPresets"
            :key="preset.value"
            @click="form.cron_expression = preset.value"
            type="button"
            class="text-xs px-2.5 py-1 rounded-full border transition-colors"
            :class="form.cron_expression === preset.value
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
          >
            {{ preset.label }}
          </button>
        </div>
        <input
          v-model="form.cron_expression"
          type="text"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono"
          placeholder="0 9 * * *"
        />
        <p class="text-xs text-gray-400 mt-1">Cron expression: minute hour day-of-month month day-of-week</p>
      </div>

      <div class="flex items-center gap-3">
        <label class="relative inline-flex items-center cursor-pointer">
          <input v-model="form.is_active" type="checkbox" class="sr-only peer" />
          <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
        </label>
        <span class="text-sm text-gray-700">{{ form.is_active ? 'Active' : 'Paused' }}</span>
      </div>
    </div>

    <template #footer>
      <button
        @click="save"
        :disabled="saving || !form.name || !form.skill_slug || !form.cron_expression"
        class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ saving ? 'Creating...' : 'Create Schedule' }}
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
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  saved: []
}>()

const visible = ref(props.modelValue)
const saving = ref(false)
const skills = ref<any[]>([])

const form = ref({
  name: '',
  skill_slug: '',
  cron_expression: '0 9 * * *',
  is_active: true,
})

const cronPresets = [
  { label: 'Every hour', value: '0 * * * *' },
  { label: 'Daily at 9am', value: '0 9 * * *' },
  { label: 'Daily at 6pm', value: '0 18 * * *' },
  { label: 'Every Monday', value: '0 9 * * 1' },
  { label: '1st of month', value: '0 9 1 * *' },
  { label: 'Every 6 hours', value: '0 */6 * * *' },
]

watch(() => props.modelValue, async (v) => {
  visible.value = v
  if (v) await loadSkills()
})

watch(visible, (v) => emit('update:modelValue', v))

async function loadSkills() {
  try {
    const res = await apiClient.get('/agents/skills/available')
    skills.value = res.data?.data ?? []
  } catch {
    skills.value = []
  }
}

async function save() {
  saving.value = true
  try {
    await apiClient.post(`/agents/${props.agentId}/schedules`, form.value)
    emit('saved')
    form.value = { name: '', skill_slug: '', cron_expression: '0 9 * * *', is_active: true }
    visible.value = false
  } finally {
    saving.value = false
  }
}
</script>
