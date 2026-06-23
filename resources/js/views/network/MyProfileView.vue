<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">My Network Profile</h1>

        <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <div v-else class="space-y-6">
            <!-- Profile form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Profile Details</h2>
                <form @submit.prevent="saveProfile" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Headline</label>
                        <input v-model="form.headline" type="text" placeholder="e.g. Sales Director at Acme Corp" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bio</label>
                        <textarea v-model="form.bio" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company</label>
                            <input v-model="form.company" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Job Title</label>
                            <input v-model="form.job_title" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Website</label>
                            <input v-model="form.website" type="url" placeholder="https://" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                            <input v-model="form.location" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50">
                            {{ saving ? 'Saving...' : 'Save Profile' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Discoverability -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Discoverable</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Allow others to find your profile in discovery</p>
                    </div>
                    <!-- Toggle switch -->
                    <button
                        @click="toggleDiscoverable"
                        :class="[
                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                            form.discoverable ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                form.discoverable ? 'translate-x-6' : 'translate-x-1'
                            ]"
                        />
                    </button>
                </div>

                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Open To</h3>
                <div class="flex flex-wrap gap-3">
                    <label v-for="opt in openToOptions" :key="opt.value" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" :value="opt.value" v-model="form.open_to" class="rounded" />
                        {{ opt.label }}
                    </label>
                </div>
            </div>

            <!-- Interests -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Interests</h2>

                <div class="flex flex-wrap gap-2 mb-4">
                    <span
                        v-for="(interest, idx) in form.interests"
                        :key="idx"
                        class="flex items-center gap-1 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-sm rounded-full"
                    >
                        <span class="text-xs text-blue-500 dark:text-blue-400">{{ interest.type }}</span>
                        {{ interest.value }}
                        <button @click="removeInterest(idx)" class="ml-1 text-blue-600 dark:text-blue-300 hover:text-blue-800 dark:hover:text-blue-100">&times;</button>
                    </span>
                </div>

                <div class="flex gap-2">
                    <select v-model="newInterest.type" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                        <option value="industry">Industry</option>
                        <option value="technology">Technology</option>
                        <option value="service">Service</option>
                    </select>
                    <input
                        v-model="newInterest.value"
                        type="text"
                        placeholder="e.g. SaaS, Laravel, Consulting..."
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                        @keyup.enter="addInterest"
                    />
                    <button @click="addInterest" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Add</button>
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

const loading = ref(false);
const saving = ref(false);

const form = reactive({
    headline: '',
    bio: '',
    company: '',
    job_title: '',
    website: '',
    location: '',
    discoverable: false,
    open_to: [] as string[],
    interests: [] as { type: string; value: string }[],
});

const newInterest = reactive({ type: 'industry', value: '' });

const openToOptions = [
    { value: 'leads', label: 'Leads' },
    { value: 'hiring', label: 'Hiring' },
    { value: 'partnerships', label: 'Partnerships' },
    { value: 'investments', label: 'Investments' },
];

async function loadProfile() {
    loading.value = true;
    try {
        const res = await apiClient.get('/v1/network/profile/me');
        const data = res.data?.data ?? {};
        Object.assign(form, data);
    } catch {
        // use defaults
    } finally {
        loading.value = false;
    }
}

async function toggleDiscoverable() {
    form.discoverable = !form.discoverable;
    try {
        if (form.discoverable) {
            await apiClient.post('/v1/network/profile/opt-in');
        } else {
            await apiClient.post('/v1/network/profile/opt-out');
        }
    } catch {
        form.discoverable = !form.discoverable; // revert
        notify.error('Failed to update discoverability');
    }
}

function addInterest() {
    if (!newInterest.value.trim()) return;
    form.interests.push({ type: newInterest.type, value: newInterest.value.trim() });
    newInterest.value = '';
}

function removeInterest(idx: number) {
    form.interests.splice(idx, 1);
}

async function saveProfile() {
    saving.value = true;
    try {
        await apiClient.put('/v1/network/profile', form);
        notify.success('Profile saved');
    } catch {
        notify.error('Failed to save profile');
    } finally {
        saving.value = false;
    }
}

onMounted(loadProfile);
</script>
