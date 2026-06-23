<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Scheduled Reports</h1>
        <p class="text-sm text-gray-500 mt-1">Automatically generate and deliver reports on a schedule</p>
      </div>
      <button @click="showCreate = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
        + Schedule Report
      </button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Loading...</div>
    <div v-else-if="scheduled.length === 0" class="text-center py-12 text-gray-400">No scheduled reports yet.</div>
    <div v-else class="space-y-3">
      <div v-for="report in scheduled" :key="report.id" class="bg-white border border-gray-200 rounded-xl p-5">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-semibold text-gray-900">{{ report.name }}</h3>
            <p class="text-sm text-gray-500">{{ reportTypeLabels[report.report_type] ?? report.report_type }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs px-2 py-0.5 rounded-full" :class="report.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
              {{ report.is_active ? 'Active' : 'Paused' }}
            </span>
            <button @click="deleteReport(report.id)" class="text-sm text-red-400 hover:text-red-600">Delete</button>
          </div>
        </div>
        <div class="mt-3 flex gap-4 text-xs text-gray-400">
          <span>Frequency: {{ report.frequency }}</span>
          <span>Format: {{ report.format?.toUpperCase() }}</span>
          <span v-if="report.delivery_email">Delivery: {{ report.delivery_email }}</span>
          <span v-if="report.next_run_at">Next: {{ new Date(report.next_run_at).toLocaleString() }}</span>
          <span v-if="report.last_run_at">Last: {{ new Date(report.last_run_at).toLocaleString() }}</span>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl w-full max-w-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Schedule New Report</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input v-model="form.name" type="text" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="e.g. Weekly Income Statement" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
            <select v-model="form.report_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
              <option v-for="(label, key) in reportTypeLabels" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
              <select v-model="form.frequency" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                <option value="hourly">Hourly</option>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
              <select v-model="form.format" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                <option value="json">JSON</option>
                <option value="csv">CSV</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Email (optional)</label>
            <input v-model="form.delivery_email" type="email" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="reports@company.com" />
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button @click="createScheduled" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Schedule</button>
          <button @click="showCreate = false" class="px-4 py-2 text-gray-600 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

const scheduled = ref<any[]>([])
const loading = ref(true)
const showCreate = ref(false)
const form = ref({ name: '', report_type: 'income_statement', frequency: 'weekly', format: 'csv', delivery_email: '' })

const reportTypeLabels: Record<string, string> = {
  trial_balance: 'Trial Balance', income_statement: 'Income Statement',
  balance_sheet: 'Balance Sheet', cash_flow: 'Cash Flow',
  hr_attendance: 'HR Attendance', hr_payroll: 'HR Payroll',
  sales_summary: 'Sales Summary', procurement: 'Procurement',
}

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/v1/reports/scheduled')
    scheduled.value = data.data ?? []
  } finally {
    loading.value = false
  }
}

async function createScheduled() {
  await axios.post('/api/v1/reports/scheduled', form.value)
  showCreate.value = false
  form.value = { name: '', report_type: 'income_statement', frequency: 'weekly', format: 'csv', delivery_email: '' }
  load()
}

async function deleteReport(id: number) {
  await axios.delete(`/api/v1/reports/scheduled/${id}`)
  load()
}

onMounted(load)
</script>
