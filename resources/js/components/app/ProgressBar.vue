<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

const props = withDefaults(defineProps<{ value: number; tone?: 'teal' | 'gold' | 'navy' }>(), { tone: 'teal' });
const ready = ref(false);
onMounted(() => requestAnimationFrame(() => { ready.value = true; }));
const pct = computed(() => Math.max(0, Math.min(100, props.value)));
const colors = { teal: 'bg-primary-600', gold: 'bg-gold-500', navy: 'bg-primary-900' };
</script>

<template>
    <div class="h-2 w-full overflow-hidden rounded-full bg-neutral-100" role="progressbar" :aria-valuenow="Math.round(pct)" aria-valuemin="0" aria-valuemax="100">
        <div :class="['h-full rounded-full transition-[width] duration-700 ease-out', colors[tone]]" :style="{ width: ready ? `${pct}%` : '0%' }" />
    </div>
</template>
