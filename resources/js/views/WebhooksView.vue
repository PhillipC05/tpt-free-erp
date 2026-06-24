<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Webhooks</h1>
            <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                + New Webhook
            </button>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <div v-else-if="!webhooks.length" class="text-center py-16 text-gray-500 dark:text-gray-400">
            <p class="text-lg font-medium">No webhooks configured</p>
            <p class="text-sm mt-1">Create a webhook to receive real-time events from this ERP.</p>
        </div>

        <div v-else class="space-y-4">
            <div v-for="hook in webhooks" :key="hook.id"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span :class="hook.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
                                class="text-xs font-medium px-2 py-0.5 rounded-full">
                                {{ hook.is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span v-if="hook.failure_count >= 5" class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                {{ hook.failure_count }} failures
                            </span>
                        </div>
                        <p class="text-sm font-mono text-gray-700 dark:text-gray-300 truncate">{{ hook.url }}</p>
                        <!-- Event badges -->
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <span v-for="ev in hook.events" :key="ev"
                                class="text-xs px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                                {{ ev }}
                            </span>
                        </div>
                        <p v-if="hook.last_triggered_at" class="text-xs text-gray-400 mt-1">
                            Last triggered: {{ formatDate(hook.last_triggered_at) }}
                        </p>
                    </div>
                    <div class="flex gap-2 flex-shrink-0">
                        <button @click="testHook(hook)" :disabled="testing === hook.id"
                            class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                            {{ testing === hook.id ? 'Sending…' : 'Test' }}
                        </button>
                        <button @click="editHook(hook)"
                            class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                            Edit
                        </button>
                        <button @click="deleteHook(hook)"
                            class="px-3 py-1.5 text-xs border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 rounded hover:bg-red-50 dark:hover:bg-red-900/20">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ editing ? 'Edit Webhook' : 'New Webhook' }}</h2>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl leading-none">&times;</button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Endpoint URL</label>
                        <input v-model="form.url" type="url" placeholder="https://your-server.com/hook"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Secret (HMAC key)</label>
                        <input v-model="form.secret" type="text" placeholder="Leave blank to auto-generate"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>

                    <!-- Event filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Events to subscribe</label>
                        <div class="grid grid-cols-2 gap-2 max-h-56 overflow-y-auto pr-1">
                            <label v-for="ev in AVAILABLE_EVENTS" :key="ev" class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" :value="ev" v-model="form.events" class="rounded" />
                                <span class="font-mono">{{ ev }}</span>
                            </label>
                        </div>
                        <p v-if="!form.events.length" class="text-xs text-red-500 mt-1">Select at least one event.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_active" v-model="form.is_active" class="rounded" />
                        <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button @click="closeModal" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button @click="saveHook" :disabled="saving || !form.events.length"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ saving ? 'Saving…' : (editing ? 'Update' : 'Create') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

const AVAILABLE_EVENTS = [
    'finance.*', 'finance.transaction_created', 'finance.transaction_updated', 'finance.transaction_deleted',
    'inventory.*', 'inventory.product_created', 'inventory.product_updated', 'inventory.product_deleted',
    'inventory.stock_movement_created',
    'sales.*', 'sales.order_created', 'sales.order_updated', 'sales.order_deleted',
    'sales.invoice_created', 'sales.invoice_updated', 'sales.invoice_deleted',
    'hr.*', 'procurement.*', 'manufacturing.*', 'projects.*', 'quality.*',
    'assets.*', 'field_service.*', 'lms.*', 'marketing.*',
];

const loading  = ref(false);
const saving   = ref(false);
const testing  = ref<number | null>(null);
const showModal = ref(false);
const editing   = ref<number | null>(null);
const webhooks  = ref<any[]>([]);

const form = reactive({
    url: '',
    secret: '',
    events: [] as string[],
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.url = '';
    form.secret = '';
    form.events = [];
    form.is_active = true;
    showModal.value = true;
}

function editHook(hook: any) {
    editing.value = hook.id;
    form.url = hook.url;
    form.secret = '';
    form.events = [...(hook.events ?? [])];
    form.is_active = hook.is_active;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function formatDate(d: string) {
    return new Date(d).toLocaleString();
}

async function load() {
    loading.value = true;
    try {
        const res = await apiClient.get('/v1/webhooks');
        webhooks.value = res.data?.data ?? res.data ?? [];
    } catch {
        notify.error('Failed to load webhooks');
    } finally {
        loading.value = false;
    }
}

async function saveHook() {
    if (!form.events.length) return;
    saving.value = true;
    try {
        const payload: Record<string, any> = {
            url: form.url,
            events: form.events,
            is_active: form.is_active,
        };
        if (form.secret) payload.secret = form.secret;

        if (editing.value) {
            await apiClient.put(`/v1/webhooks/${editing.value}`, payload);
            notify.success('Webhook updated');
        } else {
            await apiClient.post('/v1/webhooks', payload);
            notify.success('Webhook created');
        }
        closeModal();
        await load();
    } catch {
        notify.error('Failed to save webhook');
    } finally {
        saving.value = false;
    }
}

async function deleteHook(hook: any) {
    if (!confirm(`Delete webhook for ${hook.url}?`)) return;
    try {
        await apiClient.delete(`/v1/webhooks/${hook.id}`);
        notify.success('Webhook deleted');
        await load();
    } catch {
        notify.error('Failed to delete webhook');
    }
}

async function testHook(hook: any) {
    testing.value = hook.id;
    try {
        await apiClient.post(`/v1/webhooks/${hook.id}/test`);
        notify.success('Test delivery sent');
    } catch {
        notify.error('Test delivery failed');
    } finally {
        testing.value = null;
    }
}

onMounted(load);
</script>
