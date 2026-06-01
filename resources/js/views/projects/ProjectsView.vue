<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Projects</h1>
        <DataTable :columns="columns" :data="projects" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
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
            <template #actions="{ row }">
                <button @click="openEdit(row)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3 text-sm">Edit</button>
                <button @click="deleteProject(row.id)" class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Delete</button>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingProject ? 'Edit Project' : 'New Project'">
            <form @submit.prevent="submitForm" class="space-y-4">
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
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select v-model="form.status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="planning">Planning</option>
                            <option value="active">Active</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                        <select v-model="form.priority" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
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
import apiClient from '@/api/axios';
import type { Project } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const projects = ref<Project[]>([]);
const showModal = ref(false);
const editingProject = ref<Project | null>(null);
const form = reactive({ name: '', code: '', start_date: '', end_date: '', status: 'planning', priority: 'medium', budget: 0, description: '' });

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
        const res = await apiClient.get('/projects/projects');
        projects.value = res.data?.data ?? res.data ?? [];
    } catch {
        projects.value = [];
    }
}

function openCreate() {
    editingProject.value = null;
    Object.assign(form, { name: '', code: '', start_date: '', end_date: '', status: 'planning', priority: 'medium', budget: 0, description: '' });
    showModal.value = true;
}

function openEdit(row: Project) {
    editingProject.value = row;
    Object.assign(form, {
        name: row.name,
        code: row.code,
        start_date: row.start_date ?? '',
        end_date: row.end_date ?? '',
        status: row.status ?? 'planning',
        priority: row.priority ?? 'medium',
        budget: row.budget ?? 0,
        description: row.description ?? '',
    });
    showModal.value = true;
}

async function submitForm() {
    try {
        if (editingProject.value) {
            await apiClient.put(`/projects/projects/${editingProject.value.id}`, form);
            notify.success('Project updated successfully');
        } else {
            await apiClient.post('/projects/projects', form);
            notify.success('Project created successfully');
        }
        showModal.value = false;
        await loadProjects();
    } catch {
        notify.error(editingProject.value ? 'Failed to update project' : 'Failed to create project');
    }
}

async function deleteProject(id: number) {
    if (!confirm('Delete this project?')) return;
    try {
        await apiClient.delete(`/projects/projects/${id}`);
        notify.success('Project deleted');
        await loadProjects();
    } catch {
        notify.error('Failed to delete project');
    }
}

onMounted(loadProjects);
</script>
