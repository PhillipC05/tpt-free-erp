import axios from 'axios';
import { useNotificationStore } from '@/stores/notification';

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        if (status === 401) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            window.location.href = '/login';
            return Promise.reject(error);
        }

        // Show a notification for all other API errors so views don't silently fail
        try {
            const notify = useNotificationStore();
            const message = error.response?.data?.message
                || error.response?.data?.error
                || (status === 403 ? 'Access denied' : 'An unexpected error occurred');
            notify.error(message);
        } catch {
            // Pinia not yet initialized (e.g. during app bootstrap) — skip notification
        }

        return Promise.reject(error);
    }
);

export default apiClient;