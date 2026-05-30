<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Employees</h1>
        <DataTable :columns="columns" :data="employees" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Employee
                </button>
            </template>
            <template #cell-is_active="{ value }">
                <span :class="value ? 'text-green-600' : 'text-red-600'" class="text-xs">{{ value ? 'Active' : 'Inactive' }}</span>
            </template>
            <template #cell-salary="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Add Employee">
            <form @submit.prevent="createEmployee" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
                        <input v-model="form.first_name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                        <input v-model="form.last_name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Employee Code</label>
                    <input v-model="form.employee_code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input v-model="form.email" type="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Position</label>
                    <input v-model="form.position" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hire Date</label>
                        <input v-model="form.hire_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Salary</label>
                        <input v-model.number="form.salary" type="number" min="0" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
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
import type { Employee } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const employees = ref<Employee[]>([]);
const showCreateModal = ref(false);
const form = reactive({ first_name: '', last_name: '', employee_code: '', email: '', position: '', hire_date: '', salary: 0 });

const columns = [
    { key: 'employee_code', label: 'Code', sortable: true },
    { key: 'first_name', label: 'First Name', sortable: true },
    { key: 'last_name', label: 'Last Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'position', label: 'Position', sortable: true },
    { key: 'salary', label: 'Salary', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
];

async function loadEmployees() {
    try {
        const res = await apiClient.get('/employees');
        employees.value = res.data;
    } catch {
        employees.value = [];
    }
}

async function createEmployee() {
    try {
        await apiClient.post('/employees', form);
        showCreateModal.value = false;
        notify.success('Employee added successfully');
        await loadEmployees();
    } catch {
        notify.error('Failed to add employee');
    }
}

onMounted(loadEmployees);
</script>
