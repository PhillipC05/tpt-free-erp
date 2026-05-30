import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface Notification {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    message: string;
    timeout?: number;
}

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref<Notification[]>([]);

    function add(notification: Omit<Notification, 'id'>) {
        const id = Math.random().toString(36).substring(7);
        const notif: Notification = { ...notification, id };
        notifications.value.push(notif);

        const duration = notification.timeout || 5000;
        setTimeout(() => {
            remove(id);
        }, duration);
    }

    function remove(id: string) {
        notifications.value = notifications.value.filter((n) => n.id !== id);
    }

    function success(message: string) {
        add({ type: 'success', message });
    }

    function error(message: string) {
        add({ type: 'error', message, timeout: 8000 });
    }

    function warning(message: string) {
        add({ type: 'warning', message, timeout: 6000 });
    }

    function info(message: string) {
        add({ type: 'info', message });
    }

    return {
        notifications,
        add,
        remove,
        success,
        error,
        warning,
        info,
    };
});