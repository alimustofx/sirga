<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppDisclosure from '@/components/app/AppDisclosure.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import AppMoneyInput from '@/components/app/AppMoneyInput.vue';
import AppPagination from '@/components/app/AppPagination.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTextarea from '@/components/app/AppTextarea.vue';
import { confirmAction } from '@/composables/useConfirm';
import { navigating } from '@/composables/useNavigating';
import { formatNip, rupiah } from '@/lib/format';
import type { Paginated } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { SlidersHorizontal } from '@lucide/vue';
import { computed, ref } from 'vue';

type Cfg = { id: number; name: string; description: string | null; default_amount: string; is_special: boolean; master_default: string | null };
type Row = { id: number; name: string; nip: string; status: string; deductions: { id: number; config_id: number; name: string; amount: string }[]; total: string; take_home: string };
const props = defineProps<{ configs: Cfg[]; rows: Paginated<Row>; perPage: number; configTotals: Record<string, string>; needsReason: boolean; locked: boolean; editable: boolean }>();

const massOpen = ref(false);
const massCfg = ref<Cfg | null>(null);
const mass = useForm({ amount: '', scope: 'all', reason: '' });
const rowOpen = ref(false);
const rowEdit = ref<Row | null>(null);
const rowForm = useForm<{ amounts: Record<number, string>; reason: string }>({ amounts: {}, reason: '' });

function openMass(c: Cfg): void { massCfg.value = c; mass.reset(); mass.amount = (c.master_default ?? c.default_amount).replace(/\.00$/, ''); mass.clearErrors(); massOpen.value = true; }
function applyMass(): void {
    if (massCfg.value) mass.post(`/admin/potongan/konfig/${massCfg.value.id}/massal`, { preserveScroll: true, onSuccess: () => { massOpen.value = false; } });
}
function openRow(r: Row): void { rowEdit.value = r; rowForm.reset(); rowForm.clearErrors(); rowForm.amounts = Object.fromEntries(r.deductions.map((d) => [d.id, d.amount.replace(/\.00$/, '')])); rowOpen.value = true; }
function saveRow(): void {
    if (rowEdit.value) rowForm.put(`/admin/potongan/rincian/${rowEdit.value.id}`, { preserveScroll: true, onSuccess: () => { rowOpen.value = false; } });
}
async function removeCfg(c: Cfg): Promise<void> {
    if (await confirmAction({ title: 'Hapus dari periode?', message: `"${c.name}" akan dihapus dari seluruh rincian periode ini.`, danger: true, confirmLabel: 'Hapus' })) router.delete(`/admin/potongan/konfig/${c.id}`, { preserveScroll: true });
}

const amountOf = (r: Row, cid: number) => r.deductions.find((d) => d.config_id === cid)?.amount ?? '0';
const grand = computed(() => Object.values(props.configTotals).reduce((a, b) => a + Number(b), 0));
const chips = computed(() => props.configs.slice(0, 5));
const th = 'sticky top-0 z-20 border-b border-neutral-200 bg-neutral-50 px-4 py-3 whitespace-nowrap';
</script>

<template>
    <div class="space-y-4">
        <!-- Jenis potongan: ringkas saat ditutup -->
        <AppDisclosure title="Jenis potongan periode ini" :count="configs.length" storage-key="ded-configs" :default-open="false">
            <template #summary>
                <span class="flex flex-wrap items-center gap-1.5">
                    <span v-for="c in chips" :key="c.id" class="num rounded-md bg-neutral-100 px-2 py-0.5 text-xs text-neutral-800"><b class="font-semibold">{{ c.name }}</b> {{ rupiah(c.default_amount) }}</span>
                    <span v-if="configs.length > chips.length" class="text-xs text-neutral-600">+{{ configs.length - chips.length }} lainnya</span>
                    <span v-if="!configs.length" class="text-xs text-neutral-600">Belum ada jenis potongan</span>
                </span>
            </template>
            <p v-if="!configs.length" class="px-5 py-6 text-center text-sm text-neutral-600">Belum ada jenis potongan. Tambahkan potongan khusus atau salin dari periode sebelumnya.</p>
            <ul v-else class="divide-y divide-neutral-100">
                <li v-for="c in configs" :key="c.id" class="grid items-center gap-x-4 gap-y-2 px-4 py-3 sm:px-5 md:grid-cols-[1fr_9rem_10rem_auto]">
                    <div class="min-w-0"><p class="truncate text-sm font-semibold text-neutral-950">{{ c.name }} <span v-if="c.is_special" class="ml-1 text-xs font-normal text-primary-600">khusus periode</span></p><p v-if="c.description" class="truncate text-xs text-neutral-600">{{ c.description }}</p></div>
                    <p class="num text-sm md:text-right"><span class="eyebrow mr-2 md:hidden">Default</span>{{ rupiah(c.default_amount) }}</p>
                    <p class="num text-sm md:text-right"><span class="eyebrow mr-2 md:block">Total periode</span>{{ rupiah(configTotals[c.id] ?? 0) }}</p>
                    <div v-if="editable" class="flex gap-1 md:justify-end"><AppButton size="sm" variant="outline" @click="openMass(c)">Terapkan nominal</AppButton><AppButton v-if="!locked" size="sm" variant="ghost" @click="removeCfg(c)">Hapus</AppButton></div>
                </li>
            </ul>
            <div v-if="configs.length" class="flex items-baseline justify-between border-t border-neutral-200 bg-neutral-50 px-4 py-3 sm:px-5"><span class="text-sm font-semibold text-primary-900">Total seluruh potongan sekolah</span><span class="num text-base font-bold text-primary-900">{{ rupiah(grand) }}</span></div>
        </AppDisclosure>

        <!-- Matriks per ASN -->
        <AppCard flush>
            <div class="border-b border-neutral-200 p-4"><slot name="toolbar" /></div>

            <div v-if="navigating.table" class="space-y-0 divide-y divide-neutral-100" role="status" aria-label="Memuat data">
                <div v-for="n in perPage > 10 ? 10 : perPage" :key="n" class="flex items-center gap-4 px-4 py-4"><div class="skeleton h-4 w-44 rounded" /><div v-for="m in Math.min(configs.length, 4)" :key="m" class="skeleton hidden h-4 w-20 rounded sm:block" /><div class="skeleton ml-auto h-4 w-24 rounded" /></div>
            </div>
            <AppEmptyState v-else-if="!rows.data.length" title="Tidak ada ASN pada tampilan ini" description="Ubah pencarian atau filter, atau impor/input penghasilan terlebih dahulu." />
            <div v-else class="thin-scroll max-h-[68vh] overflow-auto">
                <table class="min-w-full border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr>
                            <th :class="[th, 'left-0 z-30 min-w-[13rem] border-r text-left sm:min-w-[16rem]']"><span class="eyebrow">Nama ASN</span></th>
                            <th v-for="c in configs" :key="c.id" :class="[th, 'text-right']">
                                <span class="inline-flex items-center justify-end gap-1.5"><span class="eyebrow">{{ c.name }}</span>
                                    <button v-if="editable" type="button" class="flex size-6 items-center justify-center rounded-md text-neutral-400 hover:bg-neutral-200 hover:text-neutral-950" :title="`Terapkan nominal ${c.name} ke semua ASN`" :aria-label="`Terapkan nominal ${c.name}`" @click="openMass(c)"><SlidersHorizontal class="size-3.5" /></button></span>
                            </th>
                            <th :class="[th, 'text-right']"><span class="eyebrow">Total</span></th>
                            <th :class="[th, 'text-right']"><span class="eyebrow">Diterimakan</span></th>
                            <th v-if="editable" :class="[th, 'right-0 z-30 w-px border-l']" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in rows.data" :key="r.id" class="group">
                            <td class="sticky left-0 z-10 border-r border-b border-neutral-100 bg-white px-4 py-3 group-hover:bg-neutral-50">
                                <p class="truncate text-sm font-semibold text-primary-900">{{ r.name }}</p>
                                <p class="num text-xs text-neutral-600">{{ formatNip(r.nip) }} · {{ r.status }}</p>
                            </td>
                            <td v-for="c in configs" :key="c.id" :class="['num border-b border-neutral-100 px-4 py-3 text-right group-hover:bg-neutral-50', Number(amountOf(r, c.id)) === 0 ? 'text-neutral-400' : 'text-neutral-950']">{{ rupiah(amountOf(r, c.id)) }}</td>
                            <td class="num border-b border-neutral-100 px-4 py-3 text-right font-semibold group-hover:bg-neutral-50">{{ rupiah(r.total) }}</td>
                            <td class="num border-b border-neutral-100 px-4 py-3 text-right text-primary-900 group-hover:bg-neutral-50">{{ rupiah(r.take_home) }}</td>
                            <td v-if="editable" class="sticky right-0 z-10 border-b border-l border-neutral-100 bg-white px-3 py-2 group-hover:bg-neutral-50"><AppButton size="sm" variant="outline" @click="openRow(r)">Ubah</AppButton></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination :page="rows" :per-page="perPage" :per-page-options="[10, 15, 25, 50]" />
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
