<template>
    <div class="h-full flex flex-col bg-gray-100 dark:bg-gray-900">

        <!-- ── Terminal selector overlay ─────────────────────────────────── -->
        <div v-if="!activeTerminal" class="flex-1 flex items-center justify-center p-6">
            <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 space-y-6">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/30 mb-4">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100">Open Register</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Select a terminal to begin</p>
                </div>

                <div v-if="terminalsLoading" class="flex justify-center py-4">
                    <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
                </div>

                <div v-else-if="terminals.length === 0" class="text-center text-sm text-gray-500 dark:text-gray-400 py-4">
                    No active terminals found. <router-link to="/pos/terminals" class="text-blue-600 hover:underline">Add one</router-link>
                </div>

                <div v-else class="space-y-3">
                    <div class="space-y-2">
                        <button
                            v-for="t in terminals"
                            :key="t.id"
                            @click="selectedTerminalId = t.id"
                            :class="[
                                'w-full flex items-center gap-4 px-4 py-3 rounded-xl border-2 transition-all text-left touch-manipulation',
                                selectedTerminalId === t.id
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                            ]"
                        >
                            <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-gray-100 text-sm">{{ t.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t.terminal_code }}</p>
                            </div>
                            <div v-if="selectedTerminalId === t.id" class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </div>

                    <button
                        @click="openTransaction"
                        :disabled="!selectedTerminalId || openingTransaction"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm disabled:opacity-40 transition-all active:scale-[0.98] touch-manipulation"
                    >
                        <span v-if="openingTransaction" class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Opening...
                        </span>
                        <span v-else>Start Session</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Active register ────────────────────────────────────────────── -->
        <template v-else>
            <!-- POS Header bar -->
            <header class="flex-shrink-0 flex items-center gap-3 px-4 h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <!-- Terminal info -->
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></div>
                    <span class="font-semibold text-sm text-gray-900 dark:text-gray-100 truncate">{{ activeTerminal.name }}</span>
                    <span v-if="currentTransaction" class="hidden sm:inline text-xs text-gray-400 dark:text-gray-500 font-mono">
                        {{ currentTransaction.transaction_number }}
                    </span>
                </div>

                <!-- Transaction status badge -->
                <span
                    v-if="currentTransaction"
                    :class="[
                        'hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0',
                        currentTransaction.status === 'open' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' :
                        currentTransaction.status === 'completed' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' :
                        'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
                    ]"
                >{{ currentTransaction.status }}</span>

                <div class="flex-1" />

                <!-- New sale button (after completion/void) -->
                <button
                    v-if="currentTransaction?.status !== 'open'"
                    @click="startNewSale"
                    class="px-3 py-1.5 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:scale-95 transition-all touch-manipulation"
                >
                    New Sale
                </button>

                <!-- Exit -->
                <router-link
                    to="/pos/transactions"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors touch-manipulation"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="hidden sm:inline">Exit</span>
                </router-link>
            </header>

            <!-- Main register body -->
            <div class="flex-1 flex overflow-hidden">

                <!-- Product grid (left / top) -->
                <div class="flex-1 overflow-hidden">
                    <ProductGrid
                        :products="products"
                        :loading="productsLoading"
                        :transaction-id="currentTransaction?.id ?? null"
                        :adding="addingProduct"
                        @add="addProductToCart"
                    />
                </div>

                <!-- Cart panel — sidebar on md+, hidden on mobile -->
                <div class="hidden md:flex w-80 xl:w-96 border-l border-gray-200 dark:border-gray-700 overflow-hidden flex-col">
                    <CartPanel
                        :transaction="currentTransaction"
                        :loading="cartLoading"
                        @remove="removeItem"
                        @adjust="adjustQty"
                        @checkout="showPayment = true"
                        @void="promptVoid"
                    />
                </div>
            </div>

            <!-- Mobile: floating cart bar -->
            <div class="md:hidden flex-shrink-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                <button
                    v-if="currentTransaction?.status === 'open'"
                    @click="cartDrawerOpen = true"
                    class="w-full flex items-center gap-3 py-3 px-4 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-xl font-semibold touch-manipulation active:scale-[0.98] transition-all"
                >
                    <!-- Cart icon + count badge -->
                    <div class="relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span v-if="itemCount > 0" class="absolute -top-2 -right-2 w-4 h-4 rounded-full bg-blue-500 text-white text-xs flex items-center justify-center font-bold">{{ itemCount }}</span>
                    </div>
                    <span class="flex-1 text-left">{{ itemCount ? `${itemCount} item${itemCount !== 1 ? 's' : ''}` : 'Cart is empty' }}</span>
                    <span class="font-bold">${{ fmt(currentTransaction?.total_amount) }}</span>
                </button>

                <div v-else-if="currentTransaction?.status === 'completed'" class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-green-600 dark:text-green-400">Sale Complete!</p>
                    <button @click="startNewSale" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold touch-manipulation">New Sale</button>
                </div>
                <div v-else-if="currentTransaction?.status === 'voided'" class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-red-500 dark:text-red-400">Transaction Voided</p>
                    <button @click="startNewSale" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold touch-manipulation">New Sale</button>
                </div>
            </div>

            <!-- Mobile: cart drawer -->
            <Transition name="slide-up">
                <div v-if="cartDrawerOpen" class="md:hidden fixed inset-0 z-40 flex flex-col justify-end">
                    <div class="absolute inset-0 bg-black/50" @click="cartDrawerOpen = false" />
                    <div class="relative bg-white dark:bg-gray-800 rounded-t-3xl flex flex-col max-h-[85vh]">
                        <!-- Handle -->
                        <div class="flex justify-center pt-3 pb-2 flex-shrink-0">
                            <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full" />
                        </div>
                        <div class="flex-1 overflow-hidden flex flex-col">
                            <CartPanel
                                :transaction="currentTransaction"
                                :loading="cartLoading"
                                @remove="removeItem"
                                @adjust="adjustQty"
                                @checkout="cartDrawerOpen = false; showPayment = true"
                                @void="cartDrawerOpen = false; promptVoid()"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </template>

        <!-- Payment modal -->
        <PaymentModal
            v-if="showPayment && currentTransaction"
            :transaction="currentTransaction"
            @close="showPayment = false"
            @complete="processCheckout"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/axios';
import type { PosTerminal, PosTransaction, PosTransactionItem, Product } from '@/types';
import { useNotificationStore } from '@/stores/notification';
import ProductGrid from '@/components/pos/ProductGrid.vue';
import CartPanel from '@/components/pos/CartPanel.vue';
import PaymentModal from '@/components/pos/PaymentModal.vue';

const notify = useNotificationStore();

// ── State ─────────────────────────────────────────────────────────────────────
const terminals = ref<PosTerminal[]>([]);
const terminalsLoading = ref(false);
const selectedTerminalId = ref<number | null>(null);
const activeTerminal = ref<PosTerminal | null>(null);

const products = ref<Product[]>([]);
const productsLoading = ref(false);

const currentTransaction = ref<PosTransaction | null>(null);
const openingTransaction = ref(false);
const cartLoading = ref(false);
const addingProduct = ref<number | null>(null);

const showPayment = ref(false);
const cartDrawerOpen = ref(false);

// ── Computed ──────────────────────────────────────────────────────────────────
const itemCount = computed(() => currentTransaction.value?.items?.length ?? 0);

function fmt(val: number | string | undefined | null): string {
    return Number(val ?? 0).toFixed(2);
}

// ── Load data ─────────────────────────────────────────────────────────────────
async function loadTerminals() {
    terminalsLoading.value = true;
    try {
        const res = await apiClient.get('/pos/terminals?status=active&per_page=50');
        terminals.value = res.data?.data ?? res.data ?? [];
    } catch {
        terminals.value = [];
    } finally {
        terminalsLoading.value = false;
    }
}

async function loadProducts() {
    productsLoading.value = true;
    try {
        const res = await apiClient.get('/inventory/products?per_page=100');
        const raw = res.data?.data ?? res.data ?? [];
        // Handle paginated response
        products.value = Array.isArray(raw) ? raw : raw.data ?? [];
    } catch {
        products.value = [];
    } finally {
        productsLoading.value = false;
    }
}

// ── Transaction lifecycle ────────────────────────────────────────────────────
async function openTransaction() {
    if (!selectedTerminalId.value) return;
    openingTransaction.value = true;
    try {
        const res = await apiClient.post('/pos/transactions', { terminal_id: selectedTerminalId.value });
        currentTransaction.value = res.data?.data ?? res.data;
        activeTerminal.value = terminals.value.find(t => t.id === selectedTerminalId.value) ?? null;
    } catch {
        notify.error('Failed to open transaction');
    } finally {
        openingTransaction.value = false;
    }
}

async function startNewSale() {
    if (!activeTerminal.value) return;
    try {
        const res = await apiClient.post('/pos/transactions', { terminal_id: activeTerminal.value.id });
        currentTransaction.value = res.data?.data ?? res.data;
        showPayment.value = false;
        cartDrawerOpen.value = false;
    } catch {
        notify.error('Failed to start new sale');
    }
}

async function refreshTransaction() {
    if (!currentTransaction.value) return;
    try {
        const res = await apiClient.get(`/pos/transactions/${currentTransaction.value.id}`);
        currentTransaction.value = res.data?.data ?? res.data;
    } catch { /* ignore */ }
}

// ── Cart operations ────────────────────────────────────────────────────────────
async function addProductToCart(product: Product) {
    if (!currentTransaction.value || addingProduct.value !== null) return;
    addingProduct.value = product.id;
    try {
        // Merge with existing line item for the same product
        const existing = currentTransaction.value.items?.find(i => i.product_id === product.id);
        if (existing) {
            await apiClient.delete(`/pos/transactions/${currentTransaction.value.id}/items/${existing.id}`);
            await apiClient.post(`/pos/transactions/${currentTransaction.value.id}/items`, {
                product_id: product.id,
                description: product.name,
                quantity: existing.quantity + 1,
                unit_price: product.price,
            });
        } else {
            await apiClient.post(`/pos/transactions/${currentTransaction.value.id}/items`, {
                product_id: product.id,
                description: product.name,
                quantity: 1,
                unit_price: product.price,
            });
        }
        await refreshTransaction();
    } catch {
        notify.error('Failed to add item');
    } finally {
        addingProduct.value = null;
    }
}

async function removeItem(itemId: number) {
    if (!currentTransaction.value) return;
    cartLoading.value = true;
    try {
        await apiClient.delete(`/pos/transactions/${currentTransaction.value.id}/items/${itemId}`);
        await refreshTransaction();
    } catch {
        notify.error('Failed to remove item');
    } finally {
        cartLoading.value = false;
    }
}

async function adjustQty(item: PosTransactionItem, delta: number) {
    if (!currentTransaction.value) return;
    const newQty = item.quantity + delta;
    if (newQty <= 0) {
        await removeItem(item.id);
        return;
    }
    cartLoading.value = true;
    try {
        await apiClient.delete(`/pos/transactions/${currentTransaction.value.id}/items/${item.id}`);
        await apiClient.post(`/pos/transactions/${currentTransaction.value.id}/items`, {
            product_id: item.product_id,
            description: item.description,
            quantity: newQty,
            unit_price: item.unit_price,
            discount_percent: item.discount_percent,
            tax_percent: item.tax_percent,
        });
        await refreshTransaction();
    } catch {
        notify.error('Failed to update quantity');
    } finally {
        cartLoading.value = false;
    }
}

// ── Checkout & void ──────────────────────────────────────────────────────────
async function processCheckout(paymentData: { method: string; amount: number }) {
    if (!currentTransaction.value) return;
    try {
        await apiClient.post(`/pos/transactions/${currentTransaction.value.id}/checkout`, {
            payments: [{ method: paymentData.method, amount: paymentData.amount }],
        });
        showPayment.value = false;
        notify.success('Sale completed!');
        await refreshTransaction();
    } catch {
        notify.error('Checkout failed — please try again');
    }
}

async function promptVoid() {
    if (!currentTransaction.value) return;
    const reason = window.prompt('Reason for voiding this transaction:');
    if (!reason?.trim()) return;
    try {
        await apiClient.post(`/pos/transactions/${currentTransaction.value.id}/void`, { reason });
        notify.success('Transaction voided');
        await refreshTransaction();
    } catch {
        notify.error('Failed to void transaction');
    }
}

// ── Init ────────────────────────────────────────────────────────────────────
onMounted(() => {
    loadTerminals();
    loadProducts();
});
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(100%);
}
.slide-up-enter-to,
.slide-up-leave-from {
    opacity: 1;
    transform: translateY(0);
}
</style>
