<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">My Documents</h1>
                <p class="text-sm text-gray-500">Manage your personal documents &middot; {{ meta.total_size_mb }} MB used</p>
            </div>
            <label class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 cursor-pointer">
                Upload Document
                <input type="file" ref="fileInput" @change="handleUpload" class="hidden" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.csv,.xls,.xlsx" />
            </label>
        </div>

        <div class="flex items-center gap-3 mb-4">
            <button @click="filterCategory = ''" :class="filterCategory === '' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-3 py-1.5 text-xs rounded-full">All ({{ meta.total }})</button>
            <button v-for="c in categories" :key="c.category" @click="filterCategory = c.category" :class="filterCategory === c.category ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" class="px-3 py-1.5 text-xs rounded-full">{{ c.category }} ({{ c.count }})</button>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <input v-model="search" type="text" placeholder="Search documents..." class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm" />
            <select v-model="filterType" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                <option value="">All Types</option>
                <option value="application/pdf">PDF</option>
                <option value="image">Images</option>
                <option value="application/msword">Word</option>
            </select>
        </div>

        <div v-if="documents.length === 0 && !loading" class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <div class="text-gray-400 mb-2">
                <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
            <p class="text-gray-500">No documents uploaded yet</p>
            <p class="text-xs text-gray-400 mt-1">Upload your first document using the button above</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="doc in documents" :key="doc.id" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" :class="fileIconClass(doc.mime_type)">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" /></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ doc.name }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ formatSize(doc.file_size) }} &middot; {{ formatDate(doc.created_at) }}</div>
                        <div v-if="doc.tags?.length" class="flex gap-1 mt-2 flex-wrap">
                            <span v-for="t in doc.tags" :key="t" class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ t }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <a :href="`/api/v1/self-service/documents/${doc.id}/download`" class="text-xs text-blue-600 hover:text-blue-800" target="_blank">Download</a>
                        <button @click="deleteDocument(doc.id)" class="text-xs text-red-600 hover:text-red-800">Delete</button>
                    </div>
                </div>
                <div v-if="doc.description" class="mt-2 text-xs text-gray-500 truncate">{{ doc.description }}</div>
            </div>
        </div>

        <div v-if="loading" class="text-center py-8 text-gray-500">Loading...</div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();
const documents = ref<any[]>([]);
const categories = ref<any[]>([]);
const meta = ref({ total: 0, total_size_mb: 0 });
const search = ref('');
const filterCategory = ref('');
const filterType = ref('');
const loading = ref(true);
const fileInput = ref<HTMLInputElement | null>(null);

function formatSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString();
}

function fileIconClass(mime: string): string {
    if (mime?.includes('pdf')) return 'bg-red-100 text-red-600';
    if (mime?.includes('image')) return 'bg-blue-100 text-blue-600';
    if (mime?.includes('word') || mime?.includes('document')) return 'bg-blue-100 text-blue-600';
    if (mime?.includes('spreadsheet') || mime?.includes('excel')) return 'bg-green-100 text-green-600';
    return 'bg-gray-100 text-gray-600';
}

async function loadDocuments() {
    loading.value = true;
    try {
        const params: Record<string, string> = { per_page: '50' };
        if (search.value) params.search = search.value;
        if (filterCategory.value) params.tag = filterCategory.value;
        if (filterType.value) params.mime_type = filterType.value;

        const res = await apiClient.get('/self-service/documents', { params });
        documents.value = res.data?.data ?? [];
        meta.value = res.data?.meta ?? { total: 0, total_size_mb: 0 };
    } catch {
        documents.value = [];
    } finally {
        loading.value = false;
    }
}

async function loadCategories() {
    try {
        const res = await apiClient.get('/self-service/documents/categories');
        categories.value = res.data?.data ?? [];
    } catch { /* ignore */ }
}

async function handleUpload(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('category', 'general');

    try {
        await apiClient.post('/self-service/documents', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        notify.success('Document uploaded');
        await loadDocuments();
        await loadCategories();
    } catch {
        notify.error('Failed to upload document');
    } finally {
        input.value = '';
    }
}

async function deleteDocument(id: number) {
    if (!confirm('Delete this document?')) return;
    try {
        await apiClient.delete(`/self-service/documents/${id}`);
        notify.success('Document deleted');
        await loadDocuments();
        await loadCategories();
    } catch {
        notify.error('Failed to delete');
    }
}

watch([search, filterCategory, filterType], loadDocuments);

onMounted(() => {
    loadDocuments();
    loadCategories();
});
</script>
