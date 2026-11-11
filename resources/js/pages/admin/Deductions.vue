<script setup lang="ts">
import DeductionEditor from '@/components/admin/DeductionEditor.vue';
import PeriodFilter from '@/components/admin/PeriodFilter.vue';
import AppBadge from '@/components/app/AppBadge.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppCheckbox from '@/components/app/AppCheckbox.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppMoneyInput from '@/components/app/AppMoneyInput.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTable from '@/components/app/AppTable.vue';
import AppTabs from '@/components/app/AppTabs.vue';
import { confirmAction } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';
import { rupiah } from '@/lib/format';
import type { Period } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Type = { id: number; name: string; description: string | null; default_amount: string; is_recurring: boolean; is_active: boolean; display_order: number };
const props = defineProps<{
    types: Type[]; periods: Period[]; period: Period | null;
    configs: { id: number; name: string; description: string | null; default_amount: string; is_special: boolean; master_default: string | null }[];
    rows: { id: number; name: string; nip: string; status: string; deductions: { id: number; config_id: number; name: string; amount: string }[]; total: string; take_home: string }[];
    filters: { q: string; status: string }; needsReason: boolean; locked: boolean;
}>();

const tab = ref('period');
const f = useFilters('/admin/potongan', props.filters, () => ({ period: props.period?.id }));
const typeOpen = ref(false);
const editing = ref<Type | null>(null);
const typeForm = useForm({ name: '', description: '', default_amount: '0', is_recurring: true, is_active: true });
const cfgOpen = ref(false);
const cfgForm = useForm({ name: '', description: '', amount: '0' });

function newType(): void { editing.value = null; typeForm.reset(); typeForm.clearErrors(); typeOpen.value = true; }
function editType(t: Type): void { editing.value = t; typeForm.clearErrors(); Object.assign(typeForm, { name: t.name, description: t.description ?? '', default_amount: t.default_amount.replace(/\.00$/, ''), is_recurring: t.is_recurring, is_active: t.is_active }); typeOpen.value = true; }
function saveType(): void {
    const o = { preserveScroll: true, onSuccess: () => { typeOpen.value = false; } };
    editing.value ? typeForm.put(`/admin/potongan/jenis/${editing.value.id}`, o) : typeForm.post('/admin/potongan/jenis', o);
}
async function removeType(t: Type): Promise<void> {
    if (await confirmAction({ title: 'Hapus jenis potongan?', message: `"${t.name}" dihapus dari master. Jenis yang sudah dipakai pada periode tidak dapat dihapus.`, danger: true, confirmLabel: 'Hapus' })) router.delete(`/admin/potongan/jenis/${t.id}`, { preserveScroll: true });
}
function addCfg(): void { if (props.period) cfgForm.post(`/admin/potongan/periode/${props.period.id}/konfig`, { preserveScroll: true, onSuccess: () => { cfgOpen.value = false; cfgForm.reset(); } }); }
async function copyPrev(): Promise<void> {
    if (props.period && await confirmAction({ title: 'Salin dari periode sebelumnya?', message: 'Jenis dan nominal potongan periode sebelumnya akan menimpa nominal pada periode ini.', confirmLabel: 'Salin' })) router.post(`/admin/potongan/periode/${props.period.id}/salin`, {}, { preserveScroll: true });
}
const tcols = [{ key: 'name', label: 'Jenis potongan', primary: true }, { key: 'default_amount', label: 'Nominal default', align: 'right' as const }, { key: 'is_recurring', label: 'Bulanan otomatis' }, { key: 'is_active', label: 'Status' }];
const editable = () => !!props.period && !props.locked;
</script>

<template>
    <Head title="Potongan Sekolah" />
    <AppPageHeader title="Potongan Sekolah" subtitle="Pengaturan potongan sekolah per periode dan master jenis potongan." />
    <AppTabs v-model="tab" class="mb-4" :tabs="[{ value: 'period', label: 'Potongan per Periode' }, { value: 'master', label: 'Master Jenis Potongan' }]" />

    <template v-if="tab === 'period'">
        <div v-if="!period" class="rounded-xl border border-neutral-200 bg-white p-6 text-sm text-neutral-600">Belum ada periode.</div>
        <template v-else>
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <PeriodFilter :periods="periods" :current="period.id" path="/admin/potongan" />
                <template v-if="editable()"><AppButton variant="outline" @click="cfgOpen = true">Tambah potongan khusus</AppButton><AppButton v-if="['draft', 'review'].includes(period.status)" variant="outline" @click="copyPrev">Salin periode sebelumnya</AppButton></template>
                <AppBadge v-if="locked" tone="neutral">Periode ditutup — hanya lihat</AppBadge>
            </div>
            <div class="mb-3 grid gap-3 sm:grid-cols-2"><AppInput v-model="f.q" placeholder="Cari nama ASN…" inputmode="search" /><AppSelect v-model="f.status" placeholder="Semua status ASN" :options="[{ value: 'PNS', label: 'PNS' }, { value: 'PPPK', label: 'PPPK' }]" /></div>
            <DeductionEditor :configs="configs" :rows="rows" :needs-reason="needsReason" :locked="locked" :period-id="period.id" :editable="editable()" />
        </template>
    </template>

    <AppCard v-else flush>
        <template #header><p class="text-[13px] text-neutral-600">Perubahan master hanya berlaku untuk periode baru. Periode lama tidak berubah.</p><AppButton size="sm" @click="newType">Tambah jenis</AppButton></template>
        <AppTable :columns="tcols" :rows="types">
            <template #cell-default_amount="{ row }">{{ rupiah(row.default_amount) }}</template>
            <template #cell-is_recurring="{ row }">{{ row.is_recurring ? 'Ya' : 'Tidak (diisi manual)' }}</template>
            <template #cell-is_active="{ row }"><AppBadge :tone="row.is_active ? 'success' : 'neutral'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge></template>
            <template #actions="{ row }"><div class="flex"><AppButton size="sm" variant="ghost" @click="editType(row)">Ubah</AppButton><AppButton size="sm" variant="ghost" @click="removeType(row)">Hapus</AppButton></div></template>
        </AppTable>
    </AppCard>

    <AppDialog v-model:open="typeOpen" :title="editing ? 'Ubah Jenis Potongan' : 'Tambah Jenis Potongan'">
        <form id="type-form" class="space-y-3" @submit.prevent="saveType">
            <AppInput v-model="typeForm.name" label="Nama potongan" :error="typeForm.errors.name" required />
            <AppInput v-model="typeForm.description" label="Keterangan" :error="typeForm.errors.description" />
            <AppMoneyInput v-model="typeForm.default_amount" label="Nominal default" :error="typeForm.errors.default_amount" required />
            <AppCheckbox v-model="typeForm.is_recurring" label="Berlaku otomatis setiap bulan" hint="Bila tidak dicentang, nominal awal periode baru Rp0 dan diisi manual." />
            <AppCheckbox v-model="typeForm.is_active" label="Aktif" hint="Hanya jenis aktif yang dibawa ke periode baru." />
        </form>
        <template #footer><AppButton variant="outline" @click="typeOpen = false">Batal</AppButton><AppButton type="submit" form="type-form" :loading="typeForm.processing">Simpan</AppButton></template>
    </AppDialog>

    <AppDialog v-model:open="cfgOpen" title="Tambah Potongan Khusus Periode" description="Berlaku hanya untuk periode ini (mis. iuran insidental).">
        <form id="cfg-form" class="space-y-3" @submit.prevent="addCfg">
            <AppInput v-model="cfgForm.name" label="Nama potongan" :error="cfgForm.errors.name" required />
            <AppInput v-model="cfgForm.description" label="Keterangan" :error="cfgForm.errors.description" />
            <AppMoneyInput v-model="cfgForm.amount" label="Nominal per ASN" hint="Dapat diubah per ASN setelah ditambahkan." :error="cfgForm.errors.amount" required />
        </form>
        <template #footer><AppButton variant="outline" @click="cfgOpen = false">Batal</AppButton><AppButton type="submit" form="cfg-form" :loading="cfgForm.processing">Tambahkan</AppButton></template>
    </AppDialog>
</template>
