<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Reset Password</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Enter your email to receive a reset link</p>
            </div>

            <form @submit.prevent="handleSubmit" class="mt-8 space-y-6 bg-white dark:bg-gray-800 p-8 rounded-lg shadow">
                <div v-if="message" class="bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 p-3 rounded-md text-sm">{{ message }}</div>
                <div v-if="error" class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-3 rounded-md text-sm">{{ error }}</div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input id="email" v-model="email" type="email" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500" />
                </div>

                <button type="submit" :disabled="loading"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ loading ? 'Sending...' : 'Send Reset Link' }}
                </button>

                <div class="text-center text-sm">
                    <router-link to="/login" class="text-blue-600 hover:text-blue-500">Back to login</router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { authApi } from '@/api/auth';

const email = ref('');
const loading = ref(false);
const error = ref('');
const message = ref('');

async function handleSubmit() {
    error.value = '';
    message.value = '';
    loading.value = true;
    try {
        const res = await authApi.forgotPassword(email.value);
        message.value = res.message || 'Check your email for the reset link.';
    } catch (err: unknown) {
        if (err && typeof err === 'object' && 'response' in err) {
            const axiosErr = err as { response?: { data?: { message?: string } } };
            error.value = axiosErr.response?.data?.message || 'Failed to send reset link.';
        } else {
            error.value = 'An unexpected error occurred.';
        }
    } finally {
        loading.value = false;
    }
}
</script>