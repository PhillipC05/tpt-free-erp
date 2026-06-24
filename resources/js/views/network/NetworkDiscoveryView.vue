<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Discover People</h1>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Keyword</label>
                    <input
                        v-model="filters.keyword"
                        type="text"
                        placeholder="Name, title, company..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                        @keyup.enter="search"
                    />
                </div>
                <div class="min-w-40">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Industry</label>
                    <select
                        v-model="filters.industry"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                    >
                        <option value="">All industries</option>
                        <option v-for="ind in industries" :key="ind" :value="ind">{{ ind }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Open To</label>
                    <div class="flex flex-wrap gap-3">
                        <label v-for="opt in openToOptions" :key="opt.value" class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" :value="opt.value" v-model="filters.openTo" class="rounded" />
                            {{ opt.label }}
                        </label>
                    </div>
                </div>
                <button
                    @click="search"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                >
                    Search
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <!-- Results grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="profile in profiles"
                :key="profile.id"
                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5"
            >
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold flex-shrink-0 overflow-hidden">
                        <img v-if="profile.avatar_path" :src="`/storage/${profile.avatar_path.replace(/^public\//, '')}`" class="w-12 h-12 object-cover" />
                        <span v-else>{{ initials(profile.name) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <router-link :to="{ name: 'network.profile.public', params: { id: profile.id } }"
                            class="font-semibold text-gray-900 dark:text-gray-100 truncate hover:text-blue-600 dark:hover:text-blue-400">
                            {{ profile.name }}
                        </router-link>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ profile.headline }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ profile.company }}</p>
                    </div>
                </div>

                <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400 mb-4">
                    <p v-if="profile.job_title">{{ profile.job_title }}</p>
                    <p v-if="profile.location">📍 {{ profile.location }}</p>
                </div>

                <div v-if="profile.interests?.length" class="flex flex-wrap gap-1 mb-4">
                    <span
                        v-for="interest in profile.interests?.slice(0, 3)"
                        :key="interest"
                        class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full"
                    >
                        {{ interest }}
                    </span>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="connect(profile.id)"
                        class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        Connect
                    </button>
                    <button
                        @click="follow(profile.id)"
                        class="px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        Follow
                    </button>
                    <button
                        @click="addToCrm(profile.id)"
                        class="px-3 py-1.5 text-xs border border-green-500 text-green-600 dark:text-green-400 rounded-md hover:bg-green-50 dark:hover:bg-green-900/20"
                    >
                        Add to CRM
                    </button>
                </div>
            </div>

            <div v-if="profiles.length === 0 && !loading" class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                No profiles found. Try adjusting your filters.
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

interface Profile {
    id: number;
    name: string;
    headline?: string;
    company?: string;
    job_title?: string;
    location?: string;
    interests?: string[];
}

const profiles = ref<Profile[]>([]);
const loading = ref(false);

const filters = reactive({
    keyword: '',
    industry: '',
    openTo: [] as string[],
});

const industries = [
    'Technology', 'Finance', 'Healthcare', 'Retail', 'Manufacturing',
    'Education', 'Construction', 'Agriculture', 'Hospitality', 'Legal',
    'Marketing', 'Non-Profit', 'Real Estate', 'Transport', 'Consulting',
];

const openToOptions = [
    { value: 'leads', label: 'Leads' },
    { value: 'hiring', label: 'Hiring' },
    { value: 'partnerships', label: 'Partnerships' },
    { value: 'investments', label: 'Investments' },
];

function initials(name: string): string {
    return (name ?? '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
}

async function search() {
    loading.value = true;
    try {
        const res = await apiClient.get('/v1/network/discovery', { params: filters });
        profiles.value = res.data?.data ?? [];
    } catch {
        profiles.value = [];
    } finally {
        loading.value = false;
    }
}

async function connect(userId: number) {
    try {
        await apiClient.post(`/v1/network/connections/request/${userId}`);
        notify.success('Connection request sent');
    } catch {
        notify.error('Failed to send connection request');
    }
}

async function follow(userId: number) {
    try {
        await apiClient.post(`/v1/network/follow/${userId}`);
        notify.success('Now following');
    } catch {
        notify.error('Failed to follow');
    }
}

async function addToCrm(profileId: number) {
    try {
        await apiClient.post(`/v1/network/discovery/${profileId}/add-to-crm`);
        notify.success('Added to CRM');
    } catch {
        notify.error('Failed to add to CRM');
    }
}

onMounted(search);
</script>
