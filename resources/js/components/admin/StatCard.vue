<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

defineProps<{ label: string; value: string | number; hint?: string; tone?: 'default' | 'warning' | 'danger'; icon?: Component; trend?: number[]; to?: string }>();
</script>

<template>
    <component :is="to ? Link : 'div'" :href="to" :class="['block rounded-xl border border-neutral-200 bg-white px-4 py-3.5', to ? 'transition-colors hover:border-neutral-400' : '']">
        <p class="eyebrow">{{ label }}</p>
        <p :class="['num mt-1 text-[22px] leading-7 font-bold tracking-tight', tone === 'warning' ? 'text-warning-fg' : tone === 'danger' ? 'text-danger-fg' : 'text-primary-900']">{{ value }}</p>
        <p v-if="hint" class="mt-0.5 text-xs text-neutral-600">{{ hint }}</p>
    </component>
</template>
