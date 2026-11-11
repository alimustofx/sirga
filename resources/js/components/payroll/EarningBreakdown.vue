<script setup lang="ts">
import type { Line } from '@/types';
import BreakdownList from './BreakdownList.vue';

defineProps<{ lines: Line[]; total: string }>();
</script>

<template><BreakdownList title="Penghasilan" :lines="lines" total-label="Gaji Kotor" :total="total" empty-text="Tidak ada komponen penghasilan." /></template>
