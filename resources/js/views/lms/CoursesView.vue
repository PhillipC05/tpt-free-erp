<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Courses</h1>
        <DataTable :columns="columns" :data="courses" searchable>
            <template #header>
                <button @click="openCreate" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Course
                </button>
            </template>
            <template #cell-type="{ value }">
                <span class="px-2 py-1 text-xs rounded-full font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 capitalize">
                    {{ (value as string).replace(/_/g, ' ') }}
                </span>
            </template>
            <template #cell-is_active="{ value }">
                <span :class="value ? 'text-green-600' : 'text-yellow-600'" class="text-xs">
                    {{ value ? 'Active' : 'Inactive' }}
                </span>
            </template>
            <template #actions="{ row }">
                <button @click="openEdit(row)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3 text-sm">Edit</button>
                <button @click="deleteCourse(row.id)" class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm">Delete</button>
            </template>
        </DataTable>

        <ModalDialog v-model="showModal" :title="editingCourse ? 'Edit Course' : 'Add Course'">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Course Code</label>
                        <input v-model="form.code" type="text" required placeholder="e.g. LMS-001" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select v-model="form.type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="online">Online</option>
                            <option value="classroom">Classroom</option>
                            <option value="blended">Blended</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                    <input v-model="form.title" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Duration (hours)</label>
                        <input v-model.number="form.duration_hours" type="number" min="0.5" step="0.5" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cost ($)</label>
                        <input v-model.number="form.cost" type="number" min="0" step="0.01" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
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
import type { Course } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const courses = ref<Course[]>([]);
const showModal = ref(false);
const editingCourse = ref<Course | null>(null);
const form = reactive({ code: '', title: '', type: 'online' as string, duration_hours: 1, cost: 0, description: '' });

const columns = [
    { key: 'code', label: 'Code', sortable: true },
    { key: 'title', label: 'Title', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'duration_hours', label: 'Duration (hrs)', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
];

async function loadCourses() {
    try {
        const res = await apiClient.get('/lms/courses');
        courses.value = res.data?.data ?? res.data ?? [];
    } catch {
        courses.value = [];
    }
}

function openCreate() {
    editingCourse.value = null;
    Object.assign(form, { code: '', title: '', type: 'online', duration_hours: 1, cost: 0, description: '' });
    showModal.value = true;
}

function openEdit(row: Course) {
    editingCourse.value = row;
    Object.assign(form, { code: row.code ?? '', title: row.title, type: row.type ?? 'online', duration_hours: row.duration_hours ?? 1, cost: (row as any).cost ?? 0, description: row.description ?? '' });
    showModal.value = true;
}

async function submitForm() {
    try {
        if (editingCourse.value) {
            await apiClient.put(`/lms/courses/${editingCourse.value.id}`, form);
            notify.success('Course updated successfully');
        } else {
            await apiClient.post('/lms/courses', form);
            notify.success('Course added successfully');
        }
        showModal.value = false;
        await loadCourses();
    } catch {
        notify.error(editingCourse.value ? 'Failed to update course' : 'Failed to add course');
    }
}

async function deleteCourse(id: number) {
    if (!confirm('Delete this course?')) return;
    try {
        await apiClient.delete(`/lms/courses/${id}`);
        notify.success('Course deleted');
        await loadCourses();
    } catch {
        notify.error('Failed to delete course');
    }
}

onMounted(loadCourses);
</script>
