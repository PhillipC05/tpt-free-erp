<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">TPT ERP</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Sign in to your account</p>
            </div>

            <form @submit.prevent="handleLogin" class="mt-8 space-y-6 bg-white dark:bg-gray-800 p-8 rounded-lg shadow">
                <div v-if="error" class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-3 rounded-md text-sm">
                    {{ error }}
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input v-model="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                    </label>
                    <router-link to="/forgot-password" class="text-sm text-blue-600 hover:text-blue-500">
                        Forgot password?
                    </router-link>
                </div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ loading ? 'Signing in...' : 'Sign in' }}
                </button>

                <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                    Don't have an account?
                    <router-link to="/register" class="text-blue-600 hover:text-blue-500 font-medium">Register</router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const remember = ref(false);
const loading = ref(false);
const error = ref('');

async function handleLogin() {
    error.value = '';
    loading.value = true;
    try {
        await authStore.login(email.value, password.value, remember.value);
        const redirect = (route.query.redirect as string) || '/dashboard';
        router.push(redirect);
    } catch (err: unknown) {
        if (err && typeof err === 'object' && 'response' in err) {
            const axiosErr = err as { response?: { data?: { message?: string } } };
            error.value = axiosErr.response?.data?.message || 'Login failed. Please check your credentials.';
        } else {
            error.value = 'An unexpected error occurred.';
        }
    } finally {
        loading.value = false;
    }
}
</script>