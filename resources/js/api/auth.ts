import apiClient from './axios';
import type { AuthResponse, User } from '@/types';

export const authApi = {
    login: async (email: string, password: string, remember: boolean = false): Promise<AuthResponse> => {
        const response = await apiClient.post('/auth/login', { email, password, remember });
        return response.data;
    },
    register: async (data: { name: string; email: string; password: string; password_confirmation: string }): Promise<AuthResponse> => {
        const response = await apiClient.post('/auth/register', data);
        return response.data;
    },
    logout: async (): Promise<void> => {
        await apiClient.post('/auth/logout');
    },
    user: async (): Promise<User> => {
        const response = await apiClient.get('/user');
        return response.data;
    },
    forgotPassword: async (email: string): Promise<{ message: string }> => {
        const response = await apiClient.post('/auth/forgot-password', { email });
        return response.data;
    },
    resetPassword: async (data: { token: string; email: string; password: string; password_confirmation: string }): Promise<{ message: string }> => {
        const response = await apiClient.post('/auth/reset-password', data);
        return response.data;
    },
};