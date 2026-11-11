<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import PayrollStatusBadge from '@/components/payroll/PayrollStatusBadge.vue';
import ProgressRing from '@/components/viz/ProgressRing.vue';
import { formatDate, monthName } from '@/lib/format';
import type { Period } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ period: Period & { records: number; employees: number; completeness: number; published_at: string | null } }>();
defineEmits<{ duplicate: [] }>();
const color = computed(() => ({ draft: '#a3afbc', review: '#1e40af', published: '#287b8c', closed: '#64748b' })[props.period.status]);
</script>

<template>
    <article class="card-lift relative overflow-hidden rounded-2xl border border-neutral-200 bg-white p-5 shadow-card">
        <span class="absolute inset-y-0 left-0 w-1" :style="{ background: color }" aria-hidden="true" />
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-wider text-neutral-600 uppercase">{{ period.year }}</p>
                <Link :href="`/admin/periode/${period.id}`" class="block text-2xl font-bold tracking-tight text-primary-900 hover:underline">{{ monthName(period.month) }}</Link>
                <div class="mt-2"><PayrollStatusBadge :status="period.status" /></div>
                <p v-if="period.published_at" class="mt-2 text-xs text-neutral-600">Terbit {{ formatDate(period.published_at) }}</p>
            </div>
            <ProgressRing :value="period.completeness" :color="color" :size="76" :thickness="8" />
        </div>
        <p class="num mt-3 text-[13px] text-neutral-600">{{ period.records }} dari {{ period.employees }} ASN memiliki rincian</p>
        <div class="mt-4 flex gap-2"><AppButton size="sm" :href="`/admin/periode/${period.id}`">Kelola</AppButton><AppButton size="sm" variant="outline" @click="$emit('duplicate')">Gandakan</AppButton></div>
    </article>
</template>
