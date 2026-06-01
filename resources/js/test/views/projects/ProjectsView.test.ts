import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia } from 'pinia';
import ProjectsView from '@/views/projects/ProjectsView.vue';
import apiClient from '@/api/axios';

vi.mock('@/api/axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

const sample = [
    { id: 1, code: 'PROJ-01', name: 'ERP Upgrade',    status: 'active',   budget: 50000 },
    { id: 2, code: 'PROJ-02', name: 'Mobile App',     status: 'planning', budget: 20000 },
];

describe('ProjectsView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(apiClient.get).mockResolvedValue({
            data: { success: true, data: sample, meta: { total: 2 } },
        });
    });

    function mountView() {
        return mount(ProjectsView, { global: { plugins: [createPinia()] } });
    }

    it('calls GET /projects/projects on mount', async () => {
        mountView();
        await flushPromises();
        expect(vi.mocked(apiClient.get)).toHaveBeenCalledWith('/projects/projects');
    });

    it('passes an Array to DataTable', async () => {
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(Array.isArray(dt.props('data'))).toBe(true);
        expect(dt.props('data')).toHaveLength(2);
    });

    it('renders project names', async () => {
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.text()).toContain('ERP Upgrade');
        expect(wrapper.text()).toContain('Mobile App');
    });

    it('falls back to empty array on API error', async () => {
        vi.mocked(apiClient.get).mockRejectedValue(new Error('Network'));
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.findComponent({ name: 'DataTable' }).props('data')).toEqual([]);
    });
});
