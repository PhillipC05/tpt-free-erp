<template>
    <div>
        <div class="mb-4">
            <button @click="$router.back()" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">&larr; Back</button>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <div v-else-if="notFound" class="text-center py-16 text-gray-500 dark:text-gray-400">
            <p class="text-lg font-medium">Profile not found</p>
            <p class="text-sm mt-1">This profile is private or does not exist.</p>
        </div>

        <div v-else class="max-w-2xl space-y-6">
            <!-- Profile header -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 rounded-full bg-blue-500 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
                        <img v-if="profile.avatar_path" :src="avatarUrl" alt="Avatar" class="w-16 h-16 rounded-full object-cover" />
                        <span v-else>{{ initials }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ profile.user?.name }}</h1>
                        <p v-if="profile.headline" class="text-gray-600 dark:text-gray-400 text-sm mt-0.5">{{ profile.headline }}</p>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <span v-if="profile.company">{{ profile.company }}</span>
                            <span v-if="profile.job_title">{{ profile.job_title }}</span>
                            <span v-if="profile.location">{{ profile.location }}</span>
                        </div>
                        <a v-if="profile.website" :href="profile.website" target="_blank" rel="noopener noreferrer"
                           class="inline-block mt-2 text-sm text-blue-600 dark:text-blue-400 hover:underline truncate max-w-xs">
                            {{ profile.website }}
                        </a>
                    </div>
                    <div class="flex flex-col gap-2 flex-shrink-0">
                        <button v-if="!isFollowing" @click="follow" :disabled="actionPending"
                            class="px-4 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                            Follow
                        </button>
                        <button v-else @click="unfollow" :disabled="actionPending"
                            class="px-4 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                            Unfollow
                        </button>
                        <button v-if="!isConnected" @click="connect" :disabled="actionPending || connectionRequested"
                            class="px-4 py-1.5 text-sm border border-blue-600 text-blue-600 dark:text-blue-400 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20 disabled:opacity-50">
                            {{ connectionRequested ? 'Requested' : 'Connect' }}
                        </button>
                        <span v-else class="px-4 py-1.5 text-sm text-center text-green-600 dark:text-green-400">
                            Connected
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bio -->
            <div v-if="profile.bio" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-2">About</h2>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ profile.bio }}</p>
            </div>

            <!-- Open To -->
            <div v-if="openToList.length" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">Open To</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="opt in openToList" :key="opt"
                        class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-xs rounded-full">
                        {{ opt }}
                    </span>
                </div>
            </div>

            <!-- Interests -->
            <div v-if="profile.interests?.length" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">Interests</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="interest in profile.interests" :key="interest.value + interest.type"
                        class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-xs rounded-full">
                        <span class="opacity-60">{{ interest.type }}: </span>{{ interest.value }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const route = useRoute();
const notify = useNotificationStore();

const loading        = ref(false);
const notFound       = ref(false);
const actionPending  = ref(false);
const isFollowing    = ref(false);
const isConnected    = ref(false);
const connectionRequested = ref(false);
const profile        = ref<Record<string, any>>({});

const profileId = computed(() => Number(route.params.id));

const initials = computed(() => {
    const name: string = profile.value.user?.name ?? '';
    return name.split(' ').map((n: string) => n[0]).join('').toUpperCase().slice(0, 2) || '?';
});

const avatarUrl = computed(() => profile.value.avatar_path
    ? `/storage/${profile.value.avatar_path.replace(/^public\//, '')}`
    : null
);

const openToList = computed<string[]>(() => profile.value.open_to ?? []);

async function loadProfile() {
    loading.value = true;
    notFound.value = false;
    try {
        const res = await apiClient.get(`/v1/network/profiles/${profileId.value}`);
        profile.value = res.data?.data ?? {};
    } catch (err: any) {
        if (err.response?.status === 404) {
            notFound.value = true;
        }
    } finally {
        loading.value = false;
    }
}

async function follow() {
    actionPending.value = true;
    try {
        await apiClient.post(`/v1/network/follow/${profile.value.user_id}`);
        isFollowing.value = true;
        notify.success('Now following this user');
    } catch {
        notify.error('Failed to follow user');
    } finally {
        actionPending.value = false;
    }
}

async function unfollow() {
    actionPending.value = true;
    try {
        await apiClient.delete(`/v1/network/unfollow/${profile.value.user_id}`);
        isFollowing.value = false;
        notify.success('Unfollowed');
    } catch {
        notify.error('Failed to unfollow user');
    } finally {
        actionPending.value = false;
    }
}

async function connect() {
    actionPending.value = true;
    try {
        await apiClient.post(`/v1/network/connections/request/${profile.value.user_id}`);
        connectionRequested.value = true;
        notify.success('Connection request sent');
    } catch {
        notify.error('Failed to send connection request');
    } finally {
        actionPending.value = false;
    }
}

onMounted(loadProfile);
</script>
