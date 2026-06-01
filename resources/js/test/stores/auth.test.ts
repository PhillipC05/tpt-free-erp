import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAuthStore } from '@/stores/auth';

vi.mock('@/api/auth', () => ({
    authApi: {
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        user: vi.fn(),
    },
}));

import { authApi } from '@/api/auth';

const mockUser = { id: 1, name: 'Test User', email: 'test@example.com', email_verified_at: null, created_at: '', updated_at: '' };

describe('useAuthStore', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts unauthenticated when no token in localStorage', () => {
        const store = useAuthStore();
        expect(store.isAuthenticated).toBe(false);
        expect(store.user).toBeNull();
    });

    it('isAuthenticated is true when token is set', () => {
        localStorage.setItem('auth_token', 'abc123');
        const store = useAuthStore();
        expect(store.isAuthenticated).toBe(true);
    });

    it('login() sets token and user on success', async () => {
        vi.mocked(authApi.login).mockResolvedValue({ user: mockUser, token: 'tok123' });
        const store = useAuthStore();
        await store.login('test@example.com', 'password');
        expect(store.token).toBe('tok123');
        expect(store.user).toEqual(mockUser);
        expect(localStorage.getItem('auth_token')).toBe('tok123');
    });

    it('logout() clears token and user', async () => {
        vi.mocked(authApi.logout).mockResolvedValue(undefined);
        const store = useAuthStore();
        store.$patch({ token: 'tok123' });
        await store.logout();
        expect(store.token).toBeNull();
        expect(store.user).toBeNull();
        expect(localStorage.getItem('auth_token')).toBeNull();
    });

    it('init() fetches user if token exists', async () => {
        localStorage.setItem('auth_token', 'tok123');
        vi.mocked(authApi.user).mockResolvedValue(mockUser);
        const store = useAuthStore();
        await store.init();
        expect(store.user).toEqual(mockUser);
    });

    it('init() clears token if user fetch fails', async () => {
        localStorage.setItem('auth_token', 'bad_token');
        vi.mocked(authApi.user).mockRejectedValue(new Error('Unauthenticated'));
        const store = useAuthStore();
        await store.init();
        expect(store.token).toBeNull();
        expect(localStorage.getItem('auth_token')).toBeNull();
    });
});
