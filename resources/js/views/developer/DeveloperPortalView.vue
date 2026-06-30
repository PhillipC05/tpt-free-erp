<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Developer Portal</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage API keys and monitor usage</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"
            >
                Create API Key
            </button>
        </div>

        <!-- API Keys List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">API Keys</h2>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-12">
                <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="keys.length === 0" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                No API keys yet. Create one to get started.
            </div>

            <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                <div
                    v-for="key in keys"
                    :key="key.id"
                    class="px-6 py-4 flex items-center justify-between"
                >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3">
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ key.name }}</span>
                            <span
                                :class="[
                                    'px-2 py-0.5 text-xs font-medium rounded-full',
                                    key.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                ]"
                            >
                                {{ key.is_active ? 'Active' : 'Revoked' }}
                            </span>
                        </div>
                        <div class="mt-1 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <code class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">{{ key.key_prefix }}****</code>
                            <span v-if="key.last_used_at">Last used {{ formatTimeAgo(key.last_used_at) }}</span>
                            <span v-if="key.expires_at">Expires {{ formatDate(key.expires_at) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 ml-4">
                        <button
                            @click="viewUsage(key)"
                            class="px-3 py-1.5 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors"
                        >
                            Usage
                        </button>
                        <button
                            v-if="key.is_active"
                            @click="revokeKey(key)"
                            class="px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"
                        >
                            Revoke
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Stats (when viewing a key) -->
        <div v-if="selectedKey" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Usage Stats — {{ selectedKey.name }}
                </h2>
                <button
                    @click="selectedKey = null"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    Close
                </button>
            </div>

            <div class="p-6">
                <div v-if="usageLoading" class="flex items-center justify-center py-8">
                    <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
                </div>

                <div v-else-if="usageStats" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Calls</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ usageStats.total_calls.toLocaleString() }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Error Rate</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ usageStats.error_rate }}%</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Period</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ usageStats.period_days }} days</p>
                        </div>
                    </div>

                    <!-- Top Endpoints -->
                    <div v-if="usageEndpoints.length > 0">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Top Endpoints</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 dark:text-gray-400">
                                        <th class="pb-2 font-medium">Endpoint</th>
                                        <th class="pb-2 font-medium">Method</th>
                                        <th class="pb-2 font-medium text-right">Calls</th>
                                        <th class="pb-2 font-medium text-right">Avg Response</th>
                                        <th class="pb-2 font-medium text-right">Errors</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                    <tr v-for="(ep, i) in usageEndpoints" :key="i">
                                        <td class="py-2 text-gray-900 dark:text-gray-100 font-mono text-xs">{{ ep.endpoint }}</td>
                                        <td class="py-2">
                                            <span :class="[
                                                'px-1.5 py-0.5 text-xs font-medium rounded',
                                                methodColor(ep.method)
                                            ]">{{ ep.method }}</span>
                                        </td>
                                        <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ ep.call_count.toLocaleString() }}</td>
                                        <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ ep.avg_response_ms ? Math.round(ep.avg_response_ms) + 'ms' : '-' }}</td>
                                        <td class="py-2 text-right">
                                            <span :class="ep.error_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">
                                                {{ ep.error_count }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showCreateModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Create API Key</h3>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="e.g. My Integration"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rate Limit (per minute)</label>
                        <input
                            v-model.number="createForm.rate_limit_per_minute"
                            type="number"
                            min="1"
                            max="1000"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expires At (optional)</label>
                        <input
                            v-model="createForm.expires_at"
                            type="datetime-local"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
                    <button
                        @click="showCreateModal = false"
                        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        Cancel
                    </button>
                    <button
                        @click="createKey"
                        :disabled="!createForm.name || creating"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ creating ? 'Creating...' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Key Created Modal -->
        <div v-if="newKeyData" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="newKeyData = null">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">API Key Created</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                        <p class="text-sm text-yellow-800 dark:text-yellow-300">Save this key now. It will not be shown again.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Your API Key</label>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded text-sm text-gray-900 dark:text-gray-100 break-all">{{ newKeyData.key }}</code>
                            <button
                                @click="copyKey"
                                class="px-3 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md"
                            >
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        @click="newKeyData = null"
                        class="w-full px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                    >
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/api/axios';

interface ApiKeyItem {
    id: number;
    name: string;
    key_prefix: string;
    is_active: boolean;
    abilities: string[] | null;
    rate_limit_per_minute: number;
    last_used_at: string | null;
    expires_at: string | null;
}

interface UsageEndpoint {
    endpoint: string;
    method: string;
    call_count: number;
    avg_response_ms: number | null;
    error_count: number;
}

const keys = ref<ApiKeyItem[]>([]);
const loading = ref(true);
const showCreateModal = ref(false);
const creating = ref(false);
const newKeyData = ref<{ key: string; name: string } | null>(null);

const createForm = ref({
    name: '',
    rate_limit_per_minute: 60,
    expires_at: '',
});

const selectedKey = ref<ApiKeyItem | null>(null);
const usageLoading = ref(false);
const usageStats = ref<{ total_calls: number; error_rate: number; period_days: number } | null>(null);
const usageEndpoints = ref<UsageEndpoint[]>([]);

async function fetchKeys() {
    loading.value = true;
    try {
        const res = await api.get('/developer/keys');
        keys.value = res.data?.data ?? [];
    } catch {
        // silently ignore
    } finally {
        loading.value = false;
    }
}

async function createKey() {
    creating.value = true;
    try {
        const payload: Record<string, unknown> = {
            name: createForm.value.name,
            rate_limit_per_minute: createForm.value.rate_limit_per_minute,
        };
        if (createForm.value.expires_at) {
            payload.expires_at = new Date(createForm.value.expires_at).toISOString();
        }
        const res = await api.post('/developer/keys', payload);
        newKeyData.value = res.data?.data;
        showCreateModal.value = false;
        createForm.value = { name: '', rate_limit_per_minute: 60, expires_at: '' };
        fetchKeys();
    } catch {
        // silently ignore
    } finally {
        creating.value = false;
    }
}

async function revokeKey(key: ApiKeyItem) {
    if (!confirm(`Revoke API key "${key.name}"?`)) return;
    try {
        await api.delete(`/developer/keys/${key.id}`);
        key.is_active = false;
    } catch {
        // silently ignore
    }
}

async function viewUsage(key: ApiKeyItem) {
    selectedKey.value = key;
    usageLoading.value = true;
    try {
        const [statsRes, endpointsRes] = await Promise.all([
            api.get(`/developer/keys/${key.id}/usage`),
            api.get(`/developer/keys/${key.id}/usage/endpoints`),
        ]);
        usageStats.value = statsRes.data?.data;
        usageEndpoints.value = endpointsRes.data?.data?.endpoints ?? [];
    } catch {
        // silently ignore
    } finally {
        usageLoading.value = false;
    }
}

function copyKey() {
    if (newKeyData.value?.key) {
        navigator.clipboard.writeText(newKeyData.value.key);
    }
}

function methodColor(method: string): string {
    const m = method.toUpperCase();
    if (m === 'GET') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
    if (m === 'POST') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
    if (m === 'PUT' || m === 'PATCH') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    if (m === 'DELETE') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400';
}

function formatTimeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    return `${Math.floor(hrs / 24)}d ago`;
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString();
}

onMounted(fetchKeys);
</script>
