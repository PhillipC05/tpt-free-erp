<template>
    <div class="flex flex-col h-full bg-white dark:bg-gray-800">
        <!-- Cart header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <h2 class="font-bold text-gray-900 dark:text-gray-100 text-base">Order</h2>
            <span v-if="items.length" class="text-xs text-gray-500 dark:text-gray-400">{{ items.length }} item{{ items.length !== 1 ? 's' : '' }}</span>
        </div>

        <!-- Items list -->
        <div class="flex-1 overflow-y-auto">
            <div v-if="!items.length" class="flex flex-col items-center justify-center h-full gap-3 text-gray-400 py-12">
                <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <p class="text-sm">Tap a product to add it</p>
            </div>

            <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                <div v-for="item in items" :key="item.id" class="flex items-center gap-2 px-4 py-3">
                    <!-- Description -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ item.description }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">${{ Number(item.unit_price).toFixed(2) }} each</p>
                    </div>

                    <!-- Qty controls -->
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button
                            @click="emit('adjust', item, -1)"
                            :disabled="props.loading"
                            class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 active:scale-90 transition-all touch-manipulation disabled:opacity-40 text-base leading-none font-medium"
                        >−</button>
                        <span class="w-7 text-center text-sm font-semibold text-gray-900 dark:text-gray-100 tabular-nums">{{ item.quantity }}</span>
                        <button
                            @click="emit('adjust', item, +1)"
                            :disabled="props.loading"
                            class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 active:scale-90 transition-all touch-manipulation disabled:opacity-40 text-base leading-none font-medium"
                        >+</button>
                    </div>

                    <!-- Line total -->
                    <span class="w-14 text-right text-sm font-semibold text-gray-900 dark:text-gray-100 tabular-nums flex-shrink-0">
                        ${{ Number(item.line_total).toFixed(2) }}
                    </span>

                    <!-- Remove -->
                    <button
                        @click="emit('remove', item.id)"
                        :disabled="props.loading"
                        class="text-gray-300 hover:text-red-500 dark:text-gray-600 dark:hover:text-red-400 transition-colors flex-shrink-0 touch-manipulation disabled:opacity-40"
                        aria-label="Remove item"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Totals + actions -->
        <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700">
            <!-- Totals -->
            <div class="px-4 py-3 space-y-1">
                <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Subtotal</span>
                    <span>${{ fmt(props.transaction?.subtotal) }}</span>
                </div>
                <div v-if="Number(props.transaction?.tax_amount) > 0" class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Tax</span>
                    <span>${{ fmt(props.transaction?.tax_amount) }}</span>
                </div>
                <div v-if="Number(props.transaction?.discount_amount) > 0" class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Discount</span>
                    <span>−${{ fmt(props.transaction?.discount_amount) }}</span>
                </div>
                <div class="flex justify-between text-base font-bold text-gray-900 dark:text-gray-100 pt-1 border-t border-gray-100 dark:border-gray-700">
                    <span>Total</span>
                    <span>${{ fmt(props.transaction?.total_amount) }}</span>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="px-4 pb-4 space-y-2">
                <button
                    v-if="props.transaction?.status === 'open'"
                    @click="emit('checkout')"
                    :disabled="!items.length || props.loading"
                    class="w-full py-3 bg-green-600 text-white rounded-xl font-bold text-base hover:bg-green-700 active:scale-[0.98] transition-all disabled:opacity-40 touch-manipulation"
                >
                    Charge ${{ fmt(props.transaction?.total_amount) }}
                </button>

                <div v-else-if="props.transaction?.status === 'completed'" class="py-3 bg-green-50 dark:bg-green-900/20 rounded-xl text-center">
                    <p class="text-green-700 dark:text-green-300 font-semibold text-sm">Sale Complete</p>
                </div>

                <div v-else-if="props.transaction?.status === 'voided'" class="py-3 bg-red-50 dark:bg-red-900/20 rounded-xl text-center">
                    <p class="text-red-600 dark:text-red-400 font-semibold text-sm">Transaction Voided</p>
                </div>

                <button
                    v-if="props.transaction?.status === 'open' && items.length > 0"
                    @click="emit('void')"
                    :disabled="props.loading"
                    class="w-full py-2 text-sm text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors touch-manipulation disabled:opacity-40"
                >
                    Void Transaction
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { PosTransaction, PosTransactionItem } from '@/types';

const props = defineProps<{
    transaction: PosTransaction | null;
    loading: boolean;
}>();

const emit = defineEmits<{
    remove: [itemId: number];
    adjust: [item: PosTransactionItem, delta: number];
    checkout: [];
    void: [];
}>();

const items = computed(() => props.transaction?.items ?? []);

function fmt(val: number | string | undefined | null): string {
    return Number(val ?? 0).toFixed(2);
}
</script>
