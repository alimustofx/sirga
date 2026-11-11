<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

const props = withDefaults(defineProps<{ value: number; size?: number; thickness?: number; color?: string }>(), { size: 84, thickness: 9, color: '#287b8c' });
const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));
const r = computed(() => (props.size - props.thickness) / 2);
const c = computed(() => 2 * Math.PI * r.value);
const pct = computed(() => Math.max(0, Math.min(100, props.value)));
</script>

<template>
    <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }" role="img" :aria-label="`${Math.round(pct)} persen`">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`" class="-rotate-90">
            <circle :cx="size / 2" :cy="size / 2" :r="r" fill="none" stroke="#e2e8f0" :stroke-width="thickness" />
            <circle :cx="size / 2" :cy="size / 2" :r="r" fill="none" :stroke="color" :stroke-width="thickness" stroke-linecap="round" :stroke-dasharray="c" :stroke-dashoffset="ready ? c * (1 - pct / 100) : c" style="transition: stroke-dashoffset 1s cubic-bezier(0.2, 0.7, 0.2, 1)" />
        </svg>
        <span class="num absolute text-sm font-bold text-primary-900"><slot>{{ Math.round(pct) }}%</slot></span>
    </div>
</template>
