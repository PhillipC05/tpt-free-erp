<template>
    <!-- Full-screen overlay -->
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('close')" />

        <!-- Sheet / modal -->
        <div class="relative w-full sm:max-w-md bg-white dark:bg-gray-800 rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
            <!-- Drag handle (mobile) -->
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
                <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full" />
            </div>

            <div class="px-6 pb-6 pt-2 sm:pt-6 space-y-5">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Payment</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ props.transaction.transaction_number }}</p>
                    </div>
                    <button @click="emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 touch-manipulation">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Total display -->
                <div class="text-center py-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Amount due</p>
                    <p class="text-4xl font-bold text-gray-900 dark:text-gray-100">${{ fmt(props.transaction.total_amount) }}</p>
                </div>

                <!-- Payment method tabs -->
                <div class="grid grid-cols-4 gap-1.5 bg-gray-100 dark:bg-gray-700 p-1 rounded-xl">
                    <button
                        v-for="m in METHODS"
                        :key="m.value"
                        @click="method = m.value; amount = Number(props.transaction.total_amount)"
                        :class="[
                            'py-2 px-1 rounded-lg text-xs font-semibold transition-all touch-manipulation',
                            method === m.value
                                ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                        ]"
                    >{{ m.label }}</button>
                </div>

                <!-- Amount input -->
                <div>
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide block mb-1.5">Amount tendered</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-bold text-gray-400">$</span>
                        <input
                            v-model.number="amount"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full pl-10 pr-4 py-4 text-2xl font-bold text-center bg-gray-50 dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none text-gray-900 dark:text-gray-100 transition-colors"
                        />
                    </div>
                </div>

                <!-- Quick amounts (cash only) -->
                <div v-if="method === 'cash'" class="grid grid-cols-4 gap-2">
                    <button
                        v-for="q in quickAmounts"
                        :key="q"
                        @click="amount = q"
                        class="py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-200 transition-colors touch-manipulation active:scale-95"
                    >${{ q }}</button>
                </div>

                <!-- Change display (cash only) -->
                <div v-if="method === 'cash' && amount > 0" class="flex items-center justify-between px-4 py-3 rounded-xl" :class="change >= 0 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
                    <span class="text-sm font-medium" :class="change >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-600 dark:text-red-400'">
                        {{ change >= 0 ? 'Change due' : 'Insufficient' }}
                    </span>
                    <span class="text-lg font-bold" :class="change >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-600 dark:text-red-400'">
                        ${{ Math.abs(change).toFixed(2) }}
                    </span>
                </div>

                <!-- Confirm button -->
                <button
                    @click="confirm"
                    :disabled="!canConfirm || processing"
                    class="w-full py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl text-lg font-bold disabled:opacity-40 transition-all active:scale-[0.98] touch-manipulation"
                >
                    <span v-if="processing" class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Processing...
                    </span>
                    <span v-else>Confirm Payment</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { PosTransaction } from '@/types';

const METHODS = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'digital_wallet', label: 'Wallet' },
    { value: 'bank_transfer', label: 'Bank' },
] as const;

const props = defineProps<{
    transaction: PosTransaction;
}>();

const emit = defineEmits<{
    close: [];
    complete: [paymentData: { method: string; amount: number }];
}>();

const method = ref<string>('cash');
const amount = ref<number>(Number(props.transaction.total_amount));
const processing = ref(false);

const total = computed(() => Number(props.transaction.total_amount));

const change = computed(() => amount.value - total.value);

const canConfirm = computed(() => amount.value >= total.value || method.value !== 'cash');

const quickAmounts = computed(() => {
    const t = total.value;
    const candidates = [
        Math.ceil(t),
        Math.ceil(t / 5) * 5,
        Math.ceil(t / 10) * 10,
        Math.ceil(t / 20) * 20,
        Math.ceil(t / 50) * 50,
        Math.ceil(t / 100) * 100,
    ];
    const unique = [...new Set(candidates)].filter(v => v >= t).slice(0, 4);
    while (unique.length < 4) unique.push(unique[unique.length - 1] + 20);
    return unique.slice(0, 4);
});

function fmt(val: number | string | undefined | null): string {
    return Number(val ?? 0).toFixed(2);
}

async function confirm() {
    if (!canConfirm.value || processing.value) return;
    processing.value = true;
    try {
        emit('complete', {
            method: method.value,
            amount: method.value === 'cash' ? amount.value : total.value,
        });
    } finally {
        processing.value = false;
    }
}
</script>
