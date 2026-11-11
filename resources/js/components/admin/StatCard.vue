<script setup lang="ts">
import AppIconTile from '@/components/app/AppIconTile.vue';
import Sparkline from '@/components/viz/Sparkline.vue';
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

defineProps<{ label: string; value: string | number; hint?: string; tone?: 'default' | 'warning' | 'danger'; icon?: Component; trend?: number[]; to?: string }>();
const toneIcon = { default: 'teal', warning: 'warning', danger: 'danger' } as const;
</script>

<template>
    <component :is="to ? Link : 'div'" :href="to" :class="['group relative block overflow-hidden rounded-2xl border border-neutral-200 bg-white p-4 shadow-card', to ? 'card-lift' : '']">
        <div class="flex items-start justify-between gap-3">
            <p class="text-[13px] font-medium text-neutral-600">{{ label }}</p>
            <AppIconTile v-if="icon" :icon="icon" :tone="toneIcon[tone ?? 'default']" size="sm" />
        </div>
        <p :class="['num mt-1.5 text-[26px] leading-8 font-bold tracking-tight', tone === 'warning' ? 'text-warning-fg' : tone === 'danger' ? 'text-danger-fg' : 'text-primary-900']">{{ value }}</p>
        <div class="mt-1 flex items-end justify-between gap-2">
            <p v-if="hint" class="text-xs text-neutral-600">{{ hint }}</p>
            <Sparkline v-if="trend && trend.length > 1" :values="trend" :width="72" :height="24" class="ml-auto shrink-0" />
        </div>
    </component>
</template>
