<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg w-full max-w-lg">

      <!-- Loading -->
      <div v-if="loading" class="p-12 text-center text-gray-500">Loading signing request…</div>

      <!-- Error / expired / not found -->
      <div v-else-if="error" class="p-10 text-center space-y-3">
        <div class="w-14 h-14 mx-auto bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">{{ error }}</h2>
        <p class="text-sm text-gray-500">If you believe this is a mistake, please contact the sender.</p>
      </div>

      <!-- Already signed -->
      <div v-else-if="completed === 'signed'" class="p-10 text-center space-y-3">
        <div class="w-14 h-14 mx-auto bg-green-100 rounded-full flex items-center justify-center">
          <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-900">Document Signed</h2>
        <p class="text-sm text-gray-500">Thank you — your signature has been recorded.</p>
      </div>

      <!-- Declined -->
      <div v-else-if="completed === 'declined'" class="p-10 text-center space-y-3">
        <div class="w-14 h-14 mx-auto bg-gray-100 rounded-full flex items-center justify-center">
          <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-900">Signing Declined</h2>
        <p class="text-sm text-gray-500">Your response has been recorded.</p>
      </div>

      <!-- Signing form -->
      <template v-else-if="signingData">
        <!-- Header -->
        <div class="px-8 pt-8 pb-6 border-b border-gray-100">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
              </svg>
            </div>
            <div>
              <h1 class="text-lg font-bold text-gray-900">Signature Request</h1>
              <p class="text-sm text-gray-500">You have been asked to sign a document</p>
            </div>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 space-y-1">
            <div><span class="text-gray-500">To:</span> {{ signingData.signer_name }} ({{ signingData.signer_email }})</div>
            <div v-if="signingData.expires_at">
              <span class="text-gray-500">Expires:</span> {{ formatDate(signingData.expires_at) }}
            </div>
          </div>
          <div v-if="signingData.message" class="mt-3 text-sm text-gray-700 italic">
            "{{ signingData.message }}"
          </div>
        </div>

        <!-- Signature area -->
        <div class="px-8 py-6 space-y-5">
          <!-- Type toggle -->
          <div class="flex rounded-lg border border-gray-200 overflow-hidden">
            <button @click="signatureType = 'drawn'"
              :class="signatureType === 'drawn' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
              class="flex-1 py-2 text-sm font-medium transition-colors">Draw</button>
            <button @click="signatureType = 'typed'"
              :class="signatureType === 'typed' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
              class="flex-1 py-2 text-sm font-medium transition-colors border-l border-gray-200">Type</button>
          </div>

          <!-- Drawn signature canvas -->
          <div v-if="signatureType === 'drawn'">
            <label class="block text-sm font-medium text-gray-700 mb-2">Draw your signature</label>
            <div class="relative border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 overflow-hidden"
              style="height: 160px;">
              <canvas ref="canvasEl" class="absolute inset-0 w-full h-full cursor-crosshair touch-none"
                @mousedown="startDraw" @mousemove="draw" @mouseup="stopDraw" @mouseleave="stopDraw"
                @touchstart.prevent="startDrawTouch" @touchmove.prevent="drawTouch" @touchend="stopDraw"/>
              <div v-if="!hasDrawn" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <span class="text-sm text-gray-400">Sign here</span>
              </div>
            </div>
            <button @click="clearCanvas" class="mt-2 text-xs text-gray-500 hover:text-gray-700">Clear</button>
          </div>

          <!-- Typed signature -->
          <div v-if="signatureType === 'typed'" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Type your full name</label>
            <input v-model="typedSignature" type="text"
              placeholder="Your full legal name"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xl text-center focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              style="font-family: 'Georgia', serif;"/>
            <div v-if="typedSignature" class="text-center text-3xl text-gray-800 py-3" style="font-family: 'Georgia', serif;">
              {{ typedSignature }}
            </div>
          </div>

          <!-- Confirm name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm your full name *</label>
            <input v-model="signerName" type="text" required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
          </div>

          <!-- Legal notice -->
          <p class="text-xs text-gray-400 leading-relaxed">
            By clicking "Sign Document" you agree that your signature is the electronic representation of your signature for all purposes, with the same legal force and effect as a handwritten signature.
          </p>

          <div v-if="signError" class="text-sm text-red-600">{{ signError }}</div>

          <!-- Actions -->
          <div class="flex gap-3">
            <button @click="showDeclineModal = true"
              class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
              Decline
            </button>
            <button @click="submitSignature" :disabled="signing || !canSign"
              class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors">
              {{ signing ? 'Signing…' : 'Sign Document' }}
            </button>
          </div>
        </div>
      </template>

      <!-- Decline modal -->
      <div v-if="showDeclineModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
          <h3 class="font-semibold text-gray-900">Decline signing?</h3>
          <textarea v-model="declineReason" rows="3" placeholder="Reason (optional)"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"/>
          <div class="flex gap-3">
            <button @click="showDeclineModal = false"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
              Cancel
            </button>
            <button @click="submitDecline"
              class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">
              Decline
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const token = route.params.token as string;

const loading = ref(true);
const error = ref('');
const completed = ref('');
const signingData = ref<any>(null);

const signatureType = ref<'drawn' | 'typed'>('drawn');
const typedSignature = ref('');
const signerName = ref('');
const signing = ref(false);
const signError = ref('');
const hasDrawn = ref(false);

const showDeclineModal = ref(false);
const declineReason = ref('');

const canvasEl = ref<HTMLCanvasElement | null>(null);
let ctx: CanvasRenderingContext2D | null = null;
let isDrawing = false;
let lastX = 0;
let lastY = 0;

const canSign = computed(() => {
  if (!signerName.value.trim()) return false;
  if (signatureType.value === 'typed') return !!typedSignature.value.trim();
  return hasDrawn.value;
});

onMounted(async () => {
  try {
    const { data } = await axios.get(`/api/esignatures/sign/${token}`);
    signingData.value = data.data;
    signerName.value = data.data.signer_name ?? '';
    await nextTick();
    initCanvas();
  } catch (e: any) {
    const status = e.response?.status;
    if (status === 409) error.value = 'This request has already been completed.';
    else if (status === 410) error.value = 'This signing request has expired.';
    else if (status === 404) error.value = 'Signing request not found.';
    else error.value = 'Unable to load signing request.';
  } finally {
    loading.value = false;
  }
});

function initCanvas() {
  if (!canvasEl.value) return;
  const rect = canvasEl.value.getBoundingClientRect();
  canvasEl.value.width = rect.width * window.devicePixelRatio;
  canvasEl.value.height = rect.height * window.devicePixelRatio;
  ctx = canvasEl.value.getContext('2d');
  if (ctx) {
    ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
    ctx.strokeStyle = '#1e293b';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
  }
}

function getPos(e: MouseEvent) {
  const rect = canvasEl.value!.getBoundingClientRect();
  return { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

function startDraw(e: MouseEvent) {
  isDrawing = true;
  const { x, y } = getPos(e);
  lastX = x; lastY = y;
}

function draw(e: MouseEvent) {
  if (!isDrawing || !ctx) return;
  const { x, y } = getPos(e);
  ctx.beginPath();
  ctx.moveTo(lastX, lastY);
  ctx.lineTo(x, y);
  ctx.stroke();
  lastX = x; lastY = y;
  hasDrawn.value = true;
}

function stopDraw() { isDrawing = false; }

function startDrawTouch(e: TouchEvent) {
  const rect = canvasEl.value!.getBoundingClientRect();
  const t = e.touches[0];
  isDrawing = true;
  lastX = t.clientX - rect.left;
  lastY = t.clientY - rect.top;
}

function drawTouch(e: TouchEvent) {
  if (!isDrawing || !ctx) return;
  const rect = canvasEl.value!.getBoundingClientRect();
  const t = e.touches[0];
  const x = t.clientX - rect.left;
  const y = t.clientY - rect.top;
  ctx.beginPath();
  ctx.moveTo(lastX, lastY);
  ctx.lineTo(x, y);
  ctx.stroke();
  lastX = x; lastY = y;
  hasDrawn.value = true;
}

function clearCanvas() {
  if (!ctx || !canvasEl.value) return;
  ctx.clearRect(0, 0, canvasEl.value.width, canvasEl.value.height);
  hasDrawn.value = false;
}

async function submitSignature() {
  signing.value = true;
  signError.value = '';
  try {
    const signatureData = signatureType.value === 'drawn'
      ? canvasEl.value?.toDataURL('image/png') ?? ''
      : typedSignature.value;

    await axios.post(`/api/esignatures/sign/${token}`, {
      signature_type: signatureType.value,
      signature_data: signatureData,
      signer_name: signerName.value,
    });

    completed.value = 'signed';
  } catch (e: any) {
    signError.value = e.response?.data?.message ?? 'Failed to submit signature.';
  } finally {
    signing.value = false;
  }
}

async function submitDecline() {
  try {
    await axios.post(`/api/esignatures/sign/${token}/decline`, { reason: declineReason.value });
    showDeclineModal.value = false;
    completed.value = 'declined';
  } catch (e: any) {
    signError.value = e.response?.data?.message ?? 'Failed to decline.';
  }
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-NZ', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>
