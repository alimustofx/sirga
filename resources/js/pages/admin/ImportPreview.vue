<script setup lang="ts">
import ImportValidationTable, { type ImportRowData } from '@/components/admin/ImportValidationTable.vue';
import PayrollImportWizard from '@/components/admin/PayrollImportWizard.vue';
import StatCard from '@/components/admin/StatCard.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppCheckbox from '@/components/app/AppCheckbox.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import { confirmAction } from '@/composables/useConfirm';
import { rupiah } from '@/lib/format';
import type { Period } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    batch: { id: number; filename: string; title: string | null; status: string; state: string; total: number; valid: number; invalid: number; skipped: number; overwritable: number; confirmed_at: string | null };
    period: Period; canConfirm: boolean;
    notices: { type: string; message: string }[];
    totals: { gross: string; official: string; net: string };
    rows: ImportRowData[];
    result: { imported: number; overwritten: number; mismatch: number; skipped: number } | null;
}>();

const overwrite = ref(false);
const blocking = computed(() => props.rows.filter((r) => r.status === 'error' && !(overwrite.value && r.errors.filter((e) => e.blocking && !e.resolved).every((e) => e.type === 'already_imported'))).length);
const step = computed(() => (props.batch.state === 'imported' ? 6 : 4));
const mismatches = computed(() => props.rows.filter((r) => r.status !== 'skipped' && r.errors.some((e) => e.type === 'calc_mismatch')).length);

async function confirm(): Promise<void> {
    const ok = await confirmAction({ title: 'Konfirmasi impor?', message: `${props.batch.valid + (overwrite.value ? props.batch.overwritable : 0)} baris akan disimpan ke periode ${props.period.label}.${mismatches.value ? ` ${mismatches.value} baris memiliki selisih sumber dan harus diperiksa sebelum publikasi.` : ''}`, confirmLabel: 'Ya, simpan data' });
    if (ok) router.post(`/admin/impor/${props.batch.id}/konfirmasi`, { overwrite: overwrite.value });
}
async function cancel(): Promise<void> {
    if (await confirmAction({ title: 'Batalkan impor?', message: 'File ini dibuang dan tidak ada data yang disimpan.', danger: true, confirmLabel: 'Batalkan impor' })) router.post(`/admin/impor/${props.batch.id}/batal`);
}
</script>

<template>
    <Head title="Hasil Validasi Impor" />
    <AppButton variant="ghost" size="sm" :href="`/admin/periode/${period.id}`" class="mb-2 -ml-2">← {{ period.label }}</AppButton>
    <AppPageHeader :title="batch.state === 'imported' ? 'Impor Selesai' : 'Hasil Validasi'" :subtitle="`${batch.filename} · ${batch.status} · periode ${period.label}`" />
    <PayrollImportWizard :current="step" />

    <AppCard v-if="batch.state === 'imported'" class="mb-4">
        <h2 class="text-lg font-bold text-success-fg">Data berhasil disimpan</h2>
        <ul v-if="result" class="mt-2 space-y-1 text-sm"><li>{{ result.imported }} baris diimpor<template v-if="result.overwritten"> ({{ result.overwritten }} menimpa data lama)</template>.</li><li v-if="result.skipped">{{ result.skipped }} baris diabaikan.</li><li v-if="result.mismatch" class="text-warning-fg">{{ result.mismatch }} baris memiliki selisih dengan file sumber — periksa di menu Penghasilan.</li></ul>
        <div class="mt-4 flex flex-wrap gap-2"><AppButton :href="`/admin/penghasilan?period=${period.id}`">Lihat penghasilan</AppButton><AppButton variant="outline" :href="`/admin/periode/${period.id}`">Kembali ke periode</AppButton></div>
    </AppCard>
    <div v-else-if="batch.state === 'cancelled'" class="mb-4 rounded-xl border border-neutral-200 bg-neutral-100 p-4 text-sm">Impor ini sudah dibatalkan.</div>

    <div v-for="n in notices" :key="n.message" class="mb-3 rounded-xl border border-[#fcd34d] bg-warning-bg p-3 text-sm text-warning-fg">{{ n.message }}</div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <StatCard label="Total baris" :value="batch.total" />
        <StatCard label="Valid" :value="batch.valid" />
        <StatCard label="Bermasalah" :value="batch.invalid" :tone="batch.invalid ? 'danger' : 'default'" />
        <StatCard label="Diabaikan" :value="batch.skipped" />
        <StatCard label="Selisih sumber" :value="mismatches" :tone="mismatches ? 'warning' : 'default'" class="col-span-2 lg:col-span-1" />
    </div>
    <p class="mt-3 text-[13px] text-neutral-600">Total hitung sistem (baris tidak diabaikan): kotor {{ rupiah(totals.gross) }} · potongan resmi {{ rupiah(totals.official) }} · bersih {{ rupiah(totals.net) }}</p>

    <AppCard class="mt-4" title="Daftar Baris" flush><ImportValidationTable :rows="rows" :editable="canConfirm" /></AppCard>

    <AppCard v-if="canConfirm" class="mt-4" title="Konfirmasi">
        <AppCheckbox v-if="batch.overwritable" v-model="overwrite" :label="`Timpa ${batch.overwritable} rincian yang sudah ada pada periode ini`" hint="Versi lama disimpan di riwayat revisi dan tercatat di audit log." />
        <p v-if="blocking" class="mb-3 text-sm text-danger-fg">Masih ada {{ blocking }} baris bermasalah. Perbaiki file sumber atau abaikan baris tersebut (baris NIP tidak terdaftar masuk daftar pengecualian).</p>
        <div class="flex flex-col gap-2 sm:flex-row"><AppButton size="lg" :disabled="blocking > 0" @click="confirm">Konfirmasi & Simpan Data</AppButton><AppButton size="lg" variant="outline" @click="cancel">Batalkan impor</AppButton></div>
    </AppCard>
</template>
