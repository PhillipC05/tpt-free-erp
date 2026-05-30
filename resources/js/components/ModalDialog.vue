<template>
    <Teleport to="body">
        <transition name="modal">
            <div v-if="modelValue" class="fixed inset-0 z-40 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="close" />

                    <div class="relative inline-block bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 id="modal-title" class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ title }}
                            </h3>
                            <button @click="close" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-2">
                            <slot />
                        </div>

                        <div v-if="$slots.footer" class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse gap-2">
                            <slot name="footer" />
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup lang="ts">
defineProps<{
    modelValue: boolean;
    title: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

function close() {
    emit('update:modelValue', false);
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-active > div > div,
.modal-leave-active > div > div {
    transition: transform 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from > div > div {
    transform: scale(0.95);
}
.modal-leave-to > div > div {
    transform: scale(0.95);
}
</style>