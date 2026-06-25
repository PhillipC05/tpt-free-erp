<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Parts Inventory</h1>

        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Parts</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ parts.length }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Low Stock</div>
                <div class="text-2xl font-bold text-red-600">{{ lowStockCount }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Value</div>
                <div class="text-2xl font-bold text-green-600">${{ totalValue.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Categories</div>
                <div class="text-2xl font-bold text-blue-600">{{ categories.length }}</div>
            </div>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <button @click="activeTab = 'parts'" :class="activeTab === 'parts' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="px-4 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600">Parts</button>
            <button @click="activeTab = 'categories'" :class="activeTab === 'categories' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="px-4 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600">Categories</button>
            <button @click="activeTab = 'usage'" :class="activeTab === 'usage' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="px-4 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600">Usage History</button>
        </div>

        <div v-if="activeTab === 'parts'">
            <DataTable :columns="partColumns" :data="parts" searchable>
                <template #header>
                    <button @click="showCreatePartModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Add Part</button>
                </template>
                <template #cell-quantity_on_hand="{ value, row }">
                    <span :class="row.reorder_level > 0 && value <= row.reorder_level ? 'text-red-600 font-bold' : ''">{{ value }}</span>
                </template>
                <template #cell-unit_cost="{ value }">${{ Number(value).toFixed(2) }}</template>
                <template #cell-actions="{ row }">
                    <button @click="showAdjustModal(row)" class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Adjust</button>
                </template>
            </DataTable>
        </div>

        <div v-if="activeTab === 'categories'">
            <DataTable :columns="categoryColumns" :data="categories">
                <template #header>
                    <button @click="showCreateCategoryModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Add Category</button>
                </template>
            </DataTable>
        </div>

        <div v-if="activeTab === 'usage'">
            <DataTable :columns="usageColumns" :data="usages">
                <template #header>
                    <button @click="showRecordUsageModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Record Usage</button>
                </template>
                <template #cell-quantity="{ value, row }">{{ value }} {{ row.part?.unit }}</template>
                <template #cell-total_cost="{ value }">${{ Number(value).toFixed(2) }}</template>
                <template #cell-used_date="{ value }">{{ new Date(value).toLocaleDateString() }}</template>
            </DataTable>
        </div>

        <ModalDialog v-model="showCreatePartModal" title="Add Part">
            <form @submit.prevent="createPart" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Part Number</label>
                        <input v-model="partForm.part_number" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                        <input v-model="partForm.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit Cost</label>
                        <input v-model.number="partForm.unit_cost" type="number" step="0.01" min="0" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                        <input v-model.number="partForm.quantity_on_hand" type="number" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reorder Level</label>
                        <input v-model.number="partForm.reorder_level" type="number" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreatePartModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Save</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog v-model="showAdjustModal_dialog" title="Adjust Stock">
            <form @submit.prevent="adjustStock" class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Current stock: <strong>{{ adjustingPart?.quantity_on_hand }}</strong></p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adjustment (negative to subtract)</label>
                    <input v-model.number="adjustForm.adjustment" type="number" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason</label>
                    <input v-model="adjustForm.reason" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAdjustModal_dialog = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Apply</button>
                </div>
            </form>
        </ModalDialog>

        <ModalDialog v-model="showRecordUsageModal" title="Record Part Usage">
            <form @submit.prevent="recordUsage" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Part</label>
                        <select v-model.number="usageForm.part_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="">Select part...</option>
                            <option v-for="p in parts" :key="p.id" :value="p.id">{{ p.part_number }} — {{ p.name }} ({{ p.quantity_on_hand }} in stock)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vehicle ID</label>
                        <input v-model.number="usageForm.vehicle_id" type="number" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                        <input v-model.number="usageForm.quantity" type="number" min="0.01" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                        <input v-model="usageForm.used_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showRecordUsageModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Record</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import type { FleetPart, FleetPartCategory, FleetPartUsage } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const activeTab = ref('parts');
const parts = ref<FleetPart[]>([]);
const categories = ref<FleetPartCategory[]>([]);
const usages = ref<FleetPartUsage[]>([]);

const showCreatePartModal = ref(false);
const showAdjustModal_dialog = ref(false);
const showRecordUsageModal = ref(false);
const showCreateCategoryModal = ref(false);
const adjustingPart = ref<FleetPart | null>(null);

const partForm = reactive({ part_number: '', name: '', unit_cost: 0, quantity_on_hand: 0, reorder_level: 0 });
const adjustForm = reactive({ adjustment: 0, reason: '' });
const usageForm = reactive({ part_id: '' as string | number, vehicle_id: '', quantity: 1, used_date: new Date().toISOString().split('T')[0] });

const lowStockCount = computed(() => parts.value.filter(p => p.reorder_level > 0 && p.quantity_on_hand <= p.reorder_level).length);
const totalValue = computed(() => parts.value.reduce((sum, p) => sum + (p.unit_cost * p.quantity_on_hand), 0));

const partColumns = [
    { key: 'part_number', label: 'Part #', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'category.name', label: 'Category', sortable: false },
    { key: 'quantity_on_hand', label: 'Stock', sortable: true },
    { key: 'reorder_level', label: 'Reorder At', sortable: true },
    { key: 'unit_cost', label: 'Cost', sortable: true },
    { key: 'bin_location', label: 'Location', sortable: false },
];

const categoryColumns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'slug', label: 'Slug', sortable: true },
    { key: 'parts_count', label: 'Parts', sortable: true },
];

const usageColumns = [
    { key: 'part.part_number', label: 'Part #', sortable: false },
    { key: 'part.name', label: 'Part', sortable: false },
    { key: 'vehicle.vehicle_code', label: 'Vehicle', sortable: false },
    { key: 'quantity', label: 'Qty', sortable: true },
    { key: 'total_cost', label: 'Cost', sortable: true },
    { key: 'used_date', label: 'Date', sortable: true },
];

async function loadData() {
    try {
        const [partsRes, catsRes, usageRes] = await Promise.all([
            apiClient.get('/fleet/parts'),
            apiClient.get('/fleet/part-categories'),
            apiClient.get('/fleet/parts/usage'),
        ]);
        parts.value = partsRes.data?.data ?? [];
        categories.value = catsRes.data?.data ?? [];
        usages.value = usageRes.data?.data ?? [];
    } catch { /* ignore */ }
}

async function createPart() {
    try {
        await apiClient.post('/fleet/parts', partForm);
        showCreatePartModal.value = false;
        notify.success('Part added');
        Object.assign(partForm, { part_number: '', name: '', unit_cost: 0, quantity_on_hand: 0, reorder_level: 0 });
        await loadData();
    } catch {
        notify.error('Failed to add part');
    }
}

function showAdjustModal(part: FleetPart) {
    adjustingPart.value = part;
    adjustForm.adjustment = 0;
    adjustForm.reason = '';
    showAdjustModal_dialog.value = true;
}

async function adjustStock() {
    if (!adjustingPart.value) return;
    try {
        await apiClient.post(`/fleet/parts/${adjustingPart.value.id}/adjust-stock`, adjustForm);
        showAdjustModal_dialog.value = false;
        notify.success('Stock adjusted');
        await loadData();
    } catch {
        notify.error('Failed to adjust stock');
    }
}

async function recordUsage() {
    try {
        await apiClient.post('/fleet/parts/usage', usageForm);
        showRecordUsageModal.value = false;
        notify.success('Usage recorded');
        usageForm.part_id = '';
        usageForm.vehicle_id = '';
        usageForm.quantity = 1;
        await loadData();
    } catch {
        notify.error('Failed to record usage');
    }
}

onMounted(loadData);
</script>
