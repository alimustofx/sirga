<script setup lang="ts">
withDefaults(defineProps<{ tone?: 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'primary' }>(), { tone: 'neutral' });
const tones = {
    success: 'bg-success-bg text-success-fg',
    warning: 'bg-warning-bg text-warning-fg',
    danger: 'bg-danger-bg text-danger-fg',
    info: 'bg-info-bg text-info-fg',
    neutral: 'bg-neutral-100 text-neutral-800',
    primary: 'bg-[#e6f2f4] text-primary-700',
};
</script>

<template>
    <span :class="['inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold whitespace-nowrap', tones[tone]]">
        <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
        <slot />
    </span>
</template>
