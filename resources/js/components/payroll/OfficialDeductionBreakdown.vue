<script setup lang="ts">
import type { Line } from '@/types';
import BreakdownList from './BreakdownList.vue';

defineProps<{ lines: Line[]; total: string }>();
</script>

<template><BreakdownList tone="navy" title="Potongan Resmi" :lines="lines" total-label="Total Potongan Resmi" :total="total" empty-text="Tidak ada potongan resmi." /></template>
