<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Courses</h1>
        <DataTable :columns="columns" :data="courses" searchable>
            <template #header>
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    Add Course
                </button>
            </template>
            <template #cell-difficulty="{ value }">
                <span :class="difficultyClass(value as string)" class="px-2 py-1 text-xs rounded-full font-medium capitalize">
                    {{ value }}
                </span>
            </template>
            <template #cell-is_published="{ value }">
                <span :class="value ? 'text-green-600' : 'text-yellow-600'" class="text-xs">
                    {{ value ? 'Published' : 'Draft' }}
                </span>
            </template>
        </DataTable>

        <ModalDialog v-model="showCreateModal" title="Add Course">
            <form @submit.prevent="createCourse" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                    <input v-model="form.title" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Instructor</label>
                    <input v-model="form.instructor" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Duration (hours)</label>
                        <input v-model.number="form.duration_hours" type="number" min="0.5" step="0.5" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Difficulty</label>
                        <select v-model="form.difficulty" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
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
import type { Course } from '@/types';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const courses = ref<Course[]>([]);
const showCreateModal = ref(false);
const form = reactive({ title: '', instructor: '', duration_hours: 1, difficulty: 'beginner' as Course['difficulty'], description: '' });

const columns = [
    { key: 'title', label: 'Title', sortable: true },
    { key: 'instructor', label: 'Instructor', sortable: true },
    { key: 'duration_hours', label: 'Duration (hrs)', sortable: true },
    { key: 'difficulty', label: 'Difficulty', sortable: true },
    { key: 'is_published', label: 'Status', sortable: true },
];

function difficultyClass(difficulty: string): string {
    const classes: Record<string, string> = {
        beginner: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        intermediate: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        advanced: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return classes[difficulty] || 'bg-gray-100 text-gray-800';
}

async function loadCourses() {
    try {
        const res = await apiClient.get('/courses');
        courses.value = res.data;
    } catch {
        courses.value = [];
    }
}

async function createCourse() {
    try {
        await apiClient.post('/courses', form);
        showCreateModal.value = false;
        notify.success('Course added successfully');
        await loadCourses();
    } catch {
        notify.error('Failed to add course');
    }
}

onMounted(loadCourses);
</script>
