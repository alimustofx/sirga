<script setup lang="ts">
import AppBadge from '@/components/app/AppBadge.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppMoneyInput from '@/components/app/AppMoneyInput.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTextarea from '@/components/app/AppTextarea.vue';
import PayrollStatusBadge from '@/components/payroll/PayrollStatusBadge.vue';
import { confirmAction } from '@/composables/useConfirm';
import { formatDate, formatNip, rupiah } from '@/lib/format';
import type { Period } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { computed, ref } from 'vue';

type Item = { id: number | null; component_id: number | null; name: string; type: 'earning' | 'official_deduction'; amount: string; source: string | null };
const props = defineProps<{
    record: {
        id: number; period: Period; employee: { name: string; nip: string; status: string }; validation: string; validation_label: string; note: string | null;
        source: { gross: string | null; official: string | null; net: string | null };
        totals: { gross: string; official: string; net: string; school: string; take_home: string }; locked: boolean; needs_reason: boolean;
    };
    items: Item[];
    deductions: { id: number; name: string; amount: string }[];
    available: { id: number; name: string; type: 'earning' | 'official_deduction' }[];
    revisions: { version: number; reason: string | null; by: string | null; at: string; take_home: string | null }[];
}>();

const items = ref<Item[]>(props.items.map((i) => ({ ...i })));
const deds = ref(props.deductions.map((d) => ({ ...d })));
const addId = ref('');
const reason = ref('');
const note = ref('');
const verifyOpen = ref(false);
const form = useForm({ reason: '' });

const cents = (v: string) => Math.round((parseFloat(v || '0') || 0) * 100);
const sum = (t: Item['type']) => items.value.filter((i) => i.type === t).reduce((a, i) => a + cents(i.amount), 0);
const preview = computed(() => {
    const gross = sum('earning'), off = sum('official_deduction'), school = deds.value.reduce((a, d) => a + cents(d.amount), 0);
    return { gross: gross / 100, off: off / 100, net: (gross - off) / 100, school: school / 100, take: (gross - off - school) / 100 };
});
const available = computed(() => props.available.filter((a) => !items.value.some((i) => i.component_id === a.id)));

function add(): void {
    const c = props.available.find((a) => String(a.id) === addId.value);
    if (c) items.value.push({ id: null, component_id: c.id, name: c.name, type: c.type, amount: '0', source: null });
    addId.value = '';
}
async function save(): Promise<void> {
    if (props.record.needs_reason && !reason.value.trim()) { form.setError('reason', 'Alasan perubahan wajib diisi untuk koreksi setelah publikasi.'); return; }
    if (props.record.needs_reason && !(await confirmAction({ title: 'Simpan koreksi setelah publikasi?', message: 'Perubahan akan langsung terlihat oleh guru dan tercatat pada audit log.', confirmLabel: 'Simpan koreksi' }))) return;
    form.transform(() => ({
        items: items.value.map((i) => ({ id: i.id, component_id: i.component_id, type: i.type, amount: i.amount || '0' })),
        deductions: Object.fromEntries(deds.value.map((d) => [d.id, d.amount || '0'])),
        reason: reason.value,
    })).put(`/admin/penghasilan/${props.record.id}`, { preserveScroll: true });
}
function verify(): void { router.post(`/admin/penghasilan/${props.record.id}/verifikasi`, { note: note.value }, { preserveScroll: true, onSuccess: () => { verifyOpen.value = false; } }); }
const errors = computed(() => Object.values(form.errors).filter(Boolean) as string[]);
const needsCheck = computed(() => ['mismatch', 'copied'].includes(props.record.validation));
</script>

<template>
    <Head :title="`Rincian ${record.employee.name}`" />
    <AppButton variant="ghost" size="sm" href="/admin/penghasilan" class="mb-2 -ml-2">← Daftar penghasilan</AppButton>
    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">{{ record.employee.name }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-neutral-600"><span class="num">{{ formatNip(record.employee.nip) }}</span> · {{ record.employee.status }} · {{ record.period.label }} <PayrollStatusBadge :status="record.period.status" /></p>
        </div>
        <AppBadge :tone="({ valid: 'success', mismatch: 'warning', copied: 'info', invalid: 'danger' } as const)[record.validation as 'valid']">{{ record.validation_label }}</AppBadge>
    </div>

    <div v-if="needsCheck && !record.locked" class="mb-4 rounded-xl border border-[#fcd34d] bg-warning-bg p-4 text-sm text-warning-fg">
        <p class="font-semibold">{{ record.validation === 'mismatch' ? 'Terdapat selisih dengan file sumber.' : 'Rincian hasil duplikasi periode — perlu diverifikasi.' }}</p>
        <p v-if="record.note" class="mt-0.5">{{ record.note }}</p>
        <ul v-if="record.validation === 'mismatch'" class="mt-2 space-y-0.5">
            <li v-if="record.source.gross">Gaji kotor: file {{ rupiah(record.source.gross) }} · sistem {{ rupiah(record.totals.gross) }}</li>
            <li v-if="record.source.official">Potongan resmi: file {{ rupiah(record.source.official) }} · sistem {{ rupiah(record.totals.official) }}</li>
            <li v-if="record.source.net">Gaji bersih: file {{ rupiah(record.source.net) }} · sistem {{ rupiah(record.totals.net) }}</li>
        </ul>
        <AppButton class="mt-3" size="sm" variant="outline" @click="verifyOpen = true">Tandai sudah diperiksa</AppButton>
    </div>
    <div v-if="record.locked" class="mb-4 rounded-xl border border-neutral-200 bg-neutral-100 p-4 text-sm">Periode sudah ditutup. Data terkunci dan hanya dapat dilihat.</div>
    <div v-if="errors.length" class="mb-4 rounded-xl border border-[#fca5a5] bg-danger-bg p-4 text-sm text-danger-fg" role="alert"><p v-for="e in errors" :key="e">{{ e }}</p></div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <template v-for="grp in [{ t: 'earning', title: 'Penghasilan' }, { t: 'official_deduction', title: 'Potongan Resmi' }] as const" :key="grp.t">
                <AppCard :title="grp.title">
                    <div class="space-y-2.5">
                        <div v-for="it in items.filter((i) => i.type === grp.t)" :key="it.component_id ?? it.id ?? it.name" class="grid grid-cols-[1fr_auto] items-start gap-2 sm:grid-cols-[1fr_12rem_auto]">
                            <div class="self-center"><p class="text-sm font-medium">{{ it.name }}</p><p v-if="it.source !== null && it.source !== it.amount" class="text-xs text-warning-fg">Nilai file sumber: {{ rupiah(it.source) }}</p></div>
                            <AppMoneyInput v-model="it.amount" :disabled="record.locked" class="col-span-2 row-start-2 sm:col-span-1 sm:row-start-auto" />
                            <button v-if="!record.locked" type="button" class="flex size-10 items-center justify-center rounded-lg text-neutral-600 hover:bg-neutral-100" :aria-label="`Hapus ${it.name}`" @click="items.splice(items.indexOf(it), 1)"><X class="size-4" /></button>
                        </div>
                    </div>
                </AppCard>
            </template>
            <div v-if="!record.locked && available.length" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <AppSelect v-model="addId" label="Tambah komponen lain" placeholder="Pilih komponen…" :options="available.map((a) => ({ value: a.id, label: `${a.name} (${a.type === 'earning' ? 'Penghasilan' : 'Potongan resmi'})` }))" class="flex-1" />
                <AppButton variant="outline" :disabled="!addId" @click="add">Tambah</AppButton>
            </div>
            <AppCard title="Potongan Sekolah">
                <div class="space-y-2.5">
                    <div v-for="d in deds" :key="d.id" class="grid grid-cols-1 items-center gap-1 sm:grid-cols-[1fr_12rem]"><p class="text-sm font-medium">{{ d.name }}</p><AppMoneyInput v-model="d.amount" :disabled="record.locked" /></div>
                    <p v-if="!deds.length" class="text-sm text-neutral-600">Belum ada jenis potongan sekolah pada periode ini.</p>
                </div>
            </AppCard>
        </div>

        <div class="space-y-4">
            <AppCard title="Hasil Perhitungan">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-neutral-600">Gaji Kotor</dt><dd class="num font-medium">{{ rupiah(preview.gross) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-600">Potongan Resmi</dt><dd class="num font-medium">− {{ rupiah(preview.off) }}</dd></div>
                    <div class="flex justify-between border-t border-neutral-200 pt-2"><dt class="font-semibold">Penghasilan Bersih</dt><dd class="num font-bold">{{ rupiah(preview.net) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-600">Potongan Sekolah</dt><dd class="num font-medium">− {{ rupiah(preview.school) }}</dd></div>
                    <div class="flex justify-between border-t border-neutral-200 pt-2 text-primary-900"><dt class="font-semibold">Diterimakan</dt><dd :class="['num text-lg font-bold', preview.take < 0 ? 'text-danger-fg' : '']">{{ rupiah(preview.take) }}</dd></div>
                </dl>
                <p v-if="preview.take < 0 || preview.net < 0" class="mt-3 rounded-lg bg-danger-bg p-2.5 text-[13px] text-danger-fg">Hasil negatif tidak dapat disimpan.</p>
                <p class="mt-3 text-xs text-neutral-600">Pratinjau di layar. Angka resmi dihitung ulang server saat disimpan.</p>
            </AppCard>
            <AppCard v-if="revisions.length" title="Riwayat Revisi" flush>
                <ul class="divide-y divide-neutral-100 text-sm">
                    <li v-for="r in revisions" :key="r.version" class="px-4 py-2.5"><p class="font-medium">Versi {{ r.version }} <span class="font-normal text-neutral-600">(sebelum perubahan)</span></p><p class="text-xs text-neutral-600">{{ r.by ?? 'Sistem' }} · {{ formatDate(r.at, true) }}<template v-if="r.take_home"> · diterimakan {{ rupiah(r.take_home) }}</template></p><p v-if="r.reason" class="mt-0.5 text-[13px]">{{ r.reason }}</p></li>
                </ul>
            </AppCard>
        </div>
    </div>

    <div v-if="!record.locked" class="sticky bottom-0 z-20 -mx-4 mt-6 border-t border-neutral-200 bg-white px-4 py-3 sm:-mx-6 sm:px-6">
        <div class="mx-auto flex max-w-[88rem] flex-col gap-3 md:flex-row md:items-end">
            <div class="md:w-56"><p class="eyebrow">Diterimakan (pratinjau)</p><p :class="['num text-xl font-bold', preview.take < 0 ? 'text-danger-fg' : 'text-primary-900']">{{ rupiah(preview.take) }}</p></div>
            <div class="flex-1"><AppInput v-model="reason" :label="record.needs_reason ? 'Alasan perubahan (wajib)' : 'Alasan perubahan (opsional)'" :required="record.needs_reason" :error="form.errors.reason" placeholder="Mis. koreksi tunjangan keluarga" /></div>
            <div class="flex gap-2"><AppButton variant="outline" href="/admin/penghasilan">Batal</AppButton><AppButton :loading="form.processing" :disabled="preview.take < 0 || preview.net < 0" @click="save">Simpan rincian</AppButton></div>
        </div>
    </div>

    <AppDialog v-model:open="verifyOpen" title="Tandai sudah diperiksa" description="Pastikan nominal sudah benar. Catatan akan tersimpan pada audit log.">
        <AppTextarea v-model="note" label="Catatan (opsional)" :rows="3" />
        <template #footer><AppButton variant="outline" @click="verifyOpen = false">Batal</AppButton><AppButton @click="verify">Tandai valid</AppButton></template>
    </AppDialog>
</template>
