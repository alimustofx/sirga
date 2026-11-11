<script setup lang="ts">
import AppIconTile from '@/components/app/AppIconTile.vue';
import { rupiah } from '@/lib/format';
import type { Component } from 'vue';

defineProps<{ label: string; value: string; hint?: string; icon?: Component; tone?: 'teal' | 'navy' | 'gold' }>();
</script>

<template>
    <div class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white p-4 shadow-card">
        <AppIconTile v-if="icon" :icon="icon" :tone="tone ?? 'teal'" />
        <div class="min-w-0">
            <p class="text-[13px] font-medium text-neutral-600">{{ label }}</p>
            <p class="num text-xl font-bold tracking-tight text-primary-900">{{ rupiah(value) }}</p>
            <p v-if="hint" class="text-xs text-neutral-600">{{ hint }}</p>
        </div>
    </div>
</template>
