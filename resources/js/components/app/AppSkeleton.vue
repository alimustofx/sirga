<script setup lang="ts">
defineProps<{ class?: string }>();
</script>

<template><div :class="['skeleton rounded-md', $props.class ?? 'h-4 w-full']" aria-hidden="true" /></template>
