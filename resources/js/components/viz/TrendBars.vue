<script setup lang="ts">
import { rupiah, rupiahCompact } from '@/lib/format';
import { computed, onMounted, ref } from 'vue';

export type Bar = { label: string; value: number; full?: string };
const props = withDefaults(defineProps<{ items: Bar[]; height?: number; ariaLabel?: string }>(), { height: 176 });
const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));

const SCALE = 86; // % tinggi area untuk nilai maksimum (sisakan ruang label)
const max = computed(() => Math.max(1, ...props.items.map((i) => i.value)));
const pct = (v: number) => (v / max.value) * SCALE;
const ticks = computed(() => [0, 0.5, 1].map((t) => ({ pos: t * SCALE, label: t === 0 ? 'Rp0' : rupiahCompact(max.value * t) })));
const last = computed(() => props.items.length - 1);
</script>

<template>
    <figure role="img" :aria-label="ariaLabel ?? 'Grafik batang per periode'">
        <div class="flex gap-2">
            <div class="relative w-14 shrink-0" :style="{ height: `${height}px` }" aria-hidden="true">
                <span v-for="t in ticks" :key="t.pos" class="num absolute right-0 -translate-y-1/2 text-[11px] text-neutral-600" :style="{ bottom: `${t.pos}%` }">{{ t.label }}</span>
            </div>
            <div class="relative flex flex-1 gap-1.5 sm:gap-3" :style="{ height: `${height}px` }">
                <span v-for="t in ticks" :key="`g${t.pos}`" class="pointer-events-none absolute inset-x-0 border-t border-dashed border-neutral-200" :style="{ bottom: `${t.pos}%` }" aria-hidden="true" />
                <div v-for="(b, i) in items" :key="b.label + i" class="group relative flex-1" :title="`${b.full ?? b.label}: ${rupiah(b.value)}`">
                    <div :class="['absolute inset-x-1 bottom-0 rounded-t-md transition-[height] duration-500 ease-out sm:inset-x-2', i === last ? 'bg-primary-600' : 'bg-primary-700/30 group-hover:bg-primary-700/50']" :style="{ height: ready ? `${Math.max(1, pct(b.value))}%` : '1%' }" />
                    <span :class="['num absolute inset-x-0 text-center text-[11px] font-semibold', i === last ? 'text-primary-700' : 'text-neutral-600']" :style="{ bottom: `calc(${Math.max(1, pct(b.value))}% + 4px)` }">{{ rupiahCompact(b.value) }}</span>
                </div>
            </div>
        </div>
        <figcaption class="mt-2 flex gap-2"><span class="w-14 shrink-0" /><span class="flex flex-1 gap-1.5 sm:gap-3"><span v-for="(b, i) in items" :key="b.label + i" :class="['flex-1 text-center text-[11px] font-medium', i === last ? 'text-primary-700' : 'text-neutral-600']">{{ b.label }}</span></span></figcaption>
    </figure>
</template>
