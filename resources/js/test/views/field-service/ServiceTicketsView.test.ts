import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia } from 'pinia';
import ServiceTicketsView from '@/views/field-service/ServiceTicketsView.vue';
import apiClient from '@/api/axios';

vi.mock('@/api/axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

const sample = [
    { id: 1, ticket_number: 'TKT-001', title: 'Broken printer', priority: 'high',   status: 'open'     },
    { id: 2, ticket_number: 'TKT-002', title: 'AC not working', priority: 'medium', status: 'assigned' },
];

describe('ServiceTicketsView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(apiClient.get).mockResolvedValue({
            data: { success: true, data: sample, meta: { total: 2 } },
        });
    });

    function mountView() {
        return mount(ServiceTicketsView, { global: { plugins: [createPinia()] } });
    }

    it('calls GET /field-service/tickets on mount', async () => {
        mountView();
        await flushPromises();
        expect(vi.mocked(apiClient.get)).toHaveBeenCalledWith('/field-service/tickets');
    });

    it('passes an Array to DataTable', async () => {
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(Array.isArray(dt.props('data'))).toBe(true);
        expect(dt.props('data')).toHaveLength(2);
    });

    it('renders ticket titles', async () => {
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.text()).toContain('Broken printer');
        expect(wrapper.text()).toContain('AC not working');
    });

    it('falls back to empty array on API error', async () => {
        vi.mocked(apiClient.get).mockRejectedValue(new Error('Network'));
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.findComponent({ name: 'DataTable' }).props('data')).toEqual([]);
    });
});
