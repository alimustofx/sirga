<script setup lang="ts">
import AppBadge from '@/components/app/AppBadge.vue';
import type { PeriodStatus } from '@/types';
import { computed } from 'vue';

const props = defineProps<{ status: PeriodStatus; label?: string }>();
const tone = computed(() => ({ draft: 'neutral', review: 'info', published: 'success', closed: 'neutral' } as const)[props.status]);
const text = computed(() => props.label ?? { draft: 'Draft', review: 'Review', published: 'Published', closed: 'Closed' }[props.status]);
</script>

<template><AppBadge :tone="tone">{{ text }}</AppBadge></template>
