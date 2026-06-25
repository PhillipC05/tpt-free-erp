<template>
    <div class="flex flex-col h-full bg-gray-100 dark:bg-gray-900">
        <!-- Search & filter bar -->
        <div class="flex gap-2 p-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search products..."
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>
            <!-- Category filter pills -->
            <div class="flex gap-1 overflow-x-auto">
                <button
                    v-for="cat in ['All', ...categories]"
                    :key="cat"
                    @click="selectedCategory = cat === 'All' ? null : cat"
                    :class="[
                        'flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-full transition-colors',
                        (cat === 'All' ? selectedCategory === null : selectedCategory === cat)
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    ]"
                >{{ cat }}</button>
            </div>
        </div>

        <!-- Product grid -->
        <div class="flex-1 overflow-y-auto p-3">
            <div v-if="loading" class="flex items-center justify-center h-40">
                <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin" />
            </div>

            <div v-else-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center h-40 text-gray-400 gap-2">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="text-sm">No products found</p>
            </div>

            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2">
                <button
                    v-for="product in filteredProducts"
                    :key="product.id"
                    @click="emit('add', product)"
                    :disabled="!props.transactionId || props.adding === product.id"
                    class="relative rounded-xl overflow-hidden shadow-sm aspect-square flex flex-col items-center justify-end text-white active:scale-95 transition-all duration-100 touch-manipulation disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2"
                    :style="{ backgroundColor: product.image_url ? undefined : tileColor(product.name) }"
                >
                    <!-- Product image background -->
                    <img
                        v-if="(product as any).image_url"
                        :src="(product as any).image_url"
                        :alt="product.name"
                        class="absolute inset-0 w-full h-full object-cover"
                    />

                    <!-- Gradient overlay for readability -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />

                    <!-- Product info -->
                    <div class="relative z-10 w-full px-2 pb-2 text-center">
                        <p class="text-xs font-semibold leading-tight line-clamp-2 drop-shadow">{{ product.name }}</p>
                        <p class="text-sm font-bold drop-shadow mt-0.5">${{ Number(product.price).toFixed(2) }}</p>
                    </div>

                    <!-- Loading overlay -->
                    <div v-if="props.adding === product.id" class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-xl">
                        <div class="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Product } from '@/types';

const props = defineProps<{
    products: Product[];
    loading: boolean;
    transactionId: number | null;
    adding: number | null;
}>();

const emit = defineEmits<{
    add: [product: Product];
}>();

const search = ref('');
const selectedCategory = ref<string | null>(null);

const TILE_COLORS = [
    '#4f46e5', '#7c3aed', '#db2777', '#dc2626', '#d97706',
    '#059669', '#0284c7', '#0891b2', '#16a34a', '#9333ea',
    '#ea580c', '#0f766e', '#be185d', '#1d4ed8', '#854d0e',
];

function tileColor(name: string): string {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = ((hash << 5) - hash + name.charCodeAt(i)) | 0;
    }
    return TILE_COLORS[Math.abs(hash) % TILE_COLORS.length];
}

const categories = computed(() => {
    const cats = new Set<string>();
    for (const p of props.products) {
        if (p.category?.name) cats.add(p.category.name);
    }
    return [...cats].sort();
});

const filteredProducts = computed(() => {
    let list = props.products.filter(p => p.is_active !== false);
    if (selectedCategory.value) {
        list = list.filter(p => p.category?.name === selectedCategory.value);
    }
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(p => p.name.toLowerCase().includes(q) || p.sku?.toLowerCase().includes(q));
    }
    return list;
});
</script>
