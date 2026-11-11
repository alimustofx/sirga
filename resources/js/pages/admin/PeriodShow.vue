<script setup lang="ts">
import StatCard from '@/components/admin/StatCard.vue';
import AppBadge from '@/components/app/AppBadge.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppTable from '@/components/app/AppTable.vue';
import PayrollStatusBadge from '@/components/payroll/PayrollStatusBadge.vue';
import { confirmAction } from '@/composables/useConfirm';
import { formatDate, rupiah } from '@/lib/format';
import type { Period } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Check, TriangleAlert } from '@lucide/vue';

const props = defineProps<{
    period: Period & { notes: string | null; published_at: string | null; closed_at: string | null; records: number };
    readiness: { ready: boolean; issues: { key: string; message: string; items: string[]; count: number }[]; warnings: string[]; stats: { employees: number; complete: number } };
    totals: { gross: string; official: string; net: string; school: string; take_home: string };
    batches: { id: number; filename: string; status: string; state: string; total: number; valid: number; invalid: number; at: string }[];
    configs: { id: number; name: string; amount: string }[];
    canDelete: boolean;
}>();

type Act = { path: string; title: string; message: string; label: string; danger?: boolean };
async function run(a: Act): Promise<void> {
    if (await confirmAction({ title: a.title, message: a.message, confirmLabel: a.label, danger: a.danger })) router.post(`/admin/periode/${props.period.id}/${a.path}`, {}, { preserveScroll: true });
}
async function remove(): Promise<void> {
    if (await confirmAction({ title: 'Hapus periode?', message: 'Periode draft kosong ini akan dihapus.', danger: true, confirmLabel: 'Hapus' })) router.delete(`/admin/periode/${props.period.id}`);
}
const state = { previewed: 'Menunggu konfirmasi', imported: 'Diimpor', cancelled: 'Dibatalkan' } as Record<string, string>;
const bcols = [{ key: 'filename', label: 'File', primary: true }, { key: 'status', label: 'ASN' }, { key: 'total', label: 'Baris', align: 'right' as const }, { key: 'invalid', label: 'Bermasalah', align: 'right' as const }, { key: 'state', label: 'Status' }];
</script>

<template>
    <Head :title="`Periode ${period.label}`" />
    <AppButton variant="ghost" size="sm" href="/admin/periode" class="mb-2 -ml-2">← Semua periode</AppButton>
    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="page-title">{{ period.label }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-neutral-600"><PayrollStatusBadge :status="period.status" /><span v-if="period.published_at">Dipublikasikan {{ formatDate(period.published_at, true) }}</span><span v-if="period.closed_at">· Ditutup {{ formatDate(period.closed_at, true) }}</span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <template v-if="period.status === 'draft'"><AppButton @click="run({ path: 'review', title: 'Pindah ke Review?', message: 'Data masih dapat diubah pada tahap Review. Guru belum dapat melihat rincian.', label: 'Pindah ke Review' })">Pindah ke Review</AppButton></template>
            <template v-if="period.status === 'review'">
                <AppButton variant="outline" @click="run({ path: 'draft', title: 'Kembalikan ke Draft?', message: 'Periode kembali ke tahap persiapan.', label: 'Kembalikan' })">Kembali ke Draft</AppButton>
                <AppButton :disabled="!readiness.ready" @click="run({ path: 'publikasi', title: 'Publikasikan periode?', message: `Seluruh guru akan dapat melihat rincian gaji ${period.label} dan menerima notifikasi. Perubahan setelah ini memerlukan alasan dan tercatat di audit log.`, label: 'Publikasikan' })">Publikasikan</AppButton>
            </template>
            <AppButton v-if="period.status === 'published'" variant="danger" @click="run({ path: 'tutup', title: 'Tutup periode?', message: 'Data akan terkunci dari perubahan operasional dan guru tidak dapat mengunggah bukti baru.', label: 'Tutup periode', danger: true })">Tutup periode</AppButton>
            <AppButton v-if="canDelete" variant="outline" @click="remove">Hapus</AppButton>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <StatCard label="Kelengkapan" :value="`${readiness.stats.complete}/${readiness.stats.employees}`" hint="ASN aktif punya rincian" />
        <StatCard label="Gaji kotor" :value="rupiah(totals.gross)" />
        <StatCard label="Penghasilan bersih" :value="rupiah(totals.net)" />
        <StatCard label="Potongan sekolah" :value="rupiah(totals.school)" />
        <StatCard label="Total diterimakan" :value="rupiah(totals.take_home)" class="col-span-2 lg:col-span-1" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <AppCard title="Pemeriksaan Sebelum Publikasi">
                <div v-if="readiness.ready" class="flex items-start gap-3 rounded-lg bg-success-bg p-3 text-sm text-success-fg"><Check class="mt-0.5 size-5 shrink-0" /><p><strong>Siap dipublikasikan.</strong> Seluruh pemeriksaan kelengkapan terpenuhi.</p></div>
                <ul v-else class="space-y-3">
                    <li v-for="i in readiness.issues" :key="i.key" class="rounded-lg border border-[#fcd34d] bg-warning-bg p-3 text-sm text-warning-fg">
                        <p class="flex items-start gap-2 font-semibold"><TriangleAlert class="mt-0.5 size-4 shrink-0" />{{ i.message }}</p>
                        <p v-if="i.items.length" class="mt-1 pl-6 text-[13px]">{{ i.items.join(', ') }}<template v-if="i.count > i.items.length"> dan {{ i.count - i.items.length }} lainnya</template></p>
                    </li>
                </ul>
                <ul v-if="readiness.warnings.length" class="mt-3 space-y-1 text-[13px] text-neutral-600"><li v-for="w in readiness.warnings" :key="w">• {{ w }}</li></ul>
                <p v-if="period.status === 'draft' && !readiness.ready" class="mt-3 text-[13px] text-neutral-600">Selesaikan masalah di atas, pindahkan ke Review, lalu publikasikan.</p>
            </AppCard>

            <AppCard title="Riwayat Impor Excel" flush>
                <template #header><AppButton v-if="['draft', 'review'].includes(period.status)" size="sm" :href="`/admin/periode/${period.id}/impor`">Impor baru</AppButton></template>
                <AppTable :columns="bcols" :rows="batches" empty-title="Belum ada impor">
                    <template #cell-filename="{ row }"><a :href="`/admin/impor/${row.id}`" class="font-semibold text-primary-600 hover:underline">{{ row.filename }}</a><span class="block text-xs text-neutral-600">{{ formatDate(row.at, true) }}</span></template>
                    <template #cell-state="{ row }"><AppBadge :tone="row.state === 'imported' ? 'success' : row.state === 'previewed' ? 'warning' : 'neutral'">{{ state[row.state] }}</AppBadge></template>
                </AppTable>
            </AppCard>
        </div>

        <div class="space-y-4">
            <AppCard title="Menu Cepat">
                <div class="flex flex-col gap-2">
                    <AppButton variant="outline" :href="`/admin/penghasilan?period=${period.id}`">Penghasilan ({{ period.records }})</AppButton>
                    <AppButton variant="outline" :href="`/admin/potongan?period=${period.id}`">Potongan sekolah</AppButton>
                    <AppButton variant="outline" :href="`/admin/pembayaran?period=${period.id}`">Pembayaran</AppButton>
                    <AppButton variant="outline" :href="`/admin/laporan?period=${period.id}`">Laporan & export</AppButton>
                </div>
            </AppCard>
            <AppCard title="Potongan Sekolah Periode Ini" flush>
                <ul class="divide-y divide-neutral-100 text-sm"><li v-for="c in configs" :key="c.id" class="flex justify-between px-4 py-2 sm:px-5"><span>{{ c.name }}</span><span class="num text-neutral-600">{{ rupiah(c.amount) }}</span></li><li v-if="!configs.length" class="px-5 py-4 text-neutral-600">Belum ada.</li></ul>
            </AppCard>
            <p v-if="period.notes" class="rounded-lg bg-neutral-100 p-3 text-[13px] text-neutral-800">{{ period.notes }}</p>
        </div>
    </div>
</template>
