import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import NotificationContainer from '@/components/NotificationContainer.vue';
import { useNotificationStore } from '@/stores/notification';

function mountContainer() {
    const pinia = createPinia();
    const wrapper = mount(NotificationContainer, {
        global: { plugins: [pinia] },
    });
    const store = useNotificationStore();
    return { wrapper, store };
}

describe('NotificationContainer', () => {
    it('renders nothing when there are no notifications', () => {
        const { wrapper } = mountContainer();
        expect(wrapper.findAll('[class*="rounded-lg"]')).toHaveLength(0);
    });

    it('renders a success notification with correct styling', async () => {
        const { wrapper, store } = mountContainer();
        store.success('It worked!');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('It worked!');
        expect(wrapper.find('.bg-green-600').exists()).toBe(true);
    });

    it('renders an error notification with correct styling', async () => {
        const { wrapper, store } = mountContainer();
        store.error('Something broke');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Something broke');
        expect(wrapper.find('.bg-red-600').exists()).toBe(true);
    });

    it('removes notification when dismiss button is clicked', async () => {
        const { wrapper, store } = mountContainer();
        store.success('Dismiss me');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Dismiss me');
        await wrapper.find('button').trigger('click');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).not.toContain('Dismiss me');
    });

    it('renders multiple notifications', async () => {
        const { wrapper, store } = mountContainer();
        store.success('First');
        store.error('Second');
        store.warning('Third');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('First');
        expect(wrapper.text()).toContain('Second');
        expect(wrapper.text()).toContain('Third');
    });
});
