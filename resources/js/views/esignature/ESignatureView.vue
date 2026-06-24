<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">E-Signatures</h1>
        <p class="text-sm text-gray-500 mt-1">Request and track document signatures</p>
      </div>
      <button @click="showCreateModal = true"
        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Request
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-3">
      <select v-model="filters.status" @change="loadSignatures(1)"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <option value="">All statuses</option>
        <option value="pending">Pending</option>
        <option value="signed">Signed</option>
        <option value="declined">Declined</option>
        <option value="expired">Expired</option>
      </select>
      <input v-model="filters.signer_email" @input="debouncedLoad" type="email"
        placeholder="Filter by signer email…"
        class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 max-w-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="loading" class="p-12 text-center text-gray-500">Loading…</div>
      <div v-else-if="signatures.length === 0" class="p-12 text-center text-gray-500">No signature requests found.</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Signer</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Document</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Requested</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Expires</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="sig in signatures" :key="sig.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ sig.signer_name }}</div>
              <div class="text-gray-500">{{ sig.signer_email }}</div>
            </td>
            <td class="px-4 py-3 text-gray-700">
              <span class="capitalize">{{ formatSignableType(sig.signable_type) }}</span>
              #{{ sig.signable_id }}
            </td>
            <td class="px-4 py-3">
              <span :class="statusClass(sig.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                {{ sig.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(sig.created_at) }}</td>
            <td class="px-4 py-3 text-gray-500">{{ sig.expires_at ? formatDate(sig.expires_at) : '—' }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2 justify-end">
                <button v-if="sig.status === 'signed'" @click="verifySignature(sig)"
                  class="text-xs text-green-700 hover:text-green-900 font-medium">Verify</button>
                <button @click="copySigningLink(sig)"
                  class="text-xs text-blue-600 hover:text-blue-800 font-medium">Copy link</button>
                <button v-if="sig.status === 'pending'" @click="cancelSignature(sig)"
                  class="text-xs text-red-600 hover:text-red-800 font-medium">Cancel</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
        <span>Page {{ meta.current_page }} of {{ meta.last_page }} — {{ meta.total }} total</span>
        <div class="flex gap-2">
          <button :disabled="meta.current_page === 1" @click="loadSignatures(meta.current_page - 1)"
            class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-100">Prev</button>
          <button :disabled="meta.current_page === meta.last_page" @click="loadSignatures(meta.current_page + 1)"
            class="px-3 py-1 rounded border border-gray-300 disabled:opacity-40 hover:bg-gray-100">Next</button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">New Signature Request</h2>
          <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <form @submit.prevent="createRequest" class="px-6 py-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Signer name *</label>
            <input v-model="form.signer_name" type="text" required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Signer email *</label>
            <input v-model="form.signer_email" type="email" required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Document type *</label>
              <select v-model="form.signable_type" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="contract">Contract</option>
                <option value="document">Document</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Document ID *</label>
              <input v-model.number="form.signable_id" type="number" required min="1"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message to signer</label>
            <textarea v-model="form.message" rows="3"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Expires at (optional)</label>
            <input v-model="form.expires_at" type="datetime-local"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
          </div>
          <div v-if="createError" class="text-sm text-red-600">{{ createError }}</div>
          <div class="flex gap-3 pt-2">
            <button type="button" @click="showCreateModal = false"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="creating"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50">
              {{ creating ? 'Creating…' : 'Create Request' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Verify Modal -->
    <div v-if="verifyResult" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
        <div class="flex items-center gap-3">
          <div :class="verifyResult.intact ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
            class="p-2 rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="verifyResult.intact" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">{{ verifyResult.intact ? 'Document Verified' : 'Tamper Detected' }}</h3>
            <p class="text-sm text-gray-500">{{ verifyResult.intact ? 'Document has not been modified since signing.' : 'Document content has changed since it was signed.' }}</p>
          </div>
        </div>
        <dl class="grid grid-cols-1 gap-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">Signed by</dt>
            <dd class="font-medium text-gray-900">{{ verifyResult.signer_name }} ({{ verifyResult.signer_email }})</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Signed at</dt>
            <dd class="font-medium text-gray-900">{{ formatDate(verifyResult.signed_at) }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Signer IP</dt>
            <dd class="font-mono text-gray-700">{{ verifyResult.signer_ip }}</dd>
          </div>
        </dl>
        <button @click="verifyResult = null"
          class="w-full px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200">Close</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Signature {
  id: number;
  signer_name: string;
  signer_email: string;
  signable_type: string;
  signable_id: number;
  status: string;
  token: string;
  created_at: string;
  expires_at: string | null;
}

const signatures = ref<Signature[]>([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const showCreateModal = ref(false);
const creating = ref(false);
const createError = ref('');
const verifyResult = ref<any>(null);

const filters = ref({ status: '', signer_email: '' });

const form = ref({
  signer_name: '',
  signer_email: '',
  signable_type: 'contract',
  signable_id: null as number | null,
  message: '',
  expires_at: '',
});

let debounceTimer: ReturnType<typeof setTimeout>;
function debouncedLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadSignatures(1), 400);
}

async function loadSignatures(page = 1) {
  loading.value = true;
  try {
    const params: Record<string, any> = { page };
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.signer_email) params.signer_email = filters.value.signer_email;

    const { data } = await axios.get('/api/v1/esignatures', { params });
    signatures.value = data.data;
    meta.value = data.meta;
  } finally {
    loading.value = false;
  }
}

async function createRequest() {
  creating.value = true;
  createError.value = '';
  try {
    const payload: Record<string, any> = { ...form.value };
    if (!payload.expires_at) delete payload.expires_at;
    if (!payload.message) delete payload.message;

    await axios.post('/api/v1/esignatures', payload);
    showCreateModal.value = false;
    resetForm();
    await loadSignatures(1);
  } catch (e: any) {
    createError.value = e.response?.data?.message ?? 'Failed to create request.';
  } finally {
    creating.value = false;
  }
}

async function cancelSignature(sig: Signature) {
  if (!confirm(`Cancel signature request for ${sig.signer_email}?`)) return;
  await axios.delete(`/api/v1/esignatures/${sig.id}`);
  await loadSignatures(meta.value.current_page);
}

async function verifySignature(sig: Signature) {
  const { data } = await axios.get(`/api/v1/esignatures/${sig.id}/verify`);
  verifyResult.value = data.data;
}

function copySigningLink(sig: Signature) {
  const url = `${window.location.origin}/esignatures/sign/${sig.token}`;
  navigator.clipboard.writeText(url);
}

function resetForm() {
  form.value = { signer_name: '', signer_email: '', signable_type: 'contract', signable_id: null, message: '', expires_at: '' };
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-NZ', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatSignableType(type: string) {
  return type.split('\\').pop()?.replace('Contract', 'Contract').toLowerCase() ?? type;
}

function statusClass(status: string) {
  return {
    pending:  'bg-yellow-100 text-yellow-800',
    signed:   'bg-green-100 text-green-800',
    declined: 'bg-red-100 text-red-800',
    expired:  'bg-gray-100 text-gray-600',
  }[status] ?? 'bg-gray-100 text-gray-600';
}

onMounted(() => loadSignatures());
</script>
