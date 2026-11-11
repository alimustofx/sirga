<script setup lang="ts">
import { rupiah } from '@/lib/format';
import { computed, onMounted, ref } from 'vue';

export type Bar = { label: string; value: number; sub?: string; full?: string };
const props = withDefaults(defineProps<{ items: Bar[]; height?: number; ariaLabel?: string }>(), { height: 168 });
const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));
const max = computed(() => Math.max(1, ...props.items.map((i) => i.value)));
const lastIdx = computed(() => props.items.length - 1);
</script>

<template>
    <div role="img" :aria-label="ariaLabel ?? 'Grafik batang tren per periode'">
        <div class="relative flex items-end gap-2 sm:gap-3" :style="{ height: `${height}px` }">
            <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true"><span v-for="n in 4" :key="n" class="border-t border-dashed border-neutral-200" /></div>
            <div v-for="(b, i) in items" :key="b.label + i" class="group relative flex h-full flex-1 flex-col justify-end" :title="`${b.full ?? b.label}: ${rupiah(b.value)}`">
                <span class="num pointer-events-none absolute -top-1 left-1/2 z-10 -translate-x-1/2 -translate-y-full rounded-md bg-primary-900 px-2 py-1 text-[11px] font-semibold whitespace-nowrap text-white opacity-0 shadow-modal transition-opacity group-hover:opacity-100">{{ rupiah(b.value) }}</span>
                <div :class="['w-full rounded-t-lg transition-all duration-700 ease-out', i === lastIdx ? 'bg-primary-600' : 'bg-primary-700/25 group-hover:bg-primary-700/45']" :style="{ height: ready ? `${Math.max(3, (b.value / max) * 100)}%` : '3%', transitionDelay: `${i * 70}ms` }" />
            </div>
        </div>
        <div class="mt-2 flex gap-2 sm:gap-3"><span v-for="(b, i) in items" :key="b.label + i" :class="['flex-1 text-center text-[11px] font-medium', i === lastIdx ? 'text-primary-700' : 'text-neutral-600']">{{ b.label }}</span></div>
    </div>
</template>
