<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div class="flex flex-col items-center px-6 py-12 text-center">
        <span class="mb-3 flex size-11 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-400">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M5 6h14M5 12h14M5 18h8" /></svg>
        </span>
        <p class="text-[15px] font-semibold text-primary-900">{{ title }}</p>
        <p v-if="description" class="mt-1 max-w-md text-sm text-neutral-600">{{ description }}</p>
        <div v-if="$slots.default" class="mt-4"><slot /></div>
    </div>
</template>
