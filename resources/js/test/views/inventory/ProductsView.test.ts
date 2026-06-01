import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia } from 'pinia';
import ProductsView from '@/views/inventory/ProductsView.vue';
import apiClient from '@/api/axios';

vi.mock('@/api/axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

const sample = [
    { id: 1, sku: 'SKU001', name: 'Widget A', price: 9.99,  is_active: true },
    { id: 2, sku: 'SKU002', name: 'Gadget B', price: 49.99, is_active: true },
];

describe('ProductsView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(apiClient.get).mockResolvedValue({
            data: { success: true, data: sample, meta: { total: 2 } },
        });
    });

    function mountView() {
        return mount(ProductsView, { global: { plugins: [createPinia()] } });
    }

    it('calls GET /inventory/products on mount', async () => {
        mountView();
        await flushPromises();
        expect(vi.mocked(apiClient.get)).toHaveBeenCalledWith('/inventory/products');
    });

    it('passes an Array to DataTable', async () => {
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(Array.isArray(dt.props('data'))).toBe(true);
        expect(dt.props('data')).toHaveLength(2);
    });

    it('renders product names', async () => {
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.text()).toContain('Widget A');
        expect(wrapper.text()).toContain('Gadget B');
    });

    it('falls back to empty array on API error', async () => {
        vi.mocked(apiClient.get).mockRejectedValue(new Error('Network'));
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.findComponent({ name: 'DataTable' }).props('data')).toEqual([]);
    });
});
