import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import DataTable from '@/components/DataTable.vue';

const columns = [
    { key: 'id',   label: 'ID',   sortable: true  },
    { key: 'name', label: 'Name', sortable: true  },
    { key: 'type', label: 'Type', sortable: false },
];

const rows = [
    { id: 1, name: 'Cash',    type: 'asset'   },
    { id: 2, name: 'Revenue', type: 'revenue' },
    { id: 3, name: 'Salary',  type: 'expense' },
];

describe('DataTable', () => {
    it('renders column headers', () => {
        const wrapper = mount(DataTable, { props: { columns, data: rows } });
        columns.forEach(col => expect(wrapper.text()).toContain(col.label));
    });

    it('renders one row per data item', () => {
        const wrapper = mount(DataTable, { props: { columns, data: rows } });
        expect(wrapper.findAll('tbody tr')).toHaveLength(3);
    });

    it('shows "No data available" when data is empty', () => {
        const wrapper = mount(DataTable, { props: { columns, data: [] } });
        expect(wrapper.text()).toContain('No data available');
    });

    it('shows loading spinner when loading=true, hides no-data message', () => {
        const wrapper = mount(DataTable, { props: { columns, data: [], loading: true } });
        expect(wrapper.text()).toContain('Loading...');
        expect(wrapper.text()).not.toContain('No data available');
    });

    it('shows empty table and warns when data is not an array (original response-shape bug)', () => {
        const spy = vi.spyOn(console, 'warn').mockImplementation(() => {});
        const wrapper = mount(DataTable, { props: { columns, data: { success: true, data: rows } as any } });
        // Vue emits a prop type warning
        expect(spy).toHaveBeenCalled();
        // Must not crash — must degrade to an empty table
        expect(wrapper.text()).toContain('No data available');
        spy.mockRestore();
    });

    it('shows search input when searchable=true', () => {
        const wrapper = mount(DataTable, { props: { columns, data: rows, searchable: true } });
        expect(wrapper.find('input[placeholder="Search..."]').exists()).toBe(true);
    });

    it('filters rows by search query', async () => {
        const wrapper = mount(DataTable, { props: { columns, data: rows, searchable: true } });
        await wrapper.find('input[placeholder="Search..."]').setValue('Cash');
        expect(wrapper.findAll('tbody tr')).toHaveLength(1);
        expect(wrapper.text()).toContain('Cash');
        expect(wrapper.text()).not.toContain('Revenue');
    });

    it('resets to page 1 when search query changes', async () => {
        const manyRows = Array.from({ length: 22 }, (_, i) => ({ id: i + 1, name: `Item ${i + 1}`, type: 'x' }));
        const wrapper = mount(DataTable, { props: { columns, data: manyRows, searchable: true, pageSize: 10 } });
        const buttons = wrapper.findAll('button');
        const nextBtn = buttons.find(b => b.text() === 'Next')!;
        await nextBtn.trigger('click');
        expect(wrapper.text()).toContain('Page 2');
        await wrapper.find('input[placeholder="Search..."]').setValue('Item 1');
        expect(wrapper.text()).toContain('Page 1');
    });

    it('sorts ascending on first click, descending on second click', async () => {
        const wrapper = mount(DataTable, { props: { columns, data: rows } });
        const nameHeader = wrapper.findAll('th')[1];
        await nameHeader.trigger('click');
        let cells = wrapper.findAll('tbody td:nth-child(2)');
        expect(cells[0].text()).toBe('Cash');
        await nameHeader.trigger('click');
        cells = wrapper.findAll('tbody td:nth-child(2)');
        expect(cells[0].text()).toBe('Salary');
    });

    it('emits sort event with key and direction', async () => {
        const wrapper = mount(DataTable, { props: { columns, data: rows } });
        await wrapper.findAll('th')[0].trigger('click');
        expect(wrapper.emitted('sort')).toBeTruthy();
        expect(wrapper.emitted('sort')![0]).toEqual(['id', 'asc']);
    });

    it('does not emit sort for non-sortable column', async () => {
        const wrapper = mount(DataTable, { props: { columns, data: rows } });
        await wrapper.findAll('th')[2].trigger('click'); // type — sortable: false
        expect(wrapper.emitted('sort')).toBeFalsy();
    });

    it('paginates: next/prev navigation and disables at boundaries', async () => {
        const manyRows = Array.from({ length: 22 }, (_, i) => ({ id: i + 1, name: `Item ${i + 1}`, type: 'x' }));
        const wrapper = mount(DataTable, { props: { columns, data: manyRows, pageSize: 10 } });
        expect(wrapper.text()).toContain('Page 1 of 3');

        const buttons = wrapper.findAll('button');
        const prevBtn = buttons.find(b => b.text() === 'Previous')!;
        const nextBtn = buttons.find(b => b.text() === 'Next')!;

        expect((prevBtn.element as HTMLButtonElement).disabled).toBe(true);
        await nextBtn.trigger('click');
        expect(wrapper.text()).toContain('Page 2 of 3');
        await prevBtn.trigger('click');
        expect(wrapper.text()).toContain('Page 1 of 3');
    });

    it('resolves nested key paths like product.name', () => {
        const nestedCols = [{ key: 'product.name', label: 'Product', sortable: false }];
        const nestedRows = [{ id: 1, product: { name: 'Widget' } }];
        const wrapper = mount(DataTable, { props: { columns: nestedCols, data: nestedRows } });
        expect(wrapper.text()).toContain('Widget');
    });

    it('renders header slot content', () => {
        const wrapper = mount(DataTable, {
            props: { columns, data: rows },
            slots: { header: '<button>Add Item</button>' },
        });
        expect(wrapper.text()).toContain('Add Item');
    });

    it('renders actions slot per row', () => {
        const wrapper = mount(DataTable, {
            props: { columns, data: rows },
            slots: { actions: '<button>Delete</button>' },
        });
        expect(wrapper.findAll('button[class=""]').length || wrapper.text().split('Delete').length - 1).toBeGreaterThanOrEqual(3);
    });
});
