<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Following &amp; Followers</h1>

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
                v-for="user in currentList"
                :key="user.id"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4"
            >
                <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                    {{ initials(user.name) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ user.headline }}</p>
                </div>
                <button
                    v-if="activeTab === 'following'"
                    @click="unfollow(user.id)"
                    class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    Unfollow
                </button>
            </div>

            <div v-if="currentList.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                {{ activeTab === 'following' ? 'Not following anyone yet.' : 'No followers yet.' }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

interface UserSummary {
    id: number;
    name: string;
    headline?: string;
}

const activeTab = ref('following');
const loading = ref(false);
const following = ref<UserSummary[]>([]);
const followers = ref<UserSummary[]>([]);

const tabs = [
    { label: 'Following', value: 'following' },
    { label: 'Followers', value: 'followers' },
];

const currentList = computed(() => activeTab.value === 'following' ? following.value : followers.value);

function initials(name: string): string {
    return (name ?? '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
}

async function load() {
    loading.value = true;
    try {
        const [followingRes, followersRes] = await Promise.all([
            apiClient.get('/v1/network/following'),
            apiClient.get('/v1/network/followers'),
        ]);
        following.value = followingRes.data?.data ?? [];
        followers.value = followersRes.data?.data ?? [];
    } catch {
        following.value = [];
        followers.value = [];
    } finally {
        loading.value = false;
    }
}

async function unfollow(userId: number) {
    try {
        await apiClient.delete(`/v1/network/follow/${userId}`);
        following.value = following.value.filter(u => u.id !== userId);
        notify.success('Unfollowed');
    } catch {
        notify.error('Failed to unfollow');
    }
}

onMounted(load);
</script>
