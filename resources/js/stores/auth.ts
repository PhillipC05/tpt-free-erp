import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { User } from '@/types';
import { authApi } from '@/api/auth';
import apiClient from '@/api/axios';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const token = ref<string | null>(localStorage.getItem('auth_token'));
    const loading = ref(false);
    const onboardingPending = ref(false);

    const isAuthenticated = computed(() => !!token.value);
    const userData = computed(() => user.value);

    function hasRole(role: string): boolean {
        if (!user.value?.roles?.length) return true; // permissive: no roles = full access
        return user.value.roles.some(r => r.name === role || r.name === 'admin');
    }

    function userRoles(): string[] {
        return user.value?.roles?.map(r => r.name) ?? [];
    }

    async function init() {
        if (token.value) {
            try {
                loading.value = true;
                user.value = await authApi.user();
            } catch {
                token.value = null;
                localStorage.removeItem('auth_token');
                localStorage.removeItem('auth_user');
            } finally {
                loading.value = false;
            }
        }
    }

    async function checkOnboardingStatus() {
        try {
            const res = await apiClient.get('/v1/onboarding/status');
            onboardingPending.value = (res.data?.data?.status ?? '') === 'pending';
        } catch {
            onboardingPending.value = false;
        }
    }

    async function login(email: string, password: string, remember: boolean = false) {
        loading.value = true;
        try {
            const response = await authApi.login(email, password, remember);
            token.value = response.token;
            user.value = response.user;
            localStorage.setItem('auth_token', response.token);
            localStorage.setItem('auth_user', JSON.stringify(response.user));
            await checkOnboardingStatus();
            return response;
        } finally {
            loading.value = false;
        }
    }

    async function register(data: { name: string; email: string; password: string; password_confirmation: string }) {
        loading.value = true;
        try {
            const response = await authApi.register(data);
            token.value = response.token;
            user.value = response.user;
            localStorage.setItem('auth_token', response.token);
            localStorage.setItem('auth_user', JSON.stringify(response.user));
            return response;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            await authApi.logout();
        } catch {
            // Ignore errors
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
        }
    }

    return {
        user,
        token,
        loading,
        onboardingPending,
        isAuthenticated,
        userData,
        hasRole,
        userRoles,
        init,
        login,
        register,
        logout,
        checkOnboardingStatus,
    };
});