<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

export type StatItem = { label: string; value: string | number; hint?: string; tone?: 'default' | 'warning' | 'danger'; to?: string };
defineProps<{ items: StatItem[] }>();
const tone = { default: 'text-primary-900', warning: 'text-warning-fg', danger: 'text-danger-fg' };
</script>

<template>
    <div :class="['grid divide-neutral-200 overflow-hidden rounded-xl border border-neutral-200 bg-white max-lg:divide-y lg:divide-x', items.length === 3 ? 'lg:grid-cols-3' : items.length === 5 ? 'lg:grid-cols-5' : 'lg:grid-cols-4', 'grid-cols-1 sm:grid-cols-2']">
        <component :is="it.to ? Link : 'div'" v-for="it in items" :key="it.label" :href="it.to" :class="['block px-5 py-4', it.to ? 'transition-colors hover:bg-neutral-50' : '']">
            <p class="eyebrow">{{ it.label }}</p>
            <p :class="['num mt-1.5 text-2xl leading-8 font-bold tracking-tight', tone[it.tone ?? 'default']]">{{ it.value }}</p>
            <p v-if="it.hint" class="mt-0.5 text-[13px] text-neutral-600">{{ it.hint }}</p>
        </component>
    </div>
</template>
