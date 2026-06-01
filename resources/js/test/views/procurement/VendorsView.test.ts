import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia } from 'pinia';
import VendorsView from '@/views/procurement/VendorsView.vue';
import apiClient from '@/api/axios';

vi.mock('@/api/axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

const sample = [
    { id: 1, name: 'Supplier X', email: 'x@supply.com', is_active: true },
    { id: 2, name: 'Supplier Y', email: 'y@supply.com', is_active: false },
];

describe('VendorsView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(apiClient.get).mockResolvedValue({
            data: { success: true, data: sample, meta: { total: 2 } },
        });
    });

    function mountView() {
        return mount(VendorsView, { global: { plugins: [createPinia()] } });
    }

    it('calls GET /procurement/vendors on mount', async () => {
        mountView();
        await flushPromises();
        expect(vi.mocked(apiClient.get)).toHaveBeenCalledWith('/procurement/vendors');
    });

    it('passes an Array to DataTable', async () => {
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(Array.isArray(dt.props('data'))).toBe(true);
        expect(dt.props('data')).toHaveLength(2);
    });

    it('renders vendor names', async () => {
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.text()).toContain('Supplier X');
    });

    it('falls back to empty array on API error', async () => {
        vi.mocked(apiClient.get).mockRejectedValue(new Error('Network'));
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.findComponent({ name: 'DataTable' }).props('data')).toEqual([]);
    });
});
