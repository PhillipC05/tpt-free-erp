<template>
    <div class="org-node">
        <div class="org-card" :class="{ 'ring-2 ring-blue-400': selected }" @click="$emit('select', node)">
            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-xs mx-auto mb-1">
                {{ node.first_name?.[0] }}{{ node.last_name?.[0] }}
            </div>
            <div class="text-xs font-medium text-gray-900 dark:text-gray-100 text-center truncate max-w-[120px]">{{ node.first_name }} {{ node.last_name }}</div>
            <div class="text-[10px] text-gray-500 text-center truncate max-w-[120px]">{{ node.position ?? '—' }}</div>
        </div>
        <div v-if="node.children?.length" class="org-children">
            <div v-for="child in node.children" :key="child.id" class="org-child-wrapper">
                <OrgNode :node="child" :depth="depth + 1" @select="$emit('select', $event)" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    node: any;
    depth: number;
    selected?: boolean;
}>();

defineEmits<{
    select: [node: any];
}>();
</script>

<style scoped>
.org-node {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.org-card {
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: box-shadow 0.15s;
    min-width: 120px;
}
.dark .org-card {
    background: #1f2937;
    border-color: #374151;
}
.org-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.org-children {
    display: flex;
    gap: 12px;
    padding-top: 20px;
    position: relative;
}
.org-children::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    width: 1px;
    height: 20px;
    background: #d1d5db;
}
.org-child-wrapper {
    position: relative;
}
.org-child-wrapper::before {
    content: '';
    position: absolute;
    top: -1px;
    left: 50%;
    width: 1px;
    height: 1px;
    background: #d1d5db;
}
</style>
