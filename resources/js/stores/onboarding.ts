import { defineStore } from 'pinia'
import apiClient from '@/api/axios'

export const useOnboardingStore = defineStore('onboarding', {
    state: () => ({
        status: 'pending' as 'pending' | 'completed' | 'skipped',
        industryKey: null as string | null,
        presets: [] as any[],
        loading: false,
    }),
    actions: {
        async fetchStatus() {
            const { data } = await apiClient.get('/v1/onboarding/status')
            this.status = data.data?.status ?? 'pending'
            this.industryKey = data.data?.industry_key ?? null
        },
        async fetchPresets() {
            const { data } = await apiClient.get('/v1/onboarding/presets')
            this.presets = data.data ?? []
        },
        async apply(industryKey: string) {
            await apiClient.post('/v1/onboarding/apply', { industry_key: industryKey })
            this.status = 'completed'
            this.industryKey = industryKey
        },
        async skip() {
            await apiClient.post('/v1/onboarding/skip')
            this.status = 'skipped'
        },
    },
})
