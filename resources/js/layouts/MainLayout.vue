<template>
    <div class="flex h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-30 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]">
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <router-link to="/dashboard" class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    TPT ERP
                </router-link>
                <button @click="toggleSidebar" class="lg:hidden text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-2 py-4 space-y-0.5">
                <template v-for="item in visibleNav" :key="item.label">
                    <!-- Single link (Dashboard, Reports, Field Service) -->
                    <router-link
                        v-if="!item.children"
                        :to="item.to!"
                        :class="[
                            'flex items-center gap-3 px-3 py-2 text-sm rounded-md transition-colors w-full',
                            route.path === item.to || (item.to !== '/dashboard' && route.path.startsWith(item.to!))
                                ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        <span v-html="item.icon" class="w-5 h-5 flex-shrink-0" />
                        <span>{{ item.label }}</span>
                    </router-link>

                    <!-- Expandable group -->
                    <div v-else>
                        <button
                            @click="toggleGroup(item.label)"
                            :class="[
                                'flex items-center gap-3 px-3 py-2 text-sm rounded-md transition-colors w-full',
                                isGroupActive(item.prefix!)
                                    ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]"
                        >
                            <span v-html="item.icon" class="w-5 h-5 flex-shrink-0" />
                            <span class="flex-1 text-left">{{ item.label }}</span>
                            <svg
                                class="w-4 h-4 flex-shrink-0 transition-transform duration-200"
                                :class="isGroupOpen(item.label) ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="isGroupOpen(item.label)" class="ml-4 mt-0.5 mb-1 pl-3 border-l border-gray-200 dark:border-gray-700 space-y-0.5">
                            <router-link
                                v-for="child in item.children"
                                :key="child.to"
                                :to="child.to"
                                :class="[
                                    'flex items-center gap-2 px-3 py-1.5 text-sm rounded-md transition-colors',
                                    route.path === child.to
                                        ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-200'
                                ]"
                            >
                                {{ child.label }}
                            </router-link>
                        </div>
                    </div>
                </template>
            </nav>
        </aside>

        <!-- Overlay for mobile -->
        <div
            v-if="sidebarOpen"
            @click="toggleSidebar"
            class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
        />

        <!-- Main content area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top navbar -->
            <header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="toggleSidebar" class="lg:hidden text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <Breadcrumbs />
                </div>

                <div class="flex items-center gap-3">
                    <!-- Notification Bell -->
                    <div class="relative" ref="notifRef">
                        <button
                            @click="toggleNotifications"
                            class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            aria-label="Notifications"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span
                                v-if="unreadCount > 0"
                                class="absolute top-1 right-1 flex items-center justify-center w-4 h-4 text-xs font-bold text-white bg-red-500 rounded-full"
                            >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                        </button>

                        <!-- Notification dropdown -->
                        <div
                            v-if="notificationsOpen"
                            class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
                        >
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Notifications</h3>
                                <button
                                    v-if="unreadCount > 0"
                                    @click="markAllRead"
                                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                                >Mark all read</button>
                            </div>

                            <div class="max-h-80 overflow-y-auto">
                                <div v-if="notificationsLoading" class="flex items-center justify-center py-8">
                                    <div class="w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
                                </div>
                                <div v-else-if="notifications.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No notifications
                                </div>
                                <div v-else>
                                    <div
                                        v-for="n in notifications"
                                        :key="n.id"
                                        :class="[
                                            'flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer border-b border-gray-100 dark:border-gray-700/50 last:border-0',
                                            !n.read_at ? 'bg-blue-50/50 dark:bg-blue-900/10' : ''
                                        ]"
                                        @click="markRead(n)"
                                    >
                                        <div :class="['mt-0.5 w-2 h-2 rounded-full flex-shrink-0', !n.read_at ? 'bg-blue-500' : 'bg-transparent']" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ n.data?.title || n.type }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ n.data?.message || '' }}</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatTimeAgo(n.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs text-center text-gray-400 dark:text-gray-500">{{ notifications.length }} notification{{ notifications.length !== 1 ? 's' : '' }}</p>
                            </div>
                        </div>
                    </div>

                    <router-link to="/profile" class="text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                        {{ authStore.userData?.name || 'User' }}
                    </router-link>
                    <button @click="logout" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                        Logout
                    </button>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <div class="max-w-7xl mx-auto">
                    <router-view />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import api from '@/api/axios';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

// ── Sidebar ─────────────────────────────────────────────────────────────────
const sidebarOpen = ref(false);

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}

async function logout() {
    await authStore.logout();
    router.push({ name: 'login' });
}

// ── Navigation groups ────────────────────────────────────────────────────────
interface NavChild { to: string; label: string }
interface NavGroup {
    label: string;
    icon: string;
    to?: string;
    prefix?: string;
    children?: NavChild[];
    roles?: string[];  // if set, user must have at least one of these roles (or no roles = show all)
}

const navigationGroups: NavGroup[] = [
    {
        label: 'Dashboard',
        to: '/dashboard',
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>',
    },
    {
        label: 'Finance',
        prefix: '/finance',
        roles: ['finance', 'finance_manager', 'accountant', 'cfo'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        children: [
            { to: '/finance/accounts', label: 'Chart of Accounts' },
            { to: '/finance/transactions', label: 'Transactions' },
            { to: '/finance/journal-entries', label: 'Journal Entries' },
            { to: '/finance/reports', label: 'Financial Reports' },
            { to: '/finance/budgets', label: 'Budgets' },
        ],
    },
    {
        label: 'Inventory',
        prefix: '/inventory',
        roles: ['inventory', 'inventory_manager', 'warehouse', 'warehouse_manager'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>',
        children: [
            { to: '/inventory/products', label: 'Products' },
            { to: '/inventory/categories', label: 'Categories' },
            { to: '/inventory/warehouses', label: 'Warehouses' },
            { to: '/inventory/stock-movements', label: 'Stock Movements' },
        ],
    },
    {
        label: 'HR',
        prefix: '/hr',
        roles: ['hr', 'hr_manager', 'payroll'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
        children: [
            { to: '/hr/employees', label: 'Employees' },
            { to: '/hr/departments', label: 'Departments' },
            { to: '/hr/attendance', label: 'Attendance' },
            { to: '/hr/leave-requests', label: 'Leave Requests' },
            { to: '/hr/payroll', label: 'Payroll' },
        ],
    },
    {
        label: 'Sales',
        prefix: '/sales',
        roles: ['sales', 'sales_manager', 'crm', 'account_manager'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>',
        children: [
            { to: '/sales/customers', label: 'Customers' },
            { to: '/sales/orders', label: 'Orders' },
            { to: '/sales/invoices', label: 'Invoices' },
            { to: '/sales/crm', label: 'CRM Pipeline' },
        ],
    },
    {
        label: 'Marketing',
        prefix: '/marketing',
        roles: ['marketing', 'marketing_manager'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>',
        children: [
            { to: '/marketing/campaigns', label: 'Campaigns' },
            { to: '/marketing/leads', label: 'Leads' },
        ],
    },
    {
        label: 'Procurement',
        prefix: '/procurement',
        roles: ['procurement', 'procurement_manager', 'purchasing'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>',
        children: [
            { to: '/procurement/vendors', label: 'Vendors' },
            { to: '/procurement/purchase-orders', label: 'Purchase Orders' },
        ],
    },
    {
        label: 'Manufacturing',
        prefix: '/manufacturing',
        roles: ['manufacturing', 'production', 'production_manager'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        children: [
            { to: '/manufacturing/boms', label: 'Bill of Materials' },
            { to: '/manufacturing/work-orders', label: 'Work Orders' },
        ],
    },
    {
        label: 'Projects',
        prefix: '/projects',
        roles: ['projects', 'project_manager', 'team_lead'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>',
        children: [
            { to: '/projects/projects', label: 'Projects' },
            { to: '/projects/tasks', label: 'Tasks' },
            { to: '/projects/time-entries', label: 'Time Entries' },
        ],
    },
    {
        label: 'Quality',
        prefix: '/quality',
        roles: ['quality', 'quality_manager', 'qc_inspector'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>',
        children: [
            { to: '/quality/checks', label: 'Quality Checks' },
            { to: '/quality/non-conformances', label: 'Non-Conformances' },
        ],
    },
    {
        label: 'Assets',
        prefix: '/assets',
        roles: ['assets', 'asset_manager', 'facilities'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
        children: [
            { to: '/assets/assets', label: 'Assets' },
            { to: '/assets/maintenance', label: 'Maintenance' },
        ],
    },
    {
        label: 'Field Service',
        to: '/field-service/tickets',
        roles: ['field_service', 'technician', 'dispatcher'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>',
    },
    {
        label: 'LMS',
        prefix: '/lms',
        roles: ['lms', 'instructor', 'learner', 'training_manager'],
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>',
        children: [
            { to: '/lms/courses', label: 'Courses' },
            { to: '/lms/enrollments', label: 'Enrollments' },
        ],
    },
    {
        label: 'Network',
        prefix: '/network',
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        children: [
            { to: '/network/feed', label: 'Feed' },
            { to: '/network/discovery', label: 'Discovery' },
            { to: '/network/profile', label: 'My Profile' },
            { to: '/network/connections', label: 'Connections' },
            { to: '/network/following', label: 'Following' },
        ],
    },
    {
        label: 'Expenses',
        to: '/expenses',
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>',
    },
    {
        label: 'Documents',
        to: '/documents',
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>',
    },
    {
        label: 'Contracts',
        to: '/contracts',
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>',
    },
    {
        label: 'Reports',
        to: '/reports',
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
    },
    {
        label: 'Settings',
        to: '/settings',
        icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
    },
];

// Filter nav based on user roles — show everything when user has no roles assigned
const visibleNav = computed(() => {
    const userRoleNames = authStore.userRoles();
    if (userRoleNames.length === 0) return navigationGroups; // no roles = full access
    const isAdmin = userRoleNames.includes('admin') || userRoleNames.includes('super_admin');
    if (isAdmin) return navigationGroups;
    return navigationGroups.filter(item =>
        !item.roles || item.roles.some(r => userRoleNames.includes(r))
    );
});

// ── Expandable groups state ──────────────────────────────────────────────────
const openGroups = ref<string[]>([]);

function toggleGroup(label: string) {
    const idx = openGroups.value.indexOf(label);
    if (idx >= 0) {
        openGroups.value.splice(idx, 1);
    } else {
        openGroups.value.push(label);
    }
}

function isGroupOpen(label: string): boolean {
    return openGroups.value.includes(label);
}

function isGroupActive(prefix: string): boolean {
    return route.path.startsWith(prefix);
}

// Auto-expand the group matching the current route
watch(() => route.path, (path) => {
    for (const item of navigationGroups) {
        if (item.prefix && path.startsWith(item.prefix) && !openGroups.value.includes(item.label)) {
            openGroups.value.push(item.label);
        }
    }
}, { immediate: true });

// ── Notifications ────────────────────────────────────────────────────────────
interface Notification {
    id: string;
    type: string;
    data: { title?: string; message?: string } | null;
    read_at: string | null;
    created_at: string;
}

const notificationsOpen = ref(false);
const notificationsLoading = ref(false);
const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const notifRef = ref<HTMLElement | null>(null);

function toggleNotifications() {
    notificationsOpen.value = !notificationsOpen.value;
    if (notificationsOpen.value) {
        fetchNotifications();
    }
}

async function fetchNotifications() {
    notificationsLoading.value = true;
    try {
        const [listRes, countRes] = await Promise.all([
            api.get('/notifications'),
            api.get('/notifications/unread-count'),
        ]);
        notifications.value = listRes.data?.data ?? [];
        unreadCount.value = countRes.data?.data?.count ?? 0;
    } catch {
        // silently ignore
    } finally {
        notificationsLoading.value = false;
    }
}

async function fetchUnreadCount() {
    try {
        const res = await api.get('/notifications/unread-count');
        unreadCount.value = res.data?.data?.count ?? 0;
    } catch {
        // silently ignore
    }
}

async function markRead(n: Notification) {
    if (n.read_at) return;
    try {
        await api.put(`/notifications/${n.id}/read`);
        n.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch {
        // silently ignore
    }
}

async function markAllRead() {
    try {
        await api.put('/notifications/read-all');
        notifications.value.forEach(n => { n.read_at = new Date().toISOString(); });
        unreadCount.value = 0;
    } catch {
        // silently ignore
    }
}

function formatTimeAgo(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    return `${Math.floor(hrs / 24)}d ago`;
}

// Close notification dropdown on outside click
function handleOutsideClick(e: MouseEvent) {
    if (notifRef.value && !notifRef.value.contains(e.target as Node)) {
        notificationsOpen.value = false;
    }
}

// Poll unread count every 60s
let pollInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    fetchUnreadCount();
    pollInterval = setInterval(fetchUnreadCount, 60000);
    document.addEventListener('mousedown', handleOutsideClick);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
    document.removeEventListener('mousedown', handleOutsideClick);
});
</script>
