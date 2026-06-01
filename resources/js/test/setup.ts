import { config } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, vi } from 'vitest';

// Render Teleport inline and stub transitions for synchronous DOM updates in tests
config.global.stubs = {
    Teleport: { template: '<div><slot /></div>' },
    Transition: { template: '<slot />' },
    TransitionGroup: { template: '<div><slot /></div>' },
};

// Fresh Pinia per test — prevents store state leaking between tests
beforeEach(() => {
    setActivePinia(createPinia());
    // Clear localStorage so auth state doesn't bleed between tests
    localStorage.clear();
    // Reset window.location stub
    vi.stubGlobal('location', { href: '' });
});
