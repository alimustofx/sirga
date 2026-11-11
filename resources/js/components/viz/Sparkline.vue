<script setup lang="ts">
import { computed, useId } from 'vue';

const props = withDefaults(defineProps<{ values: number[]; width?: number; height?: number; color?: string; fill?: boolean }>(), { width: 120, height: 36, color: '#287b8c', fill: true });
const id = useId();

const pts = computed(() => {
    const v = props.values.length > 1 ? props.values : [0, ...props.values, ...props.values];
    const min = Math.min(...v);
    const max = Math.max(...v);
    const span = max - min || 1;
    const pad = 3;

    return v.map((n, i) => ({ x: pad + (i / (v.length - 1)) * (props.width - pad * 2), y: pad + (1 - (n - min) / span) * (props.height - pad * 2) }));
});
const line = computed(() => pts.value.reduce((d, p, i, a) => {
    if (i === 0) return `M${p.x},${p.y}`;
    const prev = a[i - 1]!;
    const cx = (prev.x + p.x) / 2;

    return `${d} C${cx},${prev.y} ${cx},${p.y} ${p.x},${p.y}`;
}, ''));
const area = computed(() => `${line.value} L${pts.value[pts.value.length - 1]!.x},${props.height} L${pts.value[0]!.x},${props.height} Z`);
const last = computed(() => pts.value[pts.value.length - 1]!);
</script>

<template>
    <svg :width="width" :height="height" :viewBox="`0 0 ${width} ${height}`" role="img" aria-label="Grafik tren" class="overflow-visible">
        <defs><linearGradient :id="id" x1="0" y1="0" x2="0" y2="1"><stop offset="0" :stop-color="color" stop-opacity="0.28" /><stop offset="1" :stop-color="color" stop-opacity="0" /></linearGradient></defs>
        <path v-if="fill" :d="area" :fill="`url(#${id})`" />
        <path :d="line" fill="none" :stroke="color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        <circle :cx="last.x" :cy="last.y" r="3.5" :fill="color" /><circle :cx="last.x" :cy="last.y" r="7" :fill="color" opacity="0.18" />
    </svg>
</template>
