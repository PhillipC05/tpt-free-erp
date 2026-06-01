<template>
    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Company Settings</h1>

        <div v-if="loading" class="flex items-center justify-center py-16">
            <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <form v-else @submit.prevent="save" class="space-y-6">
            <!-- Company info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Company Information</h2>
                </div>
                <div class="px-6 py-4 grid gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Name</label>
                        <input type="text" v-model="form.company_name" class="input" placeholder="Acme Corp" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" v-model="form.company_email" class="input" placeholder="hello@acme.com" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                            <input type="text" v-model="form.company_phone" class="input" placeholder="+1 555 0100" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                        <textarea v-model="form.company_address" rows="2" class="input resize-none" placeholder="123 Main St, City, State, ZIP"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                        <input type="url" v-model="form.company_website" class="input" placeholder="https://acme.com" />
                    </div>
                </div>
            </div>

            <!-- Locale & finance -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                <div class="px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Locale & Finance</h2>
                </div>
                <div class="px-6 py-4 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Currency</label>
                        <select v-model="form.default_currency" class="input">
                            <option v-for="c in currencies" :key="c.code" :value="c.code">{{ c.code }} — {{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fiscal Year Start</label>
                        <select v-model="form.fiscal_year_start" class="input">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                        <select v-model="form.timezone" class="input">
                            <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Format</label>
                        <select v-model="form.date_format" class="input">
                            <option value="YYYY-MM-DD">YYYY-MM-DD (ISO)</option>
                            <option value="MM/DD/YYYY">MM/DD/YYYY (US)</option>
                            <option value="DD/MM/YYYY">DD/MM/YYYY (EU)</option>
                            <option value="DD.MM.YYYY">DD.MM.YYYY</option>
                        </select>
                    </div>
                </div>
            </div>

            <div v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</div>
            <div v-if="saved" class="text-sm text-green-600 dark:text-green-400">Settings saved successfully.</div>

            <div class="flex justify-end">
                <button type="submit" :disabled="saving" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm font-medium rounded-md transition-colors">
                    {{ saving ? 'Saving...' : 'Save Settings' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api/axios';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const saved = ref(false);

const form = ref({
    company_name: '',
    company_email: '',
    company_phone: '',
    company_address: '',
    company_website: '',
    default_currency: 'USD',
    timezone: 'UTC',
    fiscal_year_start: '1',
    date_format: 'YYYY-MM-DD',
});

const currencies = [
    { code: 'USD', name: 'US Dollar' },
    { code: 'EUR', name: 'Euro' },
    { code: 'GBP', name: 'British Pound' },
    { code: 'JPY', name: 'Japanese Yen' },
    { code: 'CAD', name: 'Canadian Dollar' },
    { code: 'AUD', name: 'Australian Dollar' },
    { code: 'CHF', name: 'Swiss Franc' },
    { code: 'CNY', name: 'Chinese Yuan' },
    { code: 'INR', name: 'Indian Rupee' },
    { code: 'BRL', name: 'Brazilian Real' },
    { code: 'MXN', name: 'Mexican Peso' },
    { code: 'ZAR', name: 'South African Rand' },
    { code: 'SGD', name: 'Singapore Dollar' },
    { code: 'HKD', name: 'Hong Kong Dollar' },
    { code: 'NOK', name: 'Norwegian Krone' },
    { code: 'SEK', name: 'Swedish Krona' },
    { code: 'DKK', name: 'Danish Krone' },
    { code: 'NZD', name: 'New Zealand Dollar' },
];

const months = [
    { value: '1', label: 'January' },
    { value: '2', label: 'February' },
    { value: '3', label: 'March' },
    { value: '4', label: 'April' },
    { value: '5', label: 'May' },
    { value: '6', label: 'June' },
    { value: '7', label: 'July' },
    { value: '8', label: 'August' },
    { value: '9', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

const timezones = [
    'UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
    'America/Toronto', 'America/Vancouver', 'America/Sao_Paulo', 'America/Mexico_City',
    'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Europe/Madrid', 'Europe/Rome',
    'Europe/Amsterdam', 'Europe/Stockholm', 'Europe/Oslo', 'Europe/Helsinki',
    'Asia/Dubai', 'Asia/Kolkata', 'Asia/Singapore', 'Asia/Tokyo', 'Asia/Shanghai',
    'Asia/Seoul', 'Asia/Hong_Kong', 'Australia/Sydney', 'Australia/Melbourne',
    'Pacific/Auckland', 'Africa/Johannesburg', 'Africa/Lagos',
];

onMounted(async () => {
    try {
        const res = await api.get('/settings');
        const data = res.data?.data ?? {};
        Object.assign(form.value, data);
    } catch {
        // use defaults
    } finally {
        loading.value = false;
    }
});

async function save() {
    saving.value = true;
    error.value = '';
    saved.value = false;
    try {
        await api.put('/settings', form.value);
        saved.value = true;
        setTimeout(() => { saved.value = false; }, 3000);
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? 'Failed to save settings.';
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
.input {
    @apply w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500;
}
</style>
