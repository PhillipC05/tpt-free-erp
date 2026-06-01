import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia } from 'pinia';
import TransactionsView from '@/views/finance/TransactionsView.vue';
import apiClient from '@/api/axios';

vi.mock('@/api/axios', () => ({
    default: { get: vi.fn(), post: vi.fn() },
}));

const sampleTx = [
    { id: 1, description: 'Deposit', amount: 1000, type: 'credit', date: '2026-01-01' },
    { id: 2, description: 'Withdrawal', amount: 500, type: 'debit', date: '2026-01-02' },
];

describe('TransactionsView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(apiClient.get).mockResolvedValue({
            data: { success: true, data: sampleTx, meta: { total: 2 } },
        });
    });

    function mountView() {
        return mount(TransactionsView, { global: { plugins: [createPinia()] } });
    }

    it('calls GET /finance/transactions on mount', async () => {
        mountView();
        await flushPromises();
        expect(vi.mocked(apiClient.get)).toHaveBeenCalledWith('/finance/transactions');
    });

    it('passes an Array to DataTable, not the raw response object', async () => {
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(Array.isArray(dt.props('data'))).toBe(true);
        expect(dt.props('data')).toHaveLength(2);
    });

    it('renders transaction descriptions', async () => {
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.text()).toContain('Deposit');
        expect(wrapper.text()).toContain('Withdrawal');
    });

    it('falls back to empty array on API error', async () => {
        vi.mocked(apiClient.get).mockRejectedValue(new Error('500'));
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(dt.props('data')).toEqual([]);
    });
});
