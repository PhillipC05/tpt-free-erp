<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Departments</h1>
        <DataTable :columns="columns" :data="departments" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Department
                </button>
            </template>
            <template #cell-is_active="{ value }">
                <span :class="value ? 'text-green-600' : 'text-red-600'" class="text-xs">{{ value ? 'Active' : 'Inactive' }}</span>
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Add Department">
            <form @submit.prevent="createDepartment" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                    <input v-model="form.code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea v-model="form.description" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
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
import apiClient from '@/api/axios';
import type { Department } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const departments = ref<Department[]>([]);
const showCreateModal = ref(false);
const form = reactive({ code: '', name: '', description: '' });

const columns = [
    { key: 'code', label: 'Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'description', label: 'Description', sortable: false },
    { key: 'is_active', label: 'Status', sortable: true },
];

async function loadDepartments() {
    try {
        const res = await apiClient.get('/departments');
        departments.value = res.data;
    } catch {
        departments.value = [];
    }
}

async function createDepartment() {
    try {
        await apiClient.post('/departments', form);
        showCreateModal.value = false;
        notify.success('Department created successfully');
        await loadDepartments();
    } catch {
        notify.error('Failed to create department');
    }
}

onMounted(loadDepartments);
</script>
