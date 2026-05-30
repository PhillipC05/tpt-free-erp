<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Profile</h1>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-2xl">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-full bg-blue-500 flex items-center justify-center text-white text-xl font-bold">
                    {{ initials }}
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ authStore.userData?.name }}</h2>
                    <p class="text-gray-500 dark:text-gray-400">{{ authStore.userData?.email }}</p>
                </div>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ authStore.userData?.name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ authStore.userData?.email }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Member Since</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ formattedDate }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const initials = computed(() => {
    const name = authStore.userData?.name || '';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const formattedDate = computed(() => {
    if (!authStore.userData?.created_at) return '';
    return new Date(authStore.userData.created_at).toLocaleDateString();
});
</script>