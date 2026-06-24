<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Assets</h1>
        <DataTable :columns="columns" :data="assets" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Asset
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ value }}
                </span>
            </template>
            <template #cell-current_value="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
            <template #cell-purchase_cost="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
            <template #actions="{ row }">
                <button @click="openEdit(row)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3 text-sm">Edit</button>
                <button @click="deleteAsset(row.id)" class="text-red-600 hover:text-red-800 dark:text-red-400 mr-3 text-sm">Delete</button>
                <button @click="openDocs(row)" class="text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 text-sm">Docs</button>
            </template>
        </DataTable>

        <!-- Document Attachment Modal -->
        <ModalDialog v-model="showDocsModal" :title="`Documents — ${selectedAssetName}`">
            <DocumentAttachmentPanel
                v-if="selectedAssetId"
                documentable-type="App\Models\Assets\Asset"
                :documentable-id="selectedAssetId"
            />
        </ModalDialog>

        <ModalDialog v-model="showModal" :title="editingAsset ? 'Edit Asset' : 'Add Asset'">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asset Code</label>
                    <input v-model="form.asset_code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select v-model="form.type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="equipment">Equipment</option>
                            <option value="vehicle">Vehicle</option>
                            <option value="building">Building</option>
                            <option value="furniture">Furniture</option>
                            <option value="it">IT</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="retired">Retired</option>
                            <option value="disposed">Disposed</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Purchase Date</label>
                        <input v-model="form.purchase_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Purchase Cost ($)</label>
                        <input v-model.number="form.purchase_cost" type="number" min="0" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                    <input v-model="form.location" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">Save</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import DataTable from '@/components/DataTable.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import DocumentAttachmentPanel from '@/components/DocumentAttachmentPanel.vue';
import apiClient from '@/api/axios';
import type { Asset } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

const showDocsModal = ref(false);
const selectedAssetId = ref<number | null>(null);
const selectedAssetName = ref('');

function openDocs(row: any) {
    selectedAssetId.value = row.id;
    selectedAssetName.value = row.name ?? String(row.id);
    showDocsModal.value = true;
}
const assets = ref<Asset[]>([]);
const showModal = ref(false);
const editingAsset = ref<Asset | null>(null);
const form = reactive({ name: '', asset_code: '', type: 'equipment' as string, status: 'active', purchase_date: '', purchase_cost: 0, location: '' });

const columns = [
    { key: 'asset_code', label: 'Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'purchase_cost', label: 'Cost', sortable: true },
    { key: 'current_value', label: 'Current Value', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        maintenance: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        retired: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        disposed: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

async function loadAssets() {
    try {
        const res = await apiClient.get('/assets/assets');
        assets.value = res.data?.data ?? res.data ?? [];
    } catch {
        assets.value = [];
    }
}

function openCreate() {
    editingAsset.value = null;
    Object.assign(form, { name: '', asset_code: '', type: 'equipment', status: 'active', purchase_date: '', purchase_cost: 0, location: '' });
    showModal.value = true;
}

function openEdit(row: Asset) {
    editingAsset.value = row;
    Object.assign(form, {
        name: row.name,
        asset_code: row.asset_code,
        type: row.type ?? 'equipment',
        status: row.status ?? 'active',
        purchase_date: row.purchase_date ?? '',
        purchase_cost: row.purchase_cost ?? 0,
        location: (row as any).location ?? '',
    });
    showModal.value = true;
}

async function submitForm() {
    try {
        if (editingAsset.value) {
            await apiClient.put(`/assets/assets/${editingAsset.value.id}`, form);
            notify.success('Asset updated successfully');
        } else {
            await apiClient.post('/assets/assets', form);
            notify.success('Asset added successfully');
        }
        showModal.value = false;
        await loadAssets();
    } catch {
        notify.error(editingAsset.value ? 'Failed to update asset' : 'Failed to add asset');
    }
}

async function deleteAsset(id: number) {
    if (!confirm('Delete this asset?')) return;
    try {
        await apiClient.delete(`/assets/assets/${id}`);
        notify.success('Asset deleted');
        await loadAssets();
    } catch {
        notify.error('Failed to delete asset');
    }
}

onMounted(loadAssets);
</script>
