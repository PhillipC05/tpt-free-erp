<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Employees</h1>
        <DataTable :columns="columns" :data="employees" searchable>
            <template #header>
                <button @click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Employee
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="{
                    'text-green-600': value === 'active',
                    'text-yellow-600': value === 'on_leave',
                    'text-red-600': value === 'terminated',
                }" class="text-xs font-medium capitalize">{{ value?.replace('_', ' ') }}</span>
            </template>
            <template #cell-salary="{ value }">
                {{ value ? '$' + Number(value).toLocaleString() : '—' }}
            </template>
            <template #cell-department="{ row }">
                {{ row.department?.name ?? '—' }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-2">
                    <button @click="viewProfile(row.id)" class="text-xs text-blue-600 hover:text-blue-800">Profile</button>
                    <button @click="openEditModal(row)" class="text-xs text-gray-500 hover:text-gray-700">Edit</button>
                </div>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingEmployee ? 'Edit Employee' : 'Add Employee'">
            <form @submit.prevent="saveEmployee" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
                        <input v-model="form.first_name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
                        <input v-model="form.last_name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Employee Code</label>
                        <input v-model="form.employee_code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input v-model="form.email" type="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                        <input v-model="form.phone" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Position</label>
                        <input v-model="form.position" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                        <input v-model.number="form.department_id" type="number" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Employment Type</label>
                        <select v-model="form.employment_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="intern">Intern</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hire Date</label>
                        <input v-model="form.hire_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Salary</label>
                        <input v-model.number="form.salary" type="number" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Emergency Contact</label>
                        <input v-model="form.emergency_contact" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Emergency Phone</label>
                        <input v-model="form.emergency_phone" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                    <textarea v-model="form.address" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"></textarea>
                </div>
                <div v-if="editingEmployee" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="active">Active</option>
                            <option value="on_leave">On Leave</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Manager ID</label>
                        <input v-model.number="form.manager_id" type="number" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">{{ editingEmployee ? 'Update' : 'Save' }}</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import DataTable from '@/components/DataTable.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import type { Employee } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const router = useRouter();
const notify = useNotificationStore();
const employees = ref<Employee[]>([]);
const showModal = ref(false);
const editingEmployee = ref<Employee | null>(null);

const defaultForm = { first_name: '', last_name: '', employee_code: '', email: '', phone: '', position: '', department_id: null as number | null, employment_type: 'full_time', hire_date: '', salary: 0, address: '', emergency_contact: '', emergency_phone: '', status: 'active', manager_id: null as number | null };
const form = reactive({ ...defaultForm });

const columns = [
    { key: 'employee_code', label: 'Code', sortable: true },
    { key: 'first_name', label: 'First Name', sortable: true },
    { key: 'last_name', label: 'Last Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'position', label: 'Position', sortable: true },
    { key: 'department', label: 'Department', sortable: false },
    { key: 'salary', label: 'Salary', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: '' },
];

function openCreateModal() {
    editingEmployee.value = null;
    Object.assign(form, defaultForm);
    showModal.value = true;
}

function openEditModal(emp: Employee) {
    editingEmployee.value = emp;
    Object.assign(form, {
        first_name: emp.first_name, last_name: emp.last_name, employee_code: emp.employee_code,
        email: emp.email, phone: emp.phone ?? '', position: emp.position ?? '',
        department_id: emp.department_id, employment_type: emp.employment_type,
        hire_date: emp.hire_date, salary: emp.salary ?? 0,
        address: (emp as any).address ?? '', emergency_contact: (emp as any).emergency_contact ?? '',
        emergency_phone: (emp as any).emergency_phone ?? '', status: emp.status ?? 'active',
        manager_id: (emp as any).manager_id,
    });
    showModal.value = true;
}

function viewProfile(id: number) {
    router.push({ name: 'hr.employee-profile', params: { id } });
}

async function loadEmployees() {
    try {
        const res = await apiClient.get('/hr/employees');
        employees.value = res.data?.data ?? res.data ?? [];
    } catch {
        employees.value = [];
    }
}

async function saveEmployee() {
    try {
        if (editingEmployee.value) {
            await apiClient.put(`/hr/employees/${editingEmployee.value.id}`, form);
            notify.success('Employee updated');
        } else {
            await apiClient.post('/hr/employees', form);
            notify.success('Employee created');
        }
        showModal.value = false;
        await loadEmployees();
    } catch {
        notify.error(editingEmployee.value ? 'Failed to update employee' : 'Failed to create employee');
    }
}

onMounted(loadEmployees);
</script>
