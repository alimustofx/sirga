<script setup lang="ts">
const model = defineModel<boolean>({ default: false });
defineProps<{ label: string; hint?: string }>();
</script>

<template>
    <label class="flex min-h-11 cursor-pointer items-start gap-3 py-1.5">
        <input v-model="model" type="checkbox" class="mt-0.5 size-5 shrink-0 rounded border-neutral-400 accent-primary-700" />
        <span>
            <span class="block text-sm font-medium text-neutral-950">{{ label }}</span>
            <span v-if="hint" class="block text-[13px] text-neutral-600">{{ hint }}</span>
        </span>
    </label>
</template>
