<template>
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-2">
            <li class="inline-flex items-center">
                <router-link to="/dashboard" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    Home
                </router-link>
            </li>
            <li v-for="crumb in breadcrumbs" :key="crumb.path" class="inline-flex items-center">
                <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" />
                </svg>
                <span
                    v-if="crumb.isLast"
                    class="text-sm text-gray-700 dark:text-gray-200 font-medium"
                >
                    {{ crumb.label }}
                </span>
                <router-link
                    v-else
                    :to="crumb.path"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                >
                    {{ crumb.label }}
                </router-link>
            </li>
        </ol>
    </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

interface Crumb {
    path: string;
    label: string;
    isLast: boolean;
}

const breadcrumbs = computed<Crumb[]>(() => {
    const parts = route.path.split('/').filter(Boolean);
    const crumbs: Crumb[] = [];
    let currentPath = '';

    for (let i = 0; i < parts.length; i++) {
        currentPath += '/' + parts[i];
        const label = parts[i].replace(/-/g, ' ');
        const isLast = i === parts.length - 1;
        crumbs.push({
            path: currentPath,
            label: label.charAt(0).toUpperCase() + label.slice(1),
            isLast,
        });
    }

    return crumbs;
});
</script>