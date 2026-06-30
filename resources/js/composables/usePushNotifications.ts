import { ref } from 'vue';
import api from '@/api/axios';

const isSubscribed = ref(false);
const isSupported = ref(false);
const permission = ref<NotificationPermission>('default');

if (typeof window !== 'undefined' && 'Notification' in window) {
    isSupported.value = true;
    permission.value = Notification.permission;
    isSubscribed.value = Notification.permission === 'granted';
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function subscribe(vapidPublicKey: string): Promise<boolean> {
    if (!isSupported.value) return false;

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });

        const subJson = subscription.toJSON();
        await api.post('/notifications/push/subscribe', {
            endpoint: subJson.endpoint,
            keys: subJson.keys,
        });

        isSubscribed.value = true;
        permission.value = 'granted';
        return true;
    } catch {
        return false;
    }
}

async function unsubscribe(): Promise<boolean> {
    if (!isSupported.value) return false;

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            const endpoint = subscription.endpoint;
            await subscription.unsubscribe();
            await api.delete('/notifications/push/unsubscribe', { data: { endpoint } });
        }

        isSubscribed.value = false;
        permission.value = Notification.permission;
        return true;
    } catch {
        return false;
    }
}

async function requestPermission(): Promise<NotificationPermission> {
    if (!isSupported.value) return 'denied';
    const result = await Notification.requestPermission();
    permission.value = result;
    return result;
}

export function usePushNotifications() {
    return {
        isSubscribed,
        isSupported,
        permission,
        subscribe,
        unsubscribe,
        requestPermission,
    };
}
