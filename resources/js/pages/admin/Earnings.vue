<script setup lang="ts">
import PeriodFilter from '@/components/admin/PeriodFilter.vue';
import AppBadge from '@/components/app/AppBadge.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTable from '@/components/app/AppTable.vue';
import { confirmAction } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';
import { formatNip, rupiah } from '@/lib/format';
import AppPagination from '@/components/app/AppPagination.vue';
import type { Paginated, Period } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';

type Rec = { id: number; name: string; nip: string; status: string; gross: string; official: string; net: string; school: string; take_home: string; validation: string; validation_label: string };
const props = defineProps<{ periods: Period[]; period: Period | null; records: Paginated<Rec>; perPage: number; missing: { id: number; full_name: string; nip: string }[]; filters: { q: string; status: string; validation: string }; counts: { copied: number } }>();

const f = useFilters('/admin/penghasilan', props.filters, () => ({ period: props.period?.id }));
const open = ref(false);
const form = useForm({ period_id: props.period?.id ?? 0, employee_id: '' });
const tone = { valid: 'success', mismatch: 'warning', copied: 'info', invalid: 'danger' } as const;
const columns = [
    { key: 'name', label: 'Nama', primary: true },
    { key: 'status', label: 'Status' },
    { key: 'gross', label: 'Gaji Kotor', align: 'right' as const },
    { key: 'net', label: 'Bersih', align: 'right' as const },
    { key: 'school', label: 'Pot. Sekolah', align: 'right' as const },
    { key: 'take_home', label: 'Diterimakan', align: 'right' as const },
    { key: 'validation', label: 'Validasi' },
];
const create = () => { form.period_id = props.period?.id ?? 0; form.post('/admin/penghasilan', { onSuccess: () => { open.value = false; } }); };
async function verifyAll(): Promise<void> {
    if (props.period && await confirmAction({ title: 'Tandai semua sudah diperiksa?', message: 'Pastikan nominal hasil duplikasi sudah Anda cek. Seluruh rincian berstatus "Perlu verifikasi" akan ditandai valid.', confirmLabel: 'Ya, sudah diperiksa' })) router.post(`/admin/periode/${props.period.id}/verifikasi-semua`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Penghasilan" />
    <AppPageHeader title="Penghasilan" subtitle="Rincian penghasilan dan potongan resmi per ASN.">
        <PeriodFilter v-if="period" :periods="periods" :current="period.id" path="/admin/penghasilan" />
        <template v-if="period && ['draft', 'review'].includes(period.status)">
            <AppButton variant="outline" :href="`/admin/periode/${period.id}/impor`">Impor Excel</AppButton>
            <AppButton v-if="missing.length" @click="open = true">Input manual</AppButton>
        </template>
    </AppPageHeader>

    <AppCard v-if="!period"><p class="text-sm text-neutral-600">Belum ada periode. Buat periode terlebih dahulu di menu Periode Gaji.</p></AppCard>
    <template v-else>
        <div v-if="counts.copied" class="mb-4 flex flex-col gap-2 rounded-xl border border-[#93c5fd] bg-info-bg p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-info-fg"><strong>{{ counts.copied }} rincian</strong> hasil duplikasi periode perlu diverifikasi sebelum publikasi.</p>
            <AppButton size="sm" variant="outline" @click="verifyAll">Tandai semua diperiksa</AppButton>
        </div>
        <p v-if="missing.length" class="mb-3 text-[13px] text-warning-fg">{{ missing.length }} ASN aktif belum memiliki rincian pada periode ini.</p>

        <AppCard flush>
            <div class="grid gap-3 border-b border-neutral-200 p-4 sm:grid-cols-3">
                <AppInput v-model="f.q" placeholder="Cari nama atau NIP…" :icon="Search" inputmode="search" />
                <AppSelect v-model="f.status" placeholder="Semua status ASN" :options="[{ value: 'PNS', label: 'PNS' }, { value: 'PPPK', label: 'PPPK' }]" />
                <AppSelect v-model="f.validation" placeholder="Semua validasi" :options="[{ value: 'valid', label: 'Valid' }, { value: 'mismatch', label: 'Selisih sumber' }, { value: 'copied', label: 'Perlu verifikasi' }, { value: 'invalid', label: 'Tidak valid' }]" />
            </div>
            <AppTable :columns="columns" :rows="records.data" empty-title="Belum ada rincian" empty-text="Impor file Excel atau input manual untuk menambah data.">
                <template #cell-name="{ row }"><span class="font-semibold text-primary-900">{{ row.name }}</span><span class="num block text-xs text-neutral-600">{{ formatNip(row.nip) }}</span></template>
                <template #cell-gross="{ row }">{{ rupiah(row.gross) }}</template>
                <template #cell-net="{ row }">{{ rupiah(row.net) }}</template>
                <template #cell-school="{ row }">{{ rupiah(row.school) }}</template>
                <template #cell-take_home="{ row }"><strong>{{ rupiah(row.take_home) }}</strong></template>
                <template #cell-validation="{ row }"><AppBadge :tone="tone[row.validation as keyof typeof tone]">{{ row.validation_label }}</AppBadge></template>
                <template #actions="{ row }"><AppButton size="sm" variant="ghost" :href="`/admin/penghasilan/${row.id}`">{{ period.status === 'closed' ? 'Lihat' : 'Ubah' }}</AppButton></template>
            </AppTable>
            <AppPagination :page="records" :per-page="perPage" :per-page-options="[10, 15, 25, 50]" />
        </AppCard>
    </template>

    <AppDialog v-model:open="open" title="Input Rincian Manual" description="Pilih ASN yang belum memiliki rincian. Seluruh komponen aktif akan dibuat bernilai Rp0.">
        <AppSelect v-model="form.employee_id" label="ASN" placeholder="Pilih ASN…" :options="missing.map((m) => ({ value: m.id, label: `${m.full_name} (${m.nip})` }))" :error="form.errors.employee_id" required />
        <template #footer><AppButton variant="outline" @click="open = false">Batal</AppButton><AppButton :disabled="!form.employee_id" :loading="form.processing" @click="create">Buat rincian</AppButton></template>
    </AppDialog>
</template>
