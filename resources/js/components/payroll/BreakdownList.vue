<script setup lang="ts">
import AppCard from '@/components/app/AppCard.vue';
import { rupiah } from '@/lib/format';
import type { Line } from '@/types';

withDefaults(defineProps<{ title: string; lines: Line[]; totalLabel: string; total: string; emptyText?: string; tone?: 'teal' | 'navy' | 'gold' }>(), { tone: 'teal' });
</script>

<template>
    <AppCard :title="title" flush :accent="tone">
        <ul v-if="lines.length" class="divide-y divide-neutral-100">
            <li v-for="(l, i) in lines" :key="i" class="flex items-baseline justify-between gap-4 px-4 py-2.5 sm:px-5">
                <span class="text-sm text-neutral-800">{{ l.name }}</span>
                <span class="num text-sm font-medium text-neutral-950">{{ rupiah(l.amount) }}</span>
            </li>
        </ul>
        <p v-else class="px-4 py-5 text-sm text-neutral-600 sm:px-5">{{ emptyText ?? 'Tidak ada data.' }}</p>
        <div class="flex items-baseline justify-between gap-4 border-t border-neutral-200 bg-neutral-50 px-4 py-3 sm:px-5">
            <span class="text-sm font-semibold text-primary-900">{{ totalLabel }}</span>
            <span class="num text-base font-bold text-primary-900">{{ rupiah(total) }}</span>
        </div>
    </AppCard>
</template>
