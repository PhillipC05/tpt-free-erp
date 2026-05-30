import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import MainLayout from '@/layouts/MainLayout.vue';

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        redirect: '/dashboard',
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/views/auth/RegisterView.vue'),
        meta: { guest: true },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('@/views/auth/ForgotPasswordView.vue'),
        meta: { guest: true },
    },
    {
        path: '/reset-password/:token',
        name: 'reset-password',
        component: () => import('@/views/auth/ResetPasswordView.vue'),
        meta: { guest: true },
    },
    {
        path: '',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'dashboard',
                name: 'dashboard',
                component: () => import('@/views/DashboardView.vue'),
            },
            {
                path: 'profile',
                name: 'profile',
                component: () => import('@/views/ProfileView.vue'),
            },
            {
                path: 'finance/accounts',
                name: 'finance.accounts',
                component: () => import('@/views/finance/AccountsView.vue'),
            },
            {
                path: 'finance/transactions',
                name: 'finance.transactions',
                component: () => import('@/views/finance/TransactionsView.vue'),
            },
            {
                path: 'finance/reports',
                name: 'finance.reports',
                component: () => import('@/views/finance/ReportsView.vue'),
            },
            {
                path: 'inventory/products',
                name: 'inventory.products',
                component: () => import('@/views/inventory/ProductsView.vue'),
            },
            {
                path: 'inventory/warehouses',
                name: 'inventory.warehouses',
                component: () => import('@/views/inventory/WarehousesView.vue'),
            },
            {
                path: 'inventory/stock-movements',
                name: 'inventory.stock-movements',
                component: () => import('@/views/inventory/StockMovementsView.vue'),
            },
            {
                path: 'hr/employees',
                name: 'hr.employees',
                component: () => import('@/views/hr/EmployeesView.vue'),
            },
            {
                path: 'hr/departments',
                name: 'hr.departments',
                component: () => import('@/views/hr/DepartmentsView.vue'),
            },
            {
                path: 'hr/leave-requests',
                name: 'hr.leave-requests',
                component: () => import('@/views/hr/LeaveRequestsView.vue'),
            },
            {
                path: 'hr/payroll',
                name: 'hr.payroll',
                component: () => import('@/views/hr/PayrollView.vue'),
            },
            {
                path: 'sales/customers',
                name: 'sales.customers',
                component: () => import('@/views/sales/CustomersView.vue'),
            },
            {
                path: 'sales/orders',
                name: 'sales.orders',
                component: () => import('@/views/sales/OrdersView.vue'),
            },
            {
                path: 'sales/invoices',
                name: 'sales.invoices',
                component: () => import('@/views/sales/InvoicesView.vue'),
            },
            {
                path: 'sales/crm',
                name: 'sales.crm',
                component: () => import('@/views/sales/CrmView.vue'),
            },
            {
                path: 'procurement/vendors',
                name: 'procurement.vendors',
                component: () => import('@/views/procurement/VendorsView.vue'),
            },
            {
                path: 'procurement/purchase-orders',
                name: 'procurement.purchase-orders',
                component: () => import('@/views/procurement/PurchaseOrdersView.vue'),
            },
            {
                path: 'manufacturing/boms',
                name: 'manufacturing.boms',
                component: () => import('@/views/manufacturing/BomsView.vue'),
            },
            {
                path: 'manufacturing/work-orders',
                name: 'manufacturing.work-orders',
                component: () => import('@/views/manufacturing/WorkOrdersView.vue'),
            },
            {
                path: 'projects/projects',
                name: 'projects.list',
                component: () => import('@/views/projects/ProjectsView.vue'),
            },
            {
                path: 'projects/tasks',
                name: 'projects.tasks',
                component: () => import('@/views/projects/TasksView.vue'),
            },
            {
                path: 'quality/checks',
                name: 'quality.checks',
                component: () => import('@/views/quality/QualityChecksView.vue'),
            },
            {
                path: 'assets/assets',
                name: 'assets.list',
                component: () => import('@/views/assets/AssetsView.vue'),
            },
            {
                path: 'field-service/tickets',
                name: 'field-service.tickets',
                component: () => import('@/views/field-service/ServiceTicketsView.vue'),
            },
            {
                path: 'lms/courses',
                name: 'lms.courses',
                component: () => import('@/views/lms/CoursesView.vue'),
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/errors/NotFoundView.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, _from, next) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } });
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;