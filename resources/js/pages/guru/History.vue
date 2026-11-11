<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTable from '@/components/app/AppTable.vue';
import PaymentStatusBadge from '@/components/payment/PaymentStatusBadge.vue';
import { rupiah } from '@/lib/format';
import type { PaymentStatus, Period } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Item = { period: Period; gross: string; net: string; school_total: string; take_home: string; payment_status: PaymentStatus; payment_label: string; has_payable: boolean; id: number };
const props = defineProps<{ items: Omit<Item, 'id'>[] }>();

const year = ref('');
const years = computed(() => [...new Set(props.items.map((i) => i.period.year))].map((y) => ({ value: String(y), label: String(y) })));
const rows = computed(() => props.items.filter((i) => !year.value || String(i.period.year) === year.value).map((i) => ({ ...i, id: i.period.id, label: i.period.label })));
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
