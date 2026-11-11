<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>();
</script>

<template>
    <div class="mb-6 flex animate-fade-up flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="gold-rule mb-2.5 block" aria-hidden="true" />
            <h1 class="page-title">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1.5 max-w-2xl text-sm text-neutral-600">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2"><slot /></div>
    </div>
</template>
