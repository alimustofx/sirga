<script setup lang="ts">
defineProps<{ class?: string }>();
</script>

<template>
    <div :class="['animate-pulse rounded-lg bg-neutral-100', $props.class ?? 'h-5 w-full']" aria-hidden="true" />
</template>
