<template>
    <div id="app-root">
        <router-view />
        <NotificationContainer />
    </div>
</template>

<script setup lang="ts">
import NotificationContainer from '@/components/NotificationContainer.vue';
</script>