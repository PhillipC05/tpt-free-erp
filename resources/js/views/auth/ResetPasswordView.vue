<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Set New Password</h2>
            </div>
            <form @submit.prevent="handleSubmit" class="mt-8 space-y-6 bg-white dark:bg-gray-800 p-8 rounded-lg shadow">
                <div v-if="error" class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-3 rounded-md text-sm">{{ error }}</div>
                <div v-if="message" class="bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 p-3 rounded-md text-sm">{{ message }}</div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input id="email" v-model="email" type="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
                    <input id="password" v-model="password" type="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                    <input id="password_confirmation" v-model="passwordConfirmation" type="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <button type="submit" :disabled="loading" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ loading ? 'Resetting...' : 'Reset Password' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { authApi } from '@/api/auth';

const route = useRoute();
const router = useRouter();

const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const error = ref('');
const message = ref('');

async function handleSubmit() {
    error.value = '';
    message.value = '';
    if (password.value !== passwordConfirmation.value) {
        error.value = 'Passwords do not match.';
        return;
    }
    loading.value = true;
    try {
        const res = await authApi.resetPassword({
            token: route.params.token as string,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        message.value = res.message || 'Password reset successfully.';
        setTimeout(() => router.push('/login'), 2000);
    } catch (err: unknown) {
        if (err && typeof err === 'object' && 'response' in err) {
            const axiosErr = err as { response?: { data?: { message?: string } } };
            error.value = axiosErr.response?.data?.message || 'Reset failed.';
        } else {
            error.value = 'An unexpected error occurred.';
        }
    } finally {
        loading.value = false;
    }
}
</script>