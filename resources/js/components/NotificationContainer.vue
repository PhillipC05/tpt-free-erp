<template>
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2">
        <transition-group name="notification">
            <div
                v-for="notif in notifications"
                :key="notif.id"
                :class="[
                    'px-4 py-3 rounded-lg shadow-lg text-white text-sm max-w-sm flex items-center gap-3',
                    typeClasses[notif.type]
                ]"
            >
                <span class="flex-1">{{ notif.message }}</span>
                <button
                    @click="store.remove(notif.id)"
                    class="text-white/80 hover:text-white flex-shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </transition-group>
    </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '@/stores/notification';

const store = useNotificationStore();
const { notifications } = storeToRefs(store);

const typeClasses: Record<string, string> = {
    success: 'bg-green-600',
    error: 'bg-red-600',
    warning: 'bg-yellow-600',
    info: 'bg-blue-600',
};
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
    transition: all 0.3s ease;
}
.notification-enter-from {
    opacity: 0;
    transform: translateX(100px);
}
.notification-leave-to {
    opacity: 0;
    transform: translateX(100px);
}
</style>