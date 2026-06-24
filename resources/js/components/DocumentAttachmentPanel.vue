<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Attachments</h3>
            <label class="cursor-pointer">
                <input type="file" class="hidden" @change="handleFileUpload" :disabled="uploading" />
                <span
                    class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                    :class="{ 'opacity-50 cursor-not-allowed': uploading }"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ uploading ? 'Uploading…' : 'Attach' }}
                </span>
            </label>
        </div>

        <div v-if="loading" class="text-xs text-gray-400 py-2">Loading…</div>

        <div v-else-if="documents.length === 0" class="text-xs text-gray-400 py-2 italic">
            No attachments yet.
        </div>

        <ul v-else class="space-y-2">
            <li
                v-for="doc in documents"
                :key="doc.id"
                class="flex items-center justify-between gap-2 text-sm"
            >
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="truncate text-gray-700 dark:text-gray-300" :title="doc.name">{{ doc.name }}</span>
                    <span class="text-xs text-gray-400 shrink-0">{{ formatSize(doc.file_size) }}</span>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button
                        @click="downloadDoc(doc)"
                        class="p-1 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                        title="Download"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </button>
                    <button
                        @click="deleteDoc(doc)"
                        class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                        title="Delete"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </li>
        </ul>

        <p v-if="error" class="mt-2 text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import apiClient from '@/api/axios';

interface Document {
    id: number;
    name: string;
    original_filename: string;
    file_size: number;
    mime_type: string;
    storage_path: string;
}

const props = defineProps<{
    documentableType: string;
    documentableId: number | null;
}>();

const documents = ref<Document[]>([]);
const loading = ref(false);
const uploading = ref(false);
const error = ref('');

async function fetchDocuments() {
    if (!props.documentableId) return;
    loading.value = true;
    error.value = '';
    try {
        const res = await apiClient.get('/v1/documents', {
            params: {
                documentable_type: props.documentableType,
                documentable_id: props.documentableId,
                per_page: 50,
            },
        });
        documents.value = res.data.data ?? [];
    } catch {
        error.value = 'Failed to load attachments.';
    } finally {
        loading.value = false;
    }
}

async function handleFileUpload(event: Event) {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length || !props.documentableId) return;

    uploading.value = true;
    error.value = '';
    const form = new FormData();
    form.append('file', input.files[0]);
    form.append('documentable_type', props.documentableType);
    form.append('documentable_id', String(props.documentableId));

    try {
        await apiClient.post('/v1/documents', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        await fetchDocuments();
    } catch {
        error.value = 'Upload failed. Max 50 MB.';
    } finally {
        uploading.value = false;
        input.value = '';
    }
}

async function downloadDoc(doc: Document) {
    try {
        const res = await apiClient.get(`/v1/documents/${doc.id}/download`, {
            responseType: 'blob',
        });
        const url = URL.createObjectURL(new Blob([res.data]));
        const a = Object.assign(document.createElement('a'), { href: url, download: doc.original_filename });
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    } catch {
        error.value = 'Download failed.';
    }
}

async function deleteDoc(doc: Document) {
    if (!confirm(`Delete "${doc.name}"?`)) return;
    try {
        await apiClient.delete(`/v1/documents/${doc.id}`);
        documents.value = documents.value.filter(d => d.id !== doc.id);
    } catch {
        error.value = 'Delete failed.';
    }
}

function formatSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

watch(() => props.documentableId, () => fetchDocuments());
onMounted(() => fetchDocuments());
</script>
