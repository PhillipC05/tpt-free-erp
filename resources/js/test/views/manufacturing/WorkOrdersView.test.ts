import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia } from 'pinia';
import WorkOrdersView from '@/views/manufacturing/WorkOrdersView.vue';
import apiClient from '@/api/axios';

vi.mock('@/api/axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

const sample = [
    { id: 1, work_order_number: 'WO-001', status: 'planned',     quantity: 100 },
    { id: 2, work_order_number: 'WO-002', status: 'in_progress', quantity: 50  },
];

describe('WorkOrdersView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(apiClient.get).mockResolvedValue({
            data: { success: true, data: sample, meta: { total: 2 } },
        });
    });

    function mountView() {
        return mount(WorkOrdersView, { global: { plugins: [createPinia()] } });
    }

    it('calls GET /manufacturing/work-orders on mount', async () => {
        mountView();
        await flushPromises();
        expect(vi.mocked(apiClient.get)).toHaveBeenCalledWith('/manufacturing/work-orders');
    });

    it('passes an Array to DataTable', async () => {
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(Array.isArray(dt.props('data'))).toBe(true);
        expect(dt.props('data')).toHaveLength(2);
    });

    it('renders work order numbers', async () => {
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.text()).toContain('WO-001');
    });

    it('falls back to empty array on API error', async () => {
        vi.mocked(apiClient.get).mockRejectedValue(new Error('Network'));
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.findComponent({ name: 'DataTable' }).props('data')).toEqual([]);
    });
});
