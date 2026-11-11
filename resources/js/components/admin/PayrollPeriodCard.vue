<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import PayrollStatusBadge from '@/components/payroll/PayrollStatusBadge.vue';
import { formatDate } from '@/lib/format';
import type { Period } from '@/types';

defineProps<{ period: Period & { records: number; employees: number; completeness: number; published_at: string | null } }>();
defineEmits<{ duplicate: [] }>();
</script>

<template>
    <article class="rounded-xl border border-neutral-200 bg-white p-4 shadow-card">
        <div class="flex items-start justify-between gap-2">
            <div><h3 class="text-lg font-bold text-primary-900">{{ period.label }}</h3><p v-if="period.published_at" class="text-xs text-neutral-600">Dipublikasikan {{ formatDate(period.published_at) }}</p></div>
            <PayrollStatusBadge :status="period.status" />
        </div>
        <div class="mt-4">
            <div class="mb-1 flex justify-between text-[13px] text-neutral-600"><span>Kelengkapan data</span><span class="num font-semibold text-neutral-950">{{ period.records }} / {{ period.employees }} ASN</span></div>
            <div class="h-2 overflow-hidden rounded-full bg-neutral-100"><div class="h-full rounded-full bg-primary-600" :style="{ width: `${period.completeness}%` }" /></div>
        </div>
        <div class="mt-4 flex gap-2"><AppButton size="sm" :href="`/admin/periode/${period.id}`">Kelola</AppButton><AppButton size="sm" variant="outline" @click="$emit('duplicate')">Gandakan</AppButton></div>
    </article>
</template>
