<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
        <!-- Top bar -->
        <div class="flex justify-end px-6 py-4">
            <button
                @click="skipSetup"
                :disabled="skipping"
                class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline"
            >
                {{ skipping ? 'Skipping...' : 'Skip setup' }}
            </button>
        </div>

        <!-- Step indicator -->
        <div class="flex justify-center mb-8 gap-2">
            <div
                v-for="n in totalSteps"
                :key="n"
                :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors',
                    n < currentStep
                        ? 'bg-blue-600 text-white'
                        : n === currentStep
                            ? 'bg-blue-600 text-white ring-4 ring-blue-100 dark:ring-blue-900'
                            : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
                ]"
            >{{ n }}</div>
        </div>

        <!-- Step content -->
        <div class="flex-1 flex items-start justify-center px-4">
            <div class="w-full max-w-3xl">

                <!-- Step 1: Welcome -->
                <div v-if="currentStep === 1" class="text-center py-16">
                    <div class="text-6xl mb-6">🚀</div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">Welcome to TPT Free ERP</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-10">Let's get you set up in 2 minutes.</p>
                    <button
                        @click="currentStep = 2"
                        class="px-8 py-3 bg-blue-600 text-white text-base font-medium rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Get Started
                    </button>
                </div>

                <!-- Step 2: Industry selection -->
                <div v-if="currentStep === 2">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">Select your industry</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-center mb-6">We'll configure the modules that matter most to you.</p>

                    <div v-if="loadingPresets" class="flex justify-center py-12">
                        <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
                    </div>

                    <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
                        <button
                            v-for="preset in allPresets"
                            :key="preset.industry_key"
                            @click="selectedIndustryKey = preset.industry_key"
                            :class="[
                                'flex flex-col items-center gap-2 p-4 rounded-lg border-2 text-center transition-colors',
                                selectedIndustryKey === preset.industry_key
                                    ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-blue-300 dark:hover:border-blue-600'
                            ]"
                        >
                            <span class="text-3xl">{{ preset.icon_emoji }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ preset.industry_name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ preset.description }}</span>
                        </button>
                    </div>

                    <div class="flex justify-between">
                        <button @click="currentStep = 1" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Back</button>
                        <button
                            @click="currentStep = 3"
                            :disabled="!selectedIndustryKey"
                            class="px-6 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                </div>

                <!-- Step 3: Preview -->
                <div v-if="currentStep === 3">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">Here's what we'll set up</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-center mb-8">Based on your industry selection</p>

                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
                        <div class="flex items-center gap-4 mb-6">
                            <span class="text-4xl">{{ selectedPreset?.icon_emoji }}</span>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ selectedPreset?.industry_name }}</h3>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">{{ selectedPreset?.description }}</p>
                            </div>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">Recommended Modules</h4>
                        <ul class="space-y-2">
                            <li
                                v-for="mod in selectedPreset?.recommended_modules ?? []"
                                :key="mod"
                                class="flex items-center gap-2 text-gray-700 dark:text-gray-300"
                            >
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ formatModule(mod) }}
                            </li>
                        </ul>
                    </div>

                    <div class="flex justify-between">
                        <button @click="currentStep = 2" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Back</button>
                        <button
                            @click="currentStep = 4"
                            class="px-6 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                        >
                            Looks good!
                        </button>
                    </div>
                </div>

                <!-- Step 4: Setup -->
                <div v-if="currentStep === 4">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2 text-center">Company details</h2>
                    <p class="text-gray-500 dark:text-gray-400 text-center mb-8">A few quick details to finish setup.</p>

                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
                        <form @submit.prevent="applySetup" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Company Name</label>
                                <input
                                    v-model="setupForm.companyName"
                                    type="text"
                                    required
                                    placeholder="Acme Corp"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Base Currency</label>
                                <input
                                    v-model="setupForm.currency"
                                    type="text"
                                    maxlength="3"
                                    placeholder="NZD"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 uppercase"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Financial Year Start</label>
                                <select
                                    v-model="setupForm.fyStart"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="01">January</option>
                                    <option value="04">April</option>
                                    <option value="07">July</option>
                                    <option value="10">October</option>
                                </select>
                            </div>
                            <p v-if="setupError" class="text-sm text-red-600 dark:text-red-400">{{ setupError }}</p>
                        </form>
                    </div>

                    <div class="flex justify-between">
                        <button @click="currentStep = 3" class="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300">Back</button>
                        <button
                            @click="applySetup"
                            :disabled="applying"
                            class="px-6 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <div v-if="applying" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                            {{ applying ? 'Setting up...' : 'Set Up Now' }}
                        </button>
                    </div>
                </div>

                <!-- Step 5: Done -->
                <div v-if="currentStep === 5" class="text-center py-16">
                    <div class="text-6xl mb-6">✅</div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">You're all set!</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-10">Your ERP is configured and ready to go.</p>
                    <button
                        @click="router.push('/dashboard')"
                        class="px-8 py-3 bg-blue-600 text-white text-base font-medium rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Go to Dashboard
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useOnboardingStore } from '@/stores/onboarding';

const router = useRouter();
const onboardingStore = useOnboardingStore();

const currentStep = ref(1);
const totalSteps = 5;
const selectedIndustryKey = ref<string | null>(null);
const loadingPresets = ref(false);
const applying = ref(false);
const skipping = ref(false);
const setupError = ref('');

const setupForm = reactive({
    companyName: '',
    currency: 'NZD',
    fyStart: '01',
});

const customSkipPreset = {
    industry_key: 'custom',
    industry_name: 'Custom / Skip',
    icon_emoji: '🔧',
    description: 'Configure modules manually later',
    recommended_modules: [],
};

const allPresets = computed(() => [...onboardingStore.presets, customSkipPreset]);

const selectedPreset = computed(() =>
    allPresets.value.find(p => p.industry_key === selectedIndustryKey.value) ?? null
);

function formatModule(mod: string): string {
    return mod.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

async function applySetup() {
    if (!selectedIndustryKey.value) return;
    applying.value = true;
    setupError.value = '';
    try {
        await onboardingStore.apply(selectedIndustryKey.value);
        currentStep.value = 5;
    } catch {
        setupError.value = 'Failed to apply setup. Please try again.';
    } finally {
        applying.value = false;
    }
}

async function skipSetup() {
    skipping.value = true;
    try {
        await onboardingStore.skip();
        router.push('/dashboard');
    } catch {
        // still redirect
        router.push('/dashboard');
    } finally {
        skipping.value = false;
    }
}

onMounted(async () => {
    loadingPresets.value = true;
    try {
        await onboardingStore.fetchPresets();
    } finally {
        loadingPresets.value = false;
    }
});
</script>
