<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div class="flex flex-col items-center px-6 py-12 text-center">
        <div class="mb-3 flex size-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round" /></svg>
        </div>
        <p class="text-base font-semibold text-primary-900">{{ title }}</p>
        <p v-if="description" class="mt-1 max-w-md text-sm text-neutral-600">{{ description }}</p>
        <div v-if="$slots.default" class="mt-4"><slot /></div>
    </div>
</template>
