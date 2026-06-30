<template>
  <img
    ref="imgRef"
    :src="isVisible ? src : placeholder"
    :alt="alt"
    :class="['transition-opacity duration-300', { 'opacity-0': !loaded && !placeholder }]"
    :loading="native ? 'lazy' : 'eager'"
    @load="onLoad"
    @error="onError"
  />
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = withDefaults(defineProps<{
  src: string;
  alt?: string;
  placeholder?: string;
  rootMargin?: string;
  threshold?: number;
  native?: boolean;
}>(), {
  alt: '',
  placeholder: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E',
  rootMargin: '200px 0px',
  threshold: 0,
  native: false,
});

const imgRef = ref<HTMLImageElement>();
const isVisible = ref(false);
const loaded = ref(false);

let observer: IntersectionObserver | null = null;

function onLoad() {
  loaded.value = true;
}

function onError() {
  loaded.value = false;
}

onMounted(() => {
  if (props.native || !imgRef.value) return;

  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting) {
        isVisible.value = true;
        observer?.disconnect();
      }
    },
    { rootMargin: props.rootMargin, threshold: props.threshold },
  );

  observer.observe(imgRef.value);
});

onUnmounted(() => {
  observer?.disconnect();
});

watch(() => props.src, () => {
  loaded.value = false;
});
</script>
