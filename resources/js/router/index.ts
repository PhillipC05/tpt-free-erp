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
                path: 'finance/journal-entries',
                name: 'finance.journal-entries',
                component: () => import('@/views/finance/JournalEntriesView.vue'),
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
                path: 'inventory/categories',
                name: 'inventory.categories',
                component: () => import('@/views/inventory/CategoriesView.vue'),
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
                path: 'hr/attendance',
                name: 'hr.attendance',
                component: () => import('@/views/hr/AttendanceView.vue'),
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
                path: 'projects/time-entries',
                name: 'projects.time-entries',
                component: () => import('@/views/projects/TimeEntriesView.vue'),
            },
            {
                path: 'quality/checks',
                name: 'quality.checks',
                component: () => import('@/views/quality/QualityChecksView.vue'),
            },
            {
                path: 'quality/non-conformances',
                name: 'quality.non-conformances',
                component: () => import('@/views/quality/NonConformancesView.vue'),
            },
            {
                path: 'assets/assets',
                name: 'assets.list',
                component: () => import('@/views/assets/AssetsView.vue'),
            },
            {
                path: 'assets/maintenance',
                name: 'assets.maintenance',
                component: () => import('@/views/assets/MaintenanceView.vue'),
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
            {
                path: 'lms/enrollments',
                name: 'lms.enrollments',
                component: () => import('@/views/lms/EnrollmentsView.vue'),
            },
            {
                path: 'reports',
                name: 'reports.builder',
                component: () => import('@/views/reports/ReportsBuilderView.vue'),
            },
            {
                path: 'settings',
                name: 'settings',
                component: () => import('@/views/SettingsView.vue'),
            },
            // Network
            { path: 'network/feed', name: 'network.feed', component: () => import('@/views/network/NetworkFeedView.vue') },
            { path: 'network/discovery', name: 'network.discovery', component: () => import('@/views/network/NetworkDiscoveryView.vue') },
            { path: 'network/profile', name: 'network.profile', component: () => import('@/views/network/MyProfileView.vue') },
            { path: 'network/connections', name: 'network.connections', component: () => import('@/views/network/ConnectionsView.vue') },
            { path: 'network/following', name: 'network.following', component: () => import('@/views/network/FollowingView.vue') },
            // Marketing
            { path: 'marketing/campaigns', name: 'marketing.campaigns', component: () => import('@/views/marketing/CampaignsView.vue') },
            { path: 'marketing/leads', name: 'marketing.leads', component: () => import('@/views/marketing/LeadsView.vue') },
            // Expenses
            { path: 'expenses', name: 'expenses', component: () => import('@/views/expenses/ExpensesView.vue') },
            // Finance budgets
            { path: 'finance/budgets', name: 'finance.budgets', component: () => import('@/views/finance/BudgetsView.vue') },
            // Documents
            { path: 'documents', name: 'documents', component: () => import('@/views/documents/DocumentsView.vue') },
            // Contracts
            { path: 'contracts', name: 'contracts', component: () => import('@/views/contracts/ContractsView.vue') },
            // AI Agents
            { path: 'agents', name: 'agents', component: () => import('@/views/agents/AgentsView.vue') },
            { path: 'agents/skills/catalog', name: 'agents.skill-catalog', component: () => import('@/views/agents/SkillCatalogView.vue') },
            { path: 'agents/:id', name: 'agents.detail', component: () => import('@/views/agents/AgentDetailView.vue') },
            // Scheduled Reports
            { path: 'reports/scheduled', name: 'reports.scheduled', component: () => import('@/views/reports/ScheduledReportsView.vue') },
        ],
    },
    {
        path: '/onboarding',
        name: 'onboarding',
        component: () => import('@/views/onboarding/OnboardingWizardView.vue'),
        meta: { requiresAuth: true },
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

router.beforeEach((to, _from) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }
    if (to.meta.guest && authStore.isAuthenticated) {
        return { name: 'dashboard' };
    }
});

export default router;