<script setup lang="ts">
import AppBadge from '@/components/app/AppBadge.vue';
import type { PaymentStatus } from '@/types';
import { computed } from 'vue';

const props = defineProps<{ status: PaymentStatus; label?: string }>();
const tone = computed(() => ({ belum_dibayar: 'warning', menunggu_verifikasi: 'info', lunas: 'success', ditolak: 'danger' } as const)[props.status]);
const text = computed(() => props.label ?? { belum_dibayar: 'Belum Dibayar', menunggu_verifikasi: 'Menunggu Verifikasi', lunas: 'Lunas', ditolak: 'Ditolak' }[props.status]);
</script>

<template><AppBadge :tone="tone">{{ text }}</AppBadge></template>
