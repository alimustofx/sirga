<script setup lang="ts">
import { rupiah } from '@/lib/format';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ value: number | string; duration?: number; format?: 'rupiah' | 'number' }>(), { duration: 900, format: 'rupiah' });

const shown = ref(0);
const done = ref(false);
let raf = 0;

function run(to: number): void {
    cancelAnimationFrame(raf);
    done.value = false;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || props.duration <= 0) {
        shown.value = to;
        done.value = true;
        return;
    }
    const from = shown.value;
    const t0 = performance.now();
    const step = (t: number) => {
        const p = Math.min(1, (t - t0) / props.duration);
        shown.value = from + (to - from) * (1 - Math.pow(1 - p, 3)); // easeOutCubic
        if (p < 1) {
            raf = requestAnimationFrame(step);
        } else {
            shown.value = to;
            done.value = true;
        }
    };
    raf = requestAnimationFrame(step);
}

onMounted(() => run(Number(props.value)));
onBeforeUnmount(() => cancelAnimationFrame(raf));
watch(() => props.value, (v) => run(Number(v)));

const fmt = (n: number | string) => (props.format === 'rupiah' ? rupiah(n) : new Intl.NumberFormat('id-ID').format(Number(n)));
const text = computed(() => (done.value ? fmt(props.value) : fmt(Math.round(shown.value))));
</script>

<template><span class="num" :aria-label="fmt(value)">{{ text }}</span></template>
