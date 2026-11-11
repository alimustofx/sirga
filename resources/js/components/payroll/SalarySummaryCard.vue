<script setup lang="ts">
import { rupiah } from '@/lib/format';

defineProps<{ label: string; value: string; hint?: string }>();
</script>

<template>
    <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-card">
        <p class="text-[13px] font-medium text-neutral-600">{{ label }}</p>
        <p class="num mt-1 text-xl font-bold text-primary-900">{{ rupiah(value) }}</p>
        <p v-if="hint" class="mt-0.5 text-xs text-neutral-600">{{ hint }}</p>
    </div>
</template>
