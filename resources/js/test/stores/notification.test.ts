import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useNotificationStore } from '@/stores/notification';

describe('useNotificationStore', () => {
    it('starts with no notifications', () => {
        const store = useNotificationStore();
        expect(store.notifications).toHaveLength(0);
    });

    it('success() adds a success notification', () => {
        const store = useNotificationStore();
        store.success('Saved!');
        expect(store.notifications).toHaveLength(1);
        expect(store.notifications[0]).toMatchObject({ type: 'success', message: 'Saved!' });
    });

    it('error() adds an error notification', () => {
        const store = useNotificationStore();
        store.error('Failed!');
        expect(store.notifications[0]).toMatchObject({ type: 'error', message: 'Failed!' });
    });

    it('warning() adds a warning notification', () => {
        const store = useNotificationStore();
        store.warning('Watch out!');
        expect(store.notifications[0]).toMatchObject({ type: 'warning', message: 'Watch out!' });
    });

    it('info() adds an info notification', () => {
        const store = useNotificationStore();
        store.info('FYI');
        expect(store.notifications[0]).toMatchObject({ type: 'info', message: 'FYI' });
    });

    it('remove() removes the specified notification by id', () => {
        const store = useNotificationStore();
        store.success('First');
        store.success('Second');
        const firstId = store.notifications[0].id;
        store.remove(firstId);
        expect(store.notifications).toHaveLength(1);
        expect(store.notifications[0].message).toBe('Second');
    });

    it('each notification has a unique id', () => {
        const store = useNotificationStore();
        store.success('A');
        store.success('B');
        const ids = store.notifications.map(n => n.id);
        expect(new Set(ids).size).toBe(2);
    });

    it('auto-removes notification after timeout', async () => {
        vi.useFakeTimers();
        const store = useNotificationStore();
        store.success('Temporary');
        expect(store.notifications).toHaveLength(1);
        vi.advanceTimersByTime(6000);
        expect(store.notifications).toHaveLength(0);
        vi.useRealTimers();
    });
});
