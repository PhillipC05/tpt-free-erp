import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia } from 'pinia';
import AccountsView from '@/views/finance/AccountsView.vue';
import apiClient from '@/api/axios';

vi.mock('@/api/axios', () => ({
    default: { get: vi.fn(), post: vi.fn() },
}));

const sampleAccounts = [
    { id: 1, code: '1000', name: 'Cash',  type: 'asset',     balance: 5000,   is_active: true  },
    { id: 2, code: '2000', name: 'Loans', type: 'liability', balance: -10000, is_active: false },
];

const pagedResponse = (items: unknown[]) => ({
    data: { success: true, data: items, meta: { current_page: 1, last_page: 1, per_page: 15, total: items.length } },
});

function mountView() {
    return mount(AccountsView, { global: { plugins: [createPinia()] } });
}

describe('AccountsView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(apiClient.get).mockResolvedValue(pagedResponse(sampleAccounts));
    });

    it('calls GET /finance/accounts on mount', async () => {
        mountView();
        await flushPromises();
        expect(vi.mocked(apiClient.get)).toHaveBeenCalledWith('/finance/accounts');
    });

    it('passes an Array (not the raw response object) to DataTable', async () => {
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(Array.isArray(dt.props('data'))).toBe(true);
    });

    it('unwraps response.data.data and shows account rows', async () => {
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(dt.props('data')).toHaveLength(2);
        expect(wrapper.text()).toContain('Cash');
        expect(wrapper.text()).toContain('Loans');
    });

    it('falls back to empty array on API error', async () => {
        vi.mocked(apiClient.get).mockRejectedValue(new Error('Network error'));
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(dt.props('data')).toEqual([]);
    });

    it('handles flat array response (no .data.data wrapper)', async () => {
        vi.mocked(apiClient.get).mockResolvedValue({ data: sampleAccounts });
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(Array.isArray(dt.props('data'))).toBe(true);
        expect(dt.props('data')).toHaveLength(2);
    });

    it('opens create modal when "Add Account" button is clicked', async () => {
        const wrapper = mountView();
        await flushPromises();
        const addBtn = wrapper.findAll('button').find(b => b.text() === 'Add Account')!;
        await addBtn.trigger('click');
        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('shows inline validation errors on 422 response', async () => {
        vi.mocked(apiClient.post).mockRejectedValue({
            response: {
                status: 422,
                data: {
                    success: false,
                    message: 'Validation failed',
                    errors: {
                        code: ['The code field is required.'],
                        name: ['The name field is required.'],
                    },
                },
            },
        });

        const wrapper = mountView();
        await flushPromises();
        const addBtn = wrapper.findAll('button').find(b => b.text() === 'Add Account')!;
        await addBtn.trigger('click');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(wrapper.text()).toContain('The code field is required.');
        expect(wrapper.text()).toContain('The name field is required.');
    });

    it('closes modal and reloads list on successful create', async () => {
        const created = { id: 3, code: '3000', name: 'New Equity', type: 'equity', balance: 0, is_active: true };
        vi.mocked(apiClient.post).mockResolvedValue({ data: { success: true, data: created } });
        vi.mocked(apiClient.get)
            .mockResolvedValueOnce(pagedResponse(sampleAccounts))
            .mockResolvedValueOnce(pagedResponse([...sampleAccounts, created]));

        const wrapper = mountView();
        await flushPromises();

        const addBtn = wrapper.findAll('button').find(b => b.text() === 'Add Account')!;
        await addBtn.trigger('click');
        expect(wrapper.find('form').exists()).toBe(true);

        await wrapper.find('form').trigger('submit');
        await flushPromises();

        // POST was made with the form data object
        expect(vi.mocked(apiClient.post)).toHaveBeenCalledWith('/finance/accounts', expect.any(Object));
        // Modal closes after success
        expect(wrapper.find('form').exists()).toBe(false);
        // List reloaded after create
        expect(vi.mocked(apiClient.get)).toHaveBeenCalledTimes(2);
    });
});
