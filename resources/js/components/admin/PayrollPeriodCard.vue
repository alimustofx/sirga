<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import ProgressBar from '@/components/app/ProgressBar.vue';
import PayrollStatusBadge from '@/components/payroll/PayrollStatusBadge.vue';
import { formatDate, monthName } from '@/lib/format';
import type { Period } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{ period: Period & { records: number; employees: number; completeness: number; published_at: string | null } }>();
defineEmits<{ duplicate: [] }>();
</script>

<template>
    <article class="rounded-xl border border-neutral-200 bg-white p-5 transition-colors hover:border-neutral-400">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="eyebrow">{{ period.year }}</p>
                <Link :href="`/admin/periode/${period.id}`" class="block text-2xl font-bold tracking-tight text-primary-900 hover:underline">{{ monthName(period.month) }}</Link>
            </div>
            <PayrollStatusBadge :status="period.status" />
        </div>
        <div class="mt-5">
            <div class="mb-1.5 flex items-baseline justify-between text-[13px]"><span class="text-neutral-600">Kelengkapan data</span><span class="num font-semibold text-neutral-950">{{ period.completeness }}%</span></div>
            <ProgressBar :value="period.completeness" :tone="period.status === 'published' || period.status === 'closed' ? 'navy' : 'teal'" />
            <p class="num mt-1.5 text-xs text-neutral-600">{{ period.records }} dari {{ period.employees }} ASN memiliki rincian<template v-if="period.published_at"> · terbit {{ formatDate(period.published_at) }}</template></p>
        </div>
        <div class="mt-4 flex gap-2"><AppButton size="sm" :href="`/admin/periode/${period.id}`">Kelola</AppButton><AppButton size="sm" variant="outline" @click="$emit('duplicate')">Gandakan</AppButton></div>
    </article>
</template>
