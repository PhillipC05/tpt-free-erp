<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div v-if="$slots.header || searchable" class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between gap-4">
                <div v-if="$slots.header" class="flex-1">
                    <slot name="header" />
                </div>
                <div v-if="searchable" class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search..."
                        class="pl-9 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            @click="col.sortable ? toggleSort(col.key) : undefined"
                            :class="[
                                'px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider',
                                col.sortable ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 select-none' : ''
                            ]"
                        >
                            <div class="flex items-center gap-1">
                                <span>{{ col.label }}</span>
                                <span v-if="col.sortable && sortKey === col.key" class="inline-block">
                                    <svg v-if="sortDir === 'asc'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 12l7-7 7 7" />
                                    </svg>
                                    <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 12l7 7 7-7" />
                                    </svg>
                                </span>
                            </div>
                        </th>
                        <th v-if="$slots.actions" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-if="loading">
                        <td :colspan="columns.length + (!!$slots.actions ? 1 : 0)" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <span>Loading...</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-else-if="filteredData.length === 0">
                        <td :colspan="columns.length + (!!$slots.actions ? 1 : 0)" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No data available.
                        </td>
                    </tr>
                    <tr v-for="(row, rowIndex) in filteredData" :key="row.id || rowIndex" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td v-for="col in columns" :key="col.key" class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                            <slot :name="`cell-${col.key}`" :row="row" :value="getNestedValue(row, col.key)">
                                {{ getNestedValue(row, col.key) }}
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="px-4 py-3 whitespace-nowrap text-right text-sm">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="totalPages > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Page {{ currentPage }} of {{ totalPages }}
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="prevPage"
                    :disabled="currentPage <= 1"
                    class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    Previous
                </button>
                <button
                    @click="nextPage"
                    :disabled="currentPage >= totalPages"
                    class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';

export interface Column {
    key: string;
    label: string;
    sortable?: boolean;
}

const props = withDefaults(defineProps<{
    columns: Column[];
    data: Record<string, unknown>[];
    loading?: boolean;
    searchable?: boolean;
    pageSize?: number;
}>(), {
    loading: false,
    searchable: false,
    pageSize: 10,
});

const emit = defineEmits<{
    sort: [key: string, dir: 'asc' | 'desc'];
}>();

const searchQuery = ref('');
const sortKey = ref('');
const sortDir = ref<'asc' | 'desc'>('asc');
const currentPage = ref(1);

const filteredData = computed(() => {
    let result = [...props.data];

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter((row) => {
            return props.columns.some((col) => {
                const val = getNestedValue(row, col.key);
                return val != null && String(val).toLowerCase().includes(q);
            });
        });
    }

    if (sortKey.value) {
        result.sort((a, b) => {
            const aVal = getNestedValue(a, sortKey.value);
            const bVal = getNestedValue(b, sortKey.value);
            if (aVal == null) return 1;
            if (bVal == null) return -1;
            const cmp = aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
            return sortDir.value === 'asc' ? cmp : -cmp;
        });
    }

    const start = (currentPage.value - 1) * props.pageSize;
    const end = start + props.pageSize;
    return result.slice(start, end);
});

const totalPages = computed(() => Math.ceil(props.data.length / props.pageSize));

watch([() => props.data, searchQuery], () => {
    currentPage.value = 1;
});

function getNestedValue(obj: Record<string, unknown>, path: string): unknown {
    return path.split('.').reduce((acc: unknown, part: string) => {
        if (acc && typeof acc === 'object') {
            return (acc as Record<string, unknown>)[part];
        }
        return undefined;
    }, obj);
}

function toggleSort(key: string) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
    emit('sort', sortKey.value, sortDir.value);
}

function prevPage() {
    if (currentPage.value > 1) currentPage.value--;
}

function nextPage() {
    if (currentPage.value < totalPages.value) currentPage.value++;
}
</script>