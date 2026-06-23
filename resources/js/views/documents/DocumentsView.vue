<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Documents</h1>

        <div class="flex gap-6">
            <!-- Folder sidebar -->
            <aside class="w-56 flex-shrink-0">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Folders</h2>
                        <button @click="showFolderModal = true" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">+ New</button>
                    </div>
                    <div v-if="loadingFolders" class="text-xs text-gray-400">Loading...</div>
                    <ul v-else class="space-y-1">
                        <li>
                            <button
                                @click="selectedFolderId = null"
                                :class="[
                                    'w-full text-left px-2 py-1.5 text-sm rounded transition-colors',
                                    selectedFolderId === null
                                        ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                ]"
                            >All Documents</button>
                        </li>
                        <li v-for="folder in folders" :key="folder.id">
                            <button
                                @click="selectedFolderId = folder.id"
                                :class="[
                                    'w-full text-left px-2 py-1.5 text-sm rounded transition-colors',
                                    selectedFolderId === folder.id
                                        ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                ]"
                            >
                                📁 {{ folder.name }}
                            </button>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Main content -->
            <div class="flex-1 min-w-0">
                <DataTable :columns="columns" :data="documents" searchable>
                    <template #header>
                        <button @click="openUpload" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            Upload Document
                        </button>
                    </template>
                    <template #cell-file_size="{ value }">
                        {{ formatSize(Number(value ?? 0)) }}
                    </template>
                    <template #cell-actions="{ row }">
                        <a
                            v-if="(row as any).storage_path"
                            :href="`/storage/${(row as any).storage_path}`"
                            target="_blank"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            Download
                        </a>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- New Folder Modal -->
        <ModalDialog v-model="showFolderModal" title="New Folder">
            <form @submit.prevent="createFolder" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Folder Name</label>
                    <input v-model="folderForm.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showFolderModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" :disabled="savingFolder" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ savingFolder ? 'Creating...' : 'Create' }}
                    </button>
                </div>
            </form>
        </ModalDialog>

        <!-- Upload Document Modal -->
        <ModalDialog v-model="showUploadModal" title="Upload Document">
            <form @submit.prevent="uploadDocument" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input v-model="uploadForm.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea v-model="uploadForm.description" rows="2" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tags (comma-separated)</label>
                    <input v-model="uploadForm.tags" type="text" placeholder="e.g. contract, legal, 2026" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Original Filename</label>
                        <input v-model="uploadForm.original_filename" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">MIME Type</label>
                        <input v-model="uploadForm.mime_type" type="text" placeholder="application/pdf" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Storage Path</label>
                        <input v-model="uploadForm.storage_path" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">File Size (bytes)</label>
                        <input v-model.number="uploadForm.file_size" type="number" min="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" :disabled="uploading" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ uploading ? 'Uploading...' : 'Upload' }}
                    </button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue';
import DataTable from '@/components/DataTable.vue';
import ModalDialog from '@/components/ModalDialog.vue';
import apiClient from '@/api/axios';
import { useNotificationStore } from '@/stores/notification';

const notify = useNotificationStore();

interface Folder { id: number; name: string; }
interface Document {
    id: number;
    name: string;
    mime_type?: string;
    file_size?: number;
    uploaded_by?: string;
    uploaded_at?: string;
    storage_path?: string;
}

const folders = ref<Folder[]>([]);
const documents = ref<Document[]>([]);
const selectedFolderId = ref<number | null>(null);
const loadingFolders = ref(false);

const showFolderModal = ref(false);
const savingFolder = ref(false);
const folderForm = reactive({ name: '' });

const showUploadModal = ref(false);
const uploading = ref(false);
const uploadForm = reactive({
    name: '', description: '', tags: '',
    original_filename: '', storage_path: '', mime_type: '', file_size: 0,
});

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'mime_type', label: 'Type', sortable: true },
    { key: 'file_size', label: 'Size', sortable: true },
    { key: 'uploaded_by', label: 'Uploaded By', sortable: true },
    { key: 'uploaded_at', label: 'Uploaded', sortable: true },
    { key: 'actions', label: '' },
];

function formatSize(bytes: number): string {
    if (bytes === 0) return '—';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function openUpload() {
    Object.assign(uploadForm, { name: '', description: '', tags: '', original_filename: '', storage_path: '', mime_type: '', file_size: 0 });
    showUploadModal.value = true;
}

async function loadFolders() {
    loadingFolders.value = true;
    try {
        const res = await apiClient.get('/v1/documents/folders');
        folders.value = res.data?.data ?? [];
    } catch {
        folders.value = [];
    } finally {
        loadingFolders.value = false;
    }
}

async function loadDocuments() {
    try {
        const params = selectedFolderId.value ? { folder_id: selectedFolderId.value } : {};
        const res = await apiClient.get('/v1/documents', { params });
        documents.value = res.data?.data ?? [];
    } catch {
        documents.value = [];
    }
}

async function createFolder() {
    savingFolder.value = true;
    try {
        await apiClient.post('/v1/documents/folders', folderForm);
        notify.success('Folder created');
        showFolderModal.value = false;
        folderForm.name = '';
        await loadFolders();
    } catch {
        notify.error('Failed to create folder');
    } finally {
        savingFolder.value = false;
    }
}

async function uploadDocument() {
    uploading.value = true;
    try {
        const payload = {
            ...uploadForm,
            tags: uploadForm.tags ? uploadForm.tags.split(',').map(t => t.trim()).filter(Boolean) : [],
            folder_id: selectedFolderId.value,
        };
        await apiClient.post('/v1/documents', payload);
        notify.success('Document uploaded');
        showUploadModal.value = false;
        await loadDocuments();
    } catch {
        notify.error('Failed to upload document');
    } finally {
        uploading.value = false;
    }
}

watch(selectedFolderId, loadDocuments);

onMounted(async () => {
    await loadFolders();
    await loadDocuments();
});
</script>
