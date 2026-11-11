<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div class="flex animate-fade-up flex-col items-center px-6 py-14 text-center">
        <svg class="mb-4 h-24 w-32" viewBox="0 0 128 96" fill="none" aria-hidden="true">
            <ellipse cx="64" cy="84" rx="40" ry="7" fill="#e2e8f0" opacity=".7" />
            <rect x="26" y="26" width="76" height="50" rx="10" fill="#fff" stroke="#e2e8f0" stroke-width="2" />
            <rect x="26" y="26" width="76" height="14" rx="10" fill="#142b45" /><rect x="26" y="34" width="76" height="6" fill="#142b45" />
            <rect x="36" y="50" width="30" height="5" rx="2.5" fill="#cbd5e1" /><rect x="36" y="61" width="46" height="5" rx="2.5" fill="#e2e8f0" />
            <circle cx="94" cy="62" r="14" fill="#f8efd6" stroke="#c9a24b" stroke-width="2" /><path d="M94 55v14M89 60h8a3 3 0 0 1 0 6h-8" stroke="#8a6a1f" stroke-width="2" stroke-linecap="round" />
            <circle cx="108" cy="20" r="3" fill="#287b8c" opacity=".6" /><circle cx="20" cy="38" r="2" fill="#c9a24b" opacity=".7" />
        </svg>
        <p class="text-base font-semibold text-primary-900">{{ title }}</p>
        <p v-if="description" class="mt-1 max-w-md text-sm text-neutral-600">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5"><slot /></div>
    </div>
</template>
