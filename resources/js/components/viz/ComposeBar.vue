<script setup lang="ts">
import { rupiah } from '@/lib/format';
import { computed, onMounted, ref } from 'vue';

export type Part = { label: string; value: number; color: string; note?: string };
const props = defineProps<{ parts: Part[] }>();
const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));
const total = computed(() => props.parts.reduce((a, p) => a + Math.max(0, p.value), 0) || 1);
const pct = (v: number) => Math.round((Math.max(0, v) / total.value) * 1000) / 10;
</script>

<template>
    <div>
        <div class="flex h-3 w-full gap-0.5 overflow-hidden rounded-full bg-neutral-100" role="img" :aria-label="parts.map((p) => `${p.label} ${pct(p.value)} persen`).join(', ')">
            <div v-for="(p, i) in parts" :key="p.label" class="h-full transition-all duration-700 ease-out first:rounded-l-full last:rounded-r-full" :style="{ width: ready ? `${pct(p.value)}%` : '0%', background: p.color, transitionDelay: `${i * 120}ms` }" />
        </div>
        <ul class="mt-3 grid gap-2 sm:grid-cols-3">
            <li v-for="p in parts" :key="p.label" class="flex items-start gap-2">
                <span class="mt-1 size-2.5 shrink-0 rounded-full" :style="{ background: p.color }" aria-hidden="true" />
                <span class="min-w-0"><span class="block text-[13px] text-neutral-600">{{ p.label }}</span><span class="num block text-sm font-semibold text-neutral-950">{{ rupiah(p.value) }} <span class="font-normal text-neutral-600">· {{ pct(p.value) }}%</span></span></span>
            </li>
        </ul>
    </div>
</template>
