<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

defineProps<{ label: string; value: string | number; hint?: string; tone?: 'default' | 'warning' | 'danger'; icon?: Component; trend?: number[]; to?: string }>();
</script>

<template>
    <component :is="to ? Link : 'div'" :href="to" :class="['block min-w-0 rounded-xl border border-neutral-200 bg-white px-4 py-3.5', to ? 'transition-colors hover:border-neutral-400' : '']">
        <p class="eyebrow">{{ label }}</p>
        <p :class="['mt-1 text-base leading-7 font-bold tracking-tight [font-variant-numeric:tabular-nums] sm:text-lg 2xl:text-[22px]', tone === 'warning' ? 'text-warning-fg' : tone === 'danger' ? 'text-danger-fg' : 'text-primary-900']">{{ value }}</p>
        <p v-if="hint" class="mt-0.5 text-xs text-neutral-600">{{ hint }}</p>
    </component>
</template>
