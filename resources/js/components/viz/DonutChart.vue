<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

export type Segment = { label: string; value: number; color: string };
const props = withDefaults(defineProps<{ segments: Segment[]; size?: number; thickness?: number }>(), { size: 150, thickness: 16 });
const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));

const r = computed(() => (props.size - props.thickness) / 2);
const c = computed(() => 2 * Math.PI * r.value);
const total = computed(() => props.segments.reduce((a, s) => a + Math.max(0, s.value), 0));
const arcs = computed(() => {
    let acc = 0;

    return props.segments.map((s) => {
        const frac = total.value ? Math.max(0, s.value) / total.value : 0;
        const a = { ...s, len: frac * c.value, offset: acc * c.value, pct: Math.round(frac * 100) };
        acc += frac;

        return a;
    });
});
</script>

<template>
    <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`" class="-rotate-90" role="img" :aria-label="segments.map((s) => s.label).join(', ')">
            <circle :cx="size / 2" :cy="size / 2" :r="r" fill="none" stroke="#f1f5f9" :stroke-width="thickness" />
            <circle v-for="(a, i) in arcs" :key="a.label" :cx="size / 2" :cy="size / 2" :r="r" fill="none" :stroke="a.color" :stroke-width="thickness" stroke-linecap="butt"
                :stroke-dasharray="`${ready ? Math.max(0, a.len - (arcs.length > 1 ? 2 : 0)) : 0} ${c}`" :stroke-dashoffset="-a.offset" :style="{ transition: `stroke-dasharray 0.9s cubic-bezier(.2,.7,.2,1) ${i * 120}ms` }" />
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center text-center"><slot /></div>
    </div>
</template>
