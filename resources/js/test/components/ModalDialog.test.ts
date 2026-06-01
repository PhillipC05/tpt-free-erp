import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ModalDialog from '@/components/ModalDialog.vue';

describe('ModalDialog', () => {
    it('renders title and content when modelValue is true', () => {
        const wrapper = mount(ModalDialog, {
            props: { modelValue: true, title: 'Create Account' },
            slots: { default: '<p>Form content</p>' },
        });
        expect(wrapper.text()).toContain('Create Account');
        expect(wrapper.text()).toContain('Form content');
    });

    it('hides content when modelValue is false', () => {
        const wrapper = mount(ModalDialog, {
            props: { modelValue: false, title: 'Hidden Modal' },
            slots: { default: '<p>Should be hidden</p>' },
        });
        expect(wrapper.text()).not.toContain('Hidden Modal');
        expect(wrapper.text()).not.toContain('Should be hidden');
    });

    it('emits update:modelValue=false when close button is clicked', async () => {
        const wrapper = mount(ModalDialog, {
            props: { modelValue: true, title: 'Close Test' },
        });
        // The X close button
        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')![0]).toEqual([false]);
    });

    it('emits update:modelValue=false when backdrop is clicked', async () => {
        const wrapper = mount(ModalDialog, {
            props: { modelValue: true, title: 'Backdrop Test' },
        });
        await wrapper.find('.bg-gray-500').trigger('click');
        expect(wrapper.emitted('update:modelValue')![0]).toEqual([false]);
    });

    it('renders footer slot when provided', () => {
        const wrapper = mount(ModalDialog, {
            props: { modelValue: true, title: 'With Footer' },
            slots: { footer: '<button>Confirm</button>' },
        });
        expect(wrapper.text()).toContain('Confirm');
    });

    it('does not show footer slot wrapper when footer slot is absent', () => {
        const wrapper = mount(ModalDialog, {
            props: { modelValue: true, title: 'No Footer' },
        });
        // The footer div has v-if="$slots.footer" — should not be rendered
        expect(wrapper.find('.sm\\:flex.sm\\:flex-row-reverse').exists()).toBe(false);
    });
});
