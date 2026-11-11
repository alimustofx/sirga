<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTable from '@/components/app/AppTable.vue';
import PaymentStatusBadge from '@/components/payment/PaymentStatusBadge.vue';
import TrendBars from '@/components/viz/TrendBars.vue';
import { monthName, rupiah } from '@/lib/format';
import type { PaymentStatus, Period } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Item = { period: Period; gross: string; net: string; school_total: string; take_home: string; payment_status: PaymentStatus; payment_label: string; has_payable: boolean };
const props = defineProps<{ items: Item[] }>();

const year = ref('');
const years = computed(() => [...new Set(props.items.map((i) => i.period.year))].map((y) => ({ value: String(y), label: String(y) })));
const filtered = computed(() => props.items.filter((i) => !year.value || String(i.period.year) === year.value));
const rows = computed(() => filtered.value.map((i) => ({ ...i, id: i.period.id, label: i.period.label })));
const bars = computed(() => [...filtered.value].reverse().slice(-12).map((i) => ({ label: monthName(i.period.month).slice(0, 3), value: Number(i.take_home), full: i.period.label })));
const total = computed(() => filtered.value.reduce((a, i) => a + Number(i.take_home), 0));
const avg = computed(() => (filtered.value.length ? total.value / filtered.value.length : 0));
const columns = [
    { key: 'label', label: 'Periode', primary: true },
    { key: 'net', label: 'Penghasilan Bersih', align: 'right' as const },
    { key: 'school_total', label: 'Potongan Sekolah', align: 'right' as const },
    { key: 'take_home', label: 'Diterimakan', align: 'right' as const },
    { key: 'payment', label: 'Pembayaran' },
];
</script>

<template>
    <Head title="Riwayat Gaji" />
    <AppPageHeader title="Riwayat Gaji" subtitle="Seluruh periode yang pernah dipublikasikan untuk Anda.">
        <AppSelect v-if="years.length > 1" v-model="year" :options="years" placeholder="Semua tahun" class="w-44" />
    </AppPageHeader>

    <div v-if="filtered.length" class="mb-4 grid gap-4 lg:grid-cols-3">
        <AppCard title="Ringkasan" class="lg:col-span-1" accent="gold">
            <p class="text-[13px] text-neutral-600">Total diterimakan{{ year ? ` ${year}` : '' }}</p>
            <p class="num text-2xl font-bold tracking-tight text-primary-900">{{ rupiah(total) }}</p>
            <p class="mt-3 text-[13px] text-neutral-600">Rata-rata per bulan</p>
            <p class="num text-lg font-semibold text-neutral-950">{{ rupiah(Math.round(avg)) }}</p>
            <p class="mt-3 text-xs text-neutral-600">{{ filtered.length }} periode</p>
        </AppCard>
        <AppCard title="Grafik per periode" class="lg:col-span-2"><TrendBars :items="bars" :height="140" aria-label="Grafik yang diterimakan per periode" /></AppCard>
    </div>

    <AppCard flush>
        <AppTable :columns="columns" :rows="rows" empty-title="Belum ada riwayat gaji" empty-text="Riwayat akan muncul setelah periode gaji dipublikasikan.">
            <template #cell-net="{ row }">{{ rupiah(row.net) }}</template>
            <template #cell-school_total="{ row }">{{ rupiah(row.school_total) }}</template>
            <template #cell-take_home="{ row }"><strong class="text-primary-900">{{ rupiah(row.take_home) }}</strong></template>
            <template #cell-payment="{ row }"><PaymentStatusBadge v-if="row.has_payable" :status="row.payment_status" :label="row.payment_label" /><span v-else class="text-neutral-400">—</span></template>
            <template #actions="{ row }"><AppButton size="sm" variant="ghost" :href="`/saya/rincian/${row.period.id}`">Rincian</AppButton></template>
        </AppTable>
    </AppCard>
</template>
