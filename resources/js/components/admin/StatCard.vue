<script setup lang="ts">
defineProps<{ label: string; value: string | number; hint?: string; tone?: 'default' | 'warning' | 'danger' }>();
</script>

<template>
    <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-card">
        <p class="text-[13px] font-medium text-neutral-600">{{ label }}</p>
        <p :class="['num mt-1 text-2xl font-bold', tone === 'warning' ? 'text-warning-fg' : tone === 'danger' ? 'text-danger-fg' : 'text-primary-900']">{{ value }}</p>
        <p v-if="hint" class="mt-0.5 text-xs text-neutral-600">{{ hint }}</p>
    </div>
</template>
