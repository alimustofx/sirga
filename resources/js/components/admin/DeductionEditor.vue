<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppMoneyInput from '@/components/app/AppMoneyInput.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTable from '@/components/app/AppTable.vue';
import AppTextarea from '@/components/app/AppTextarea.vue';
import { confirmAction } from '@/composables/useConfirm';
import { rupiah } from '@/lib/format';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Cfg = { id: number; name: string; description: string | null; default_amount: string; is_special: boolean; master_default: string | null };
type Row = { id: number; name: string; nip: string; status: string; deductions: { id: number; config_id: number; name: string; amount: string }[]; total: string; take_home: string };
const props = defineProps<{ configs: Cfg[]; rows: Row[]; needsReason: boolean; locked: boolean; periodId: number; editable: boolean }>();

const massOpen = ref(false);
const massCfg = ref<Cfg | null>(null);
const mass = useForm({ amount: '', scope: 'all', reason: '' });
const rowOpen = ref(false);
const rowEdit = ref<Row | null>(null);
const rowForm = useForm<{ amounts: Record<number, string>; reason: string }>({ amounts: {}, reason: '' });

function openMass(c: Cfg): void { massCfg.value = c; mass.reset(); mass.amount = (c.master_default ?? c.default_amount).replace(/\.00$/, ''); mass.clearErrors(); massOpen.value = true; }
function applyMass(): void {
    if (!massCfg.value) return;
    mass.post(`/admin/potongan/konfig/${massCfg.value.id}/massal`, { preserveScroll: true, onSuccess: () => { massOpen.value = false; } });
}
function openRow(r: Row): void { rowEdit.value = r; rowForm.reset(); rowForm.clearErrors(); rowForm.amounts = Object.fromEntries(r.deductions.map((d) => [d.id, d.amount.replace(/\.00$/, '')])); rowOpen.value = true; }
function saveRow(): void {
    if (!rowEdit.value) return;
    rowForm.put(`/admin/potongan/rincian/${rowEdit.value.id}`, { preserveScroll: true, onSuccess: () => { rowOpen.value = false; } });
}
async function removeCfg(c: Cfg): Promise<void> {
    if (await confirmAction({ title: 'Hapus dari periode?', message: `"${c.name}" akan dihapus dari seluruh rincian periode ini.`, danger: true, confirmLabel: 'Hapus' })) router.delete(`/admin/potongan/konfig/${c.id}`, { preserveScroll: true });
}

const cols = computed(() => [
    { key: 'name', label: 'Nama', primary: true },
    ...props.configs.map((c) => ({ key: `c${c.id}`, label: c.name, align: 'right' as const })),
    { key: 'total', label: 'Total', align: 'right' as const },
    { key: 'take_home', label: 'Diterimakan', align: 'right' as const },
]);
const amountOf = (r: Row, cid: number) => r.deductions.find((d) => d.config_id === cid)?.amount ?? '0';
</script>

<template>
    <div class="space-y-4">
        <AppCard title="Jenis Potongan pada Periode Ini" flush>
            <p v-if="!configs.length" class="px-5 py-6 text-center text-sm text-neutral-600">Belum ada jenis potongan. Tambahkan potongan khusus atau salin dari periode sebelumnya.</p>
            <ul v-else class="divide-y divide-neutral-100">
                <li v-for="c in configs" :key="c.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-5">
                    <div class="min-w-0"><p class="text-sm font-semibold">{{ c.name }} <span v-if="c.is_special" class="ml-1 text-xs font-normal text-primary-600">khusus periode</span></p><p class="num text-[13px] text-neutral-600">Default {{ rupiah(c.default_amount) }}</p></div>
                    <div v-if="editable" class="flex gap-1"><AppButton size="sm" variant="outline" @click="openMass(c)">Terapkan nominal</AppButton><AppButton v-if="!locked" size="sm" variant="ghost" @click="removeCfg(c)">Hapus</AppButton></div>
                </li>
            </ul>
        </AppCard>

        <AppCard title="Nominal per ASN" flush>
            <AppTable :columns="cols" :rows="rows" empty-title="Belum ada rincian ASN" empty-text="Impor atau input penghasilan terlebih dahulu.">
                <template #cell-name="{ row }"><span class="font-semibold text-primary-900">{{ row.name }}</span><span class="ml-1.5 text-xs text-neutral-600">{{ row.status }}</span></template>
                <template v-for="c in configs" :key="c.id" #[`cell-c${c.id}`]="{ row }">{{ rupiah(amountOf(row as Row, c.id)) }}</template>
                <template #cell-total="{ row }"><strong>{{ rupiah(row.total) }}</strong></template>
                <template #cell-take_home="{ row }">{{ rupiah(row.take_home) }}</template>
                <template v-if="editable" #actions="{ row }"><AppButton size="sm" variant="ghost" @click="openRow(row as Row)">Ubah</AppButton></template>
            </AppTable>
        </AppCard>
    </div>

    <AppDialog v-model:open="massOpen" :title="`Terapkan nominal — ${massCfg?.name}`" description="Mengubah nominal potongan ini untuk ASN pada periode ini.">
        <div class="space-y-3">
            <AppMoneyInput v-model="mass.amount" label="Nominal per ASN" :error="mass.errors.amount" required />
            <AppSelect v-model="mass.scope" label="Berlaku untuk" :options="[{ value: 'all', label: 'Semua ASN' }, { value: 'PNS', label: 'PNS saja' }, { value: 'PPPK', label: 'PPPK saja' }]" />
            <AppTextarea v-model="mass.reason" :label="needsReason ? 'Alasan perubahan (wajib)' : 'Alasan (opsional)'" :rows="2" :error="mass.errors.reason" />
        </div>
        <template #footer><AppButton variant="outline" @click="massOpen = false">Batal</AppButton><AppButton :loading="mass.processing" @click="applyMass">Terapkan</AppButton></template>
    </AppDialog>

    <AppDialog v-model:open="rowOpen" :title="`Potongan — ${rowEdit?.name}`" description="Isi Rp0 untuk mengecualikan ASN dari suatu potongan.">
        <div class="space-y-3">
            <AppMoneyInput v-for="d in rowEdit?.deductions ?? []" :key="d.id" v-model="rowForm.amounts[d.id]" :label="d.name" />
            <AppTextarea v-model="rowForm.reason" :label="needsReason ? 'Alasan perubahan (wajib)' : 'Alasan (opsional)'" :rows="2" :error="rowForm.errors.reason" />
        </div>
        <template #footer><AppButton variant="outline" @click="rowOpen = false">Batal</AppButton><AppButton :loading="rowForm.processing" @click="saveRow">Simpan</AppButton></template>
    </AppDialog>
</template>
