<script setup lang="ts">
import { rupiah } from '@/lib/format';
import { computed, onMounted, ref } from 'vue';

export type Part = { label: string; value: number; color: string };
const props = defineProps<{ parts: Part[] }>();
const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));
const total = computed(() => props.parts.reduce((a, p) => a + Math.max(0, p.value), 0) || 1);
const pct = (v: number) => Math.round((Math.max(0, v) / total.value) * 1000) / 10;
const pctText = (v: number) => `${pct(v).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 1 })}%`;
</script>

<template>
    <div class="min-w-0">
        <div class="flex h-3 w-full gap-0.5 overflow-hidden rounded-full bg-neutral-100" role="img" :aria-label="parts.map((p) => `${p.label} ${pctText(p.value)}`).join(', ')">
            <div v-for="(p, i) in parts" :key="p.label" class="h-full transition-[width] duration-500 ease-out" :style="{ width: ready ? `${pct(p.value)}%` : '0%', background: p.color, transitionDelay: `${i * 80}ms` }" />
        </div>
        <ul class="mt-4 divide-y divide-neutral-100">
            <li v-for="p in parts" :key="p.label" class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                <span class="size-2.5 shrink-0 rounded-full" :style="{ background: p.color }" aria-hidden="true" />
                <span class="min-w-0 flex-1 text-sm text-neutral-800">{{ p.label }}</span>
                <span class="flex shrink-0 flex-col items-end text-right sm:flex-row sm:items-baseline sm:gap-2"><span class="num text-sm font-semibold text-neutral-950">{{ rupiah(p.value) }}</span><span class="num w-12 text-xs text-neutral-600 sm:text-right">{{ pctText(p.value) }}</span></span>
            </li>
        </ul>
    </div>
</template>
