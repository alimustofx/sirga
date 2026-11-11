<script setup lang="ts">
import type { Line } from '@/types';
import BreakdownList from './BreakdownList.vue';

defineProps<{ lines: Line[]; total: string }>();
</script>

<template><BreakdownList tone="gold" title="Potongan Sekolah" :lines="lines" total-label="Total Potongan Sekolah" :total="total" empty-text="Tidak ada potongan sekolah pada periode ini." /></template>
