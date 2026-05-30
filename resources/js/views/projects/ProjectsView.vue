<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Projects</h1>
        <DataTable :columns="columns" :data="projects" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    New Project
                </button>
            </template>
            <template #cell-status="{ value }">
                <span :class="statusClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ (value as string).replace(/_/g, ' ') }}
                </span>
            </template>
            <template #cell-budget="{ value }">
                ${{ Number(value).toLocaleString() }}
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="New Project">
            <form @submit.prevent="createProject" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                    <input v-model="form.code" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                        <input v-model="form.start_date" type="date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                        <input v-model="form.end_date" type="date" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Budget ($)</label>
                    <input v-model.number="form.budget" type="number" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
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
import type { Project } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const projects = ref<Project[]>([]);
const showCreateModal = ref(false);
const form = reactive({ name: '', code: '', start_date: '', end_date: '', budget: 0, description: '' });

const columns = [
    { key: 'code', label: 'Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'start_date', label: 'Start', sortable: true },
    { key: 'end_date', label: 'End', sortable: true },
    { key: 'budget', label: 'Budget', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
];

function statusClass(status: string): string {
    const classes: Record<string, string> = {
        planning: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        on_hold: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

async function loadProjects() {
    try {
        const res = await apiClient.get('/projects');
        projects.value = res.data;
    } catch {
        projects.value = [];
    }
}

async function createProject() {
    try {
        await apiClient.post('/projects', form);
        showCreateModal.value = false;
        notify.success('Project created successfully');
        await loadProjects();
    } catch {
        notify.error('Failed to create project');
    }
}

onMounted(loadProjects);
</script>
