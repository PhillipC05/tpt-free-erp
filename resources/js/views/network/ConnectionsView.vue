<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Connections</h1>

        <!-- Tabs -->
        <div class="flex gap-1 mb-6 border-b border-gray-200 dark:border-gray-700">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="activeTab = tab.value"
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    activeTab === tab.value
                        ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                ]"
            >
                {{ tab.label }}
            </button>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="conn in currentList"
                :key="conn.id"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4"
            >
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                    {{ initials(conn.name) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ conn.name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ conn.headline }}</p>
                </div>

                <!-- Pending incoming actions -->
                <div v-if="activeTab === 'pending' && conn.direction === 'incoming'" class="flex gap-2">
                    <button @click="accept(conn.id)" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700">Accept</button>
                    <button @click="decline(conn.id)" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">Decline</button>
                </div>
                <div v-else-if="activeTab === 'pending' && conn.direction === 'outgoing'">
                    <span class="text-xs text-gray-400 dark:text-gray-500">Pending</span>
                </div>
            </div>

            <div v-if="currentList.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                {{ activeTab === 'connections' ? 'No connections yet.' : 'No pending requests.' }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

interface Connection {
    id: number;
    name: string;
    headline?: string;
    direction?: 'incoming' | 'outgoing';
}

const activeTab = ref('connections');
const loading = ref(false);
const connections = ref<Connection[]>([]);
const pending = ref<Connection[]>([]);

const tabs = [
    { label: 'Connections', value: 'connections' },
    { label: 'Pending', value: 'pending' },
];

const currentList = computed(() => activeTab.value === 'connections' ? connections.value : pending.value);

function initials(name: string): string {
    return (name ?? '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
}

async function load() {
    loading.value = true;
    try {
        const [connRes, pendRes] = await Promise.all([
            apiClient.get('/v1/network/connections'),
            apiClient.get('/v1/network/connections/pending'),
        ]);
        connections.value = connRes.data?.data ?? [];
        pending.value = pendRes.data?.data ?? [];
    } catch {
        connections.value = [];
        pending.value = [];
    } finally {
        loading.value = false;
    }
}

async function accept(id: number) {
    try {
        await apiClient.post(`/v1/network/connections/accept/${id}`);
        notify.success('Connection accepted');
        await load();
    } catch {
        notify.error('Failed to accept connection');
    }
}

async function decline(id: number) {
    try {
        await apiClient.post(`/v1/network/connections/decline/${id}`);
        notify.success('Connection declined');
        await load();
    } catch {
        notify.error('Failed to decline connection');
    }
}

onMounted(load);
</script>
