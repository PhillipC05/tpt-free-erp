import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia } from 'pinia';
import CoursesView from '@/views/lms/CoursesView.vue';
import apiClient from '@/api/axios';

vi.mock('@/api/axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

const sample = [
    { id: 1, title: 'Intro to ERP',     type: 'online',    is_published: true  },
    { id: 2, title: 'Advanced Finance', type: 'classroom', is_published: false },
];

describe('CoursesView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(apiClient.get).mockResolvedValue({
            data: { success: true, data: sample, meta: { total: 2 } },
        });
    });

    function mountView() {
        return mount(CoursesView, { global: { plugins: [createPinia()] } });
    }

    it('calls GET /lms/courses on mount', async () => {
        mountView();
        await flushPromises();
        expect(vi.mocked(apiClient.get)).toHaveBeenCalledWith('/lms/courses');
    });

    it('passes an Array to DataTable', async () => {
        const wrapper = mountView();
        await flushPromises();
        const dt = wrapper.findComponent({ name: 'DataTable' });
        expect(Array.isArray(dt.props('data'))).toBe(true);
        expect(dt.props('data')).toHaveLength(2);
    });

    it('renders course titles', async () => {
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.text()).toContain('Intro to ERP');
        expect(wrapper.text()).toContain('Advanced Finance');
    });

    it('falls back to empty array on API error', async () => {
        vi.mocked(apiClient.get).mockRejectedValue(new Error('Network'));
        const wrapper = mountView();
        await flushPromises();
        expect(wrapper.findComponent({ name: 'DataTable' }).props('data')).toEqual([]);
    });
});
