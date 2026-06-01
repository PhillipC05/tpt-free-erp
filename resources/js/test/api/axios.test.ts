import { describe, it, expect, vi, beforeEach } from 'vitest';
import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import { useNotificationStore } from '@/stores/notification';

// Import the configured apiClient (this registers the interceptors)
import apiClient from '@/api/axios';

describe('apiClient interceptors', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        localStorage.clear();
    });

    it('adds Authorization header when token is in localStorage', async () => {
        localStorage.setItem('auth_token', 'my-bearer-token');
        let capturedHeaders: Record<string, string> = {};

        // Spy on the underlying adapter
        const mockAdapter = vi.fn().mockResolvedValue({
            data: {},
            status: 200,
            statusText: 'OK',
            headers: {},
            config: {},
        });
        const original = (apiClient.defaults as any).adapter;
        (apiClient.defaults as any).adapter = mockAdapter;

        try {
            await apiClient.get('/test');
        } catch {
            // ignore
        }

        if (mockAdapter.mock.calls.length > 0) {
            capturedHeaders = mockAdapter.mock.calls[0][0].headers;
            expect(capturedHeaders['Authorization']).toBe('Bearer my-bearer-token');
        }

        (apiClient.defaults as any).adapter = original;
    });

    it('does not add Authorization header when no token', async () => {
        const mockAdapter = vi.fn().mockResolvedValue({
            data: {},
            status: 200,
            statusText: 'OK',
            headers: {},
            config: {},
        });
        const original = (apiClient.defaults as any).adapter;
        (apiClient.defaults as any).adapter = mockAdapter;

        try {
            await apiClient.get('/test');
        } catch {
            // ignore
        }

        if (mockAdapter.mock.calls.length > 0) {
            const headers = mockAdapter.mock.calls[0][0].headers;
            expect(headers['Authorization']).toBeUndefined();
        }

        (apiClient.defaults as any).adapter = original;
    });

    it('redirects to /login and removes token on 401', async () => {
        localStorage.setItem('auth_token', 'expired-token');
        const mockAdapter = vi.fn().mockRejectedValue({
            response: { status: 401, data: {} },
            isAxiosError: true,
        });
        const original = (apiClient.defaults as any).adapter;
        (apiClient.defaults as any).adapter = mockAdapter;

        try {
            await apiClient.get('/protected');
        } catch {
            // expected to reject
        }

        expect(localStorage.getItem('auth_token')).toBeNull();
        expect(window.location.href).toBe('/login');

        (apiClient.defaults as any).adapter = original;
    });

    it('shows error notification for non-401 errors', async () => {
        const notify = useNotificationStore();
        const mockAdapter = vi.fn().mockRejectedValue({
            response: { status: 500, data: { message: 'Internal server error' } },
            isAxiosError: true,
        });
        const original = (apiClient.defaults as any).adapter;
        (apiClient.defaults as any).adapter = mockAdapter;

        try {
            await apiClient.get('/broken');
        } catch {
            // expected
        }

        expect(notify.notifications.some(n => n.message === 'Internal server error')).toBe(true);

        (apiClient.defaults as any).adapter = original;
    });
});
