<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Notifications</h1>

        <div class="flex items-center gap-4 mb-4">
            <button @click="tab = 'inbox'" :class="tab === 'inbox' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">
                Inbox <span v-if="unreadCount > 0" class="ml-1 px-1.5 py-0.5 text-xs bg-red-500 text-white rounded-full">{{ unreadCount }}</span>
            </button>
            <button @click="tab = 'templates'" :class="tab === 'templates' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Templates</button>
            <button @click="tab = 'preferences'" :class="tab === 'preferences' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-4 py-2 text-sm rounded-md">Preferences</button>
        </div>

        <div v-if="tab === 'inbox'">
            <div class="flex items-center gap-2 mb-4">
                <select v-model="filterChannel" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                    <option value="">All Channels</option>
                    <option value="in_app">In-App</option>
                    <option value="email">Email</option>
                </select>
                <button @click="markAllRead" class="px-3 py-2 text-sm text-blue-600 hover:text-blue-800">Mark All Read</button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div v-if="notifications.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">No notifications</div>
                <div v-for="n in notifications" :key="n.id"
                     @click="selectNotification(n)"
                     class="flex items-start gap-3 p-4 border-b border-gray-100 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                     :class="{ 'bg-blue-50 dark:bg-blue-900/10': !n.read_at }">
                    <div class="w-2 h-2 mt-2 rounded-full flex-shrink-0" :class="n.read_at ? 'bg-gray-300' : 'bg-blue-500'"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ n.subject || 'Notification' }}</span>
                            <span class="text-xs text-gray-500">{{ timeAgo(n.created_at) }}</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate mt-1">{{ n.body }}</p>
                        <span class="inline-block mt-1 text-xs px-1.5 py-0.5 rounded" :class="channelClass(n.channel)">{{ n.channel }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="tab === 'templates'">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Code</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Category</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Channels</th>
                            <th class="px-4 py-3 text-left text-xs text-gray-500">Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="t in templates" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">{{ t.code }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ t.name }}</td>
                            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ t.category }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1">
                                    <span v-for="ch in t.default_channels" :key="ch" class="text-xs px-1.5 py-0.5 rounded" :class="channelClass(ch)">{{ ch }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="t.is_active ? 'text-green-600' : 'text-red-600'" class="text-xs">{{ t.is_active ? 'Yes' : 'No' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="tab === 'preferences'">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-xl">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Default Channel Preferences</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">In-App Notifications</div>
                            <div class="text-xs text-gray-500">Show in the notification bell</div>
                        </div>
                        <button @click="prefForm.in_app_enabled = !prefForm.in_app_enabled" class="relative inline-flex h-6 w-11 rounded-full" :class="prefForm.in_app_enabled ? 'bg-blue-600' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow mt-1 transition" :class="prefForm.in_app_enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Email Notifications</div>
                            <div class="text-xs text-gray-500">Receive notifications via email</div>
                        </div>
                        <button @click="prefForm.email_enabled = !prefForm.email_enabled" class="relative inline-flex h-6 w-11 rounded-full" :class="prefForm.email_enabled ? 'bg-blue-600' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow mt-1 transition" :class="prefForm.email_enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Webhook Notifications</div>
                            <div class="text-xs text-gray-500">Send to configured webhook URL</div>
                        </div>
                        <button @click="prefForm.webhook_enabled = !prefForm.webhook_enabled" class="relative inline-flex h-6 w-11 rounded-full" :class="prefForm.webhook_enabled ? 'bg-blue-600' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow mt-1 transition" :class="prefForm.webhook_enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notification Email</label>
                        <input v-model="prefForm.email_address" type="email" placeholder="user@example.com" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <button @click="savePreferences" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Save Preferences</button>
                </div>
            </div>
        </div>

        <div v-if="selectedNotification" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="selectedNotification = null">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ selectedNotification.subject || 'Notification' }}</h3>
                    <button @click="selectedNotification = null" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">{{ selectedNotification.body }}</p>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="px-1.5 py-0.5 rounded" :class="channelClass(selectedNotification.channel)">{{ selectedNotification.channel }}</span>
                    <span>{{ new Date(selectedNotification.created_at).toLocaleString() }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue';
import apiClient from '@/api/axios';
import type { NotificationTemplateRecord, NotificationMessageRecord, NotificationPreferenceRecord } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const tab = ref('inbox');
const filterChannel = ref('');
const notifications = ref<NotificationMessageRecord[]>([]);
const templates = ref<NotificationTemplateRecord[]>([]);
const preferences = ref<NotificationPreferenceRecord[]>([]);
const unreadCount = ref(0);
const selectedNotification = ref<NotificationMessageRecord | null>(null);

const prefForm = reactive({
    in_app_enabled: true,
    email_enabled: true,
    webhook_enabled: false,
    email_address: '',
});

function channelClass(channel: string): string {
    const map: Record<string, string> = {
        in_app: 'bg-blue-100 text-blue-700',
        email: 'bg-green-100 text-green-700',
        webhook: 'bg-purple-100 text-purple-700',
    };
    return map[channel] ?? 'bg-gray-100 text-gray-700';
}

function timeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    return `${Math.floor(hours / 24)}d ago`;
}

async function loadNotifications() {
    try {
        const params: Record<string, string> = { per_page: '50' };
        if (filterChannel.value) params.channel = filterChannel.value;

        const [notiRes, countRes] = await Promise.all([
            apiClient.get('/notifications-enhanced', { params }),
            apiClient.get('/notifications-enhanced/unread-count'),
        ]);
        notifications.value = notiRes.data?.data ?? [];
        unreadCount.value = countRes.data?.data?.count ?? 0;
    } catch {
        notifications.value = [];
    }
}

async function loadTemplates() {
    try {
        const res = await apiClient.get('/notification-templates');
        templates.value = res.data?.data ?? [];
    } catch {
        templates.value = [];
    }
}

async function loadPreferences() {
    try {
        const res = await apiClient.get('/notifications-enhanced/preferences');
        preferences.value = res.data?.data ?? [];
        if (preferences.value.length > 0) {
            const globalPref = preferences.value.find((p: NotificationPreferenceRecord) => !p.template_code) || preferences.value[0];
            prefForm.in_app_enabled = globalPref.in_app_enabled;
            prefForm.email_enabled = globalPref.email_enabled;
            prefForm.webhook_enabled = globalPref.webhook_enabled;
            prefForm.email_address = globalPref.email_address ?? '';
        }
    } catch { /* ignore */ }
}

async function selectNotification(n: NotificationMessageRecord) {
    selectedNotification.value = n;
    if (!n.read_at) {
        try {
            await apiClient.put(`/notifications-enhanced/${n.id}/read`);
            n.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch { /* ignore */ }
    }
}

async function markAllRead() {
    try {
        await apiClient.put('/notifications-enhanced/read-all');
        unreadCount.value = 0;
        notifications.value.forEach(n => { n.read_at = new Date().toISOString(); });
        notify.success('All notifications marked as read');
    } catch {
        notify.error('Failed to mark notifications');
    }
}

async function savePreferences() {
    try {
        await apiClient.post('/notifications-enhanced/preferences', {
            channels: [
                ...(prefForm.in_app_enabled ? ['in_app'] : []),
                ...(prefForm.email_enabled ? ['email'] : []),
                ...(prefForm.webhook_enabled ? ['webhook'] : []),
            ],
            email_enabled: prefForm.email_enabled,
            in_app_enabled: prefForm.in_app_enabled,
            webhook_enabled: prefForm.webhook_enabled,
            email_address: prefForm.email_address,
        });
        notify.success('Preferences saved');
    } catch {
        notify.error('Failed to save preferences');
    }
}

watch(tab, (t) => {
    if (t === 'inbox') loadNotifications();
    if (t === 'templates') loadTemplates();
    if (t === 'preferences') loadPreferences();
});

watch(filterChannel, loadNotifications);

onMounted(() => {
    loadNotifications();
    loadPreferences();
});
</script>
