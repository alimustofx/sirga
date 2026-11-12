<script setup lang="ts">
import PeriodFilter from '@/components/admin/PeriodFilter.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppTable from '@/components/app/AppTable.vue';
import PaymentStatusBadge from '@/components/payment/PaymentStatusBadge.vue';
import { useFilters } from '@/composables/useFilters';
import { formatDate, formatNip, rupiah } from '@/lib/format';
import AppPagination from '@/components/app/AppPagination.vue';
import type { Paginated, PaymentStatus, Period } from '@/types';
import { Head, router } from '@inertiajs/vue3';

type Item = { record_id: number; payment_id: number | null; name: string; nip: string; status: PaymentStatus; status_label: string; amount: string; submitted_at: string | null; verified_at: string | null };
const props = defineProps<{ periods: Period[]; period: Period | null; items: Paginated<Item>; perPage: number; counts: Record<string, number>; filters: { q: string; status: string } }>();
const f = useFilters('/admin/pembayaran', props.filters, () => ({ period: props.period?.id }));
const tabs: { v: string; l: string }[] = [{ v: '', l: 'Semua' }, { v: 'menunggu_verifikasi', l: 'Menunggu' }, { v: 'belum_dibayar', l: 'Belum dibayar' }, { v: 'ditolak', l: 'Ditolak' }, { v: 'lunas', l: 'Lunas' }];
const columns = [{ key: 'name', label: 'Nama', primary: true }, { key: 'amount', label: 'Potongan', align: 'right' as const }, { key: 'status', label: 'Status' }, { key: 'submitted_at', label: 'Diunggah' }];
const total = () => Object.values(props.counts).reduce((a, b) => a + b, 0);
</script>

<template>
    <Head title="Pembayaran" />
    <AppPageHeader title="Pembayaran Potongan" subtitle="Verifikasi bukti transfer potongan sekolah."><PeriodFilter v-if="period" :periods="periods" :current="period.id" path="/admin/pembayaran" /></AppPageHeader>
    <AppCard flush>
        <div class="space-y-3 border-b border-neutral-200 p-4">
            <div class="flex gap-1 overflow-x-auto" role="group" aria-label="Filter status">
                <button v-for="t in tabs" :key="t.v" type="button" :aria-pressed="f.status === t.v" :class="['min-h-10 shrink-0 rounded-lg px-3 text-[13px] font-semibold', f.status === t.v ? 'bg-primary-900 text-white' : 'bg-neutral-100 text-neutral-800 hover:bg-neutral-200']" @click="f.status = t.v">{{ t.l }} <span class="num opacity-70">{{ t.v ? (counts[t.v] ?? 0) : total() }}</span></button>
            </div>
            <AppInput v-model="f.q" placeholder="Cari nama atau NIP…" inputmode="search" />
        </div>
        <AppTable :columns="columns" :rows="items.data" row-key="record_id" empty-title="Tidak ada data pembayaran" empty-text="Pembayaran muncul setelah periode dipublikasikan dan ada potongan sekolah.">
            <template #cell-name="{ row }"><span class="font-semibold text-primary-900">{{ row.name }}</span><span class="num block text-xs text-neutral-600">{{ formatNip(row.nip) }}</span></template>
            <template #cell-amount="{ row }">{{ rupiah(row.amount) }}</template>
            <template #cell-status="{ row }"><PaymentStatusBadge :status="row.status" :label="row.status_label" /></template>
            <template #cell-submitted_at="{ row }">{{ formatDate(row.submitted_at, true) }}</template>
            <template #actions="{ row }"><AppButton v-if="row.payment_id" size="sm" :variant="row.status === 'menunggu_verifikasi' ? 'primary' : 'ghost'" @click="router.visit(`/admin/pembayaran/${row.payment_id}`)">{{ row.status === 'menunggu_verifikasi' ? 'Verifikasi' : 'Detail' }}</AppButton></template>
        </AppTable>
        <AppPagination :page="items" :per-page="perPage" :per-page-options="[10, 15, 25, 50]" />
    </AppCard>
</template>
