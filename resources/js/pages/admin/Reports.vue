<script setup lang="ts">
import PeriodFilter from '@/components/admin/PeriodFilter.vue';
import StatCard from '@/components/admin/StatCard.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTable from '@/components/app/AppTable.vue';
import { useFilters } from '@/composables/useFilters';
import { formatNip, rupiah } from '@/lib/format';
import type { Period } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    periods: Period[]; period: Period | null; filters: { status: string; payment: string };
    summary: { count: number; gross: string; official: string; net: string; school: string; take_home: string; paid: number; pending: number };
    rows: { id: number; name: string; nip: string; status: string; gross: string; net: string; school: string; take_home: string; payment_label: string }[]; truncated: boolean;
}>();
const f = useFilters('/admin/laporan', props.filters, () => ({ period: props.period?.id }));
const qs = computed(() => new URLSearchParams({ period: String(props.period?.id ?? ''), ...(f.status ? { status: f.status } : {}), ...(f.payment ? { payment: f.payment } : {}) }).toString());
const columns = [{ key: 'name', label: 'Nama', primary: true }, { key: 'status', label: 'Status' }, { key: 'gross', label: 'Gaji Kotor', align: 'right' as const }, { key: 'net', label: 'Bersih', align: 'right' as const }, { key: 'school', label: 'Pot. Sekolah', align: 'right' as const }, { key: 'take_home', label: 'Diterimakan', align: 'right' as const }, { key: 'payment_label', label: 'Pembayaran' }];
</script>

<template>
    <Head title="Laporan & Export" />
    <AppPageHeader title="Laporan & Export" subtitle="Ringkasan periode dan unduhan Excel."><PeriodFilter v-if="period" :periods="periods" :current="period.id" path="/admin/laporan" /></AppPageHeader>
    <AppCard v-if="!period"><p class="text-sm text-neutral-600">Belum ada periode.</p></AppCard>
    <template v-else>
        <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <AppSelect v-model="f.status" placeholder="Semua status ASN" :options="[{ value: 'PNS', label: 'PNS' }, { value: 'PPPK', label: 'PPPK' }]" />
            <AppSelect v-model="f.payment" placeholder="Semua status bayar" :options="[{ value: 'belum_dibayar', label: 'Belum Dibayar' }, { value: 'menunggu_verifikasi', label: 'Menunggu Verifikasi' }, { value: 'lunas', label: 'Lunas' }, { value: 'ditolak', label: 'Ditolak' }]" />
        </div>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <StatCard label="Jumlah ASN" :value="summary.count" :hint="`${summary.paid} lunas · ${summary.pending} menunggu`" />
            <StatCard label="Total gaji kotor" :value="rupiah(summary.gross)" />
            <StatCard label="Total potongan sekolah" :value="rupiah(summary.school)" />
            <StatCard label="Total diterimakan" :value="rupiah(summary.take_home)" />
        </div>
        <div class="my-4 flex flex-col gap-2 sm:flex-row">
            <AppButton external :href="`/admin/laporan/export?type=full&${qs}`">Unduh rekap lengkap (Excel)</AppButton>
            <AppButton external variant="outline" :href="`/admin/laporan/export?type=deductions&${qs}`">Rekap potongan sekolah</AppButton>
            <AppButton external variant="outline" :href="`/admin/laporan/export?type=payments&${qs}`">Status pembayaran</AppButton>
        </div>
        <AppCard flush>
            <AppTable :columns="columns" :rows="rows" empty-title="Tidak ada data" empty-text="Ubah filter atau periksa data periode.">
                <template #cell-name="{ row }"><span class="font-semibold text-primary-900">{{ row.name }}</span><span class="num block text-xs text-neutral-600">{{ formatNip(row.nip) }}</span></template>
                <template v-for="k in ['gross', 'net', 'school', 'take_home']" :key="k" #[`cell-${k}`]="{ row }">{{ rupiah(row[k]) }}</template>
            </AppTable>
            <p v-if="truncated" class="border-t border-neutral-200 px-4 py-2 text-[13px] text-neutral-600">Pratinjau dibatasi 200 baris. Unduh Excel untuk data lengkap.</p>
        </AppCard>
    </template>
</template>
