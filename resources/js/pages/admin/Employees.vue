<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppBadge from '@/components/app/AppBadge.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppDropdown from '@/components/app/AppDropdown.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppPagination from '@/components/app/AppPagination.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTable from '@/components/app/AppTable.vue';
import { confirmAction } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';
import { formatDate, formatNip } from '@/lib/format';
import type { Paginated } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { MoreHorizontal } from '@lucide/vue';
import { ref } from 'vue';

type Emp = {
    id: number; nip: string; full_name: string; status: string; rank: string | null; position: string | null; unit: string | null; account: string | null;
    npwp: string | null; is_active: boolean; email: string | null; role: string | null; role_label: string | null; has_account: boolean; last_login_at: string | null;
};
const props = defineProps<{ employees: Paginated<Emp>; filters: { q: string; status: string; active: string }; canManage: boolean; roles: { name: string; label: string }[] }>();

const f = useFilters('/admin/asn', props.filters);
const open = ref(false);
const bulkOpen = ref(false);
const editing = ref<Emp | null>(null);

const form = useForm({ nip: '', full_name: '', employment_status: 'PNS', rank: '', position: '', unit: '', bank_account_number: '', npwp: '', email: '', role: 'guru', password: '' });
const bulk = useForm<{ file: File | null; employment_status: string }>({ file: null, employment_status: 'PNS' });

function create(): void { editing.value = null; form.reset(); form.clearErrors(); open.value = true; }
function edit(e: Emp): void {
    editing.value = e; form.clearErrors();
    Object.assign(form, { nip: e.nip, full_name: e.full_name, employment_status: e.status, rank: e.rank ?? '', position: e.position ?? '', unit: e.unit ?? '', bank_account_number: e.account ?? '', npwp: e.npwp ?? '', email: e.email ?? '', role: e.role ?? 'guru', password: '' });
    open.value = true;
}
function save(): void {
    const opts = { preserveScroll: true, onSuccess: () => { open.value = false; } };
    editing.value ? form.put(`/admin/asn/${editing.value.id}`, opts) : form.post('/admin/asn', opts);
}
async function toggle(e: Emp): Promise<void> {
    if (await confirmAction({ title: e.is_active ? 'Nonaktifkan akun?' : 'Aktifkan akun?', message: e.is_active ? `${e.full_name} tidak akan bisa login dan tidak dihitung pada periode baru. Riwayat gaji tetap tersimpan.` : `${e.full_name} dapat login kembali.`, danger: e.is_active, confirmLabel: e.is_active ? 'Nonaktifkan' : 'Aktifkan' })) router.post(`/admin/asn/${e.id}/status`, {}, { preserveScroll: true });
}
async function reset(e: Emp): Promise<void> {
    if (await confirmAction({ title: 'Reset password?', message: `Password baru acak akan dibuat untuk ${e.full_name} dan wajib diganti saat login berikutnya.`, confirmLabel: 'Reset password' })) router.post(`/admin/asn/${e.id}/reset-password`, {}, { preserveScroll: true });
}
async function remove(e: Emp): Promise<void> {
    if (await confirmAction({ title: 'Hapus data ASN?', message: `${e.full_name} akan dihapus permanen. Hanya bisa jika belum punya riwayat gaji.`, danger: true, confirmLabel: 'Hapus' })) router.delete(`/admin/asn/${e.id}`, { preserveScroll: true });
}
function pickFile(ev: Event): void { bulk.file = (ev.target as HTMLInputElement).files?.[0] ?? null; }
const importBulk = () => bulk.post('/admin/asn/impor-akun', { forceFormData: true, onSuccess: () => { bulkOpen.value = false; bulk.reset(); } });

const columns = [
    { key: 'full_name', label: 'Nama', primary: true },
    { key: 'nip', label: 'NIP' },
    { key: 'status', label: 'Status' },
    { key: 'rank', label: 'Golongan' },
    { key: 'last_login_at', label: 'Login terakhir', mobile: false },
    { key: 'is_active', label: 'Akun' },
];
const statusOpts = [{ value: 'PNS', label: 'PNS' }, { value: 'PPPK', label: 'PPPK' }];
</script>

<template>
    <Head title="Data ASN" />
    <AppPageHeader title="Data ASN" subtitle="Data pegawai dan akun login. Akun tidak dibuat otomatis oleh impor gaji.">
        <template v-if="canManage"><AppButton variant="outline" @click="bulkOpen = true">Buat akun dari Excel</AppButton><AppButton @click="create">Tambah ASN</AppButton></template>
    </AppPageHeader>

    <AppCard flush>
        <div class="grid gap-3 border-b border-neutral-200 p-4 sm:grid-cols-3">
            <AppInput v-model="f.q" placeholder="Cari nama atau NIP…" inputmode="search" />
            <AppSelect v-model="f.status" placeholder="Semua status ASN" :options="statusOpts" />
            <AppSelect v-model="f.active" placeholder="Semua akun" :options="[{ value: '1', label: 'Aktif' }, { value: '0', label: 'Nonaktif' }]" />
        </div>
        <AppTable :columns="columns" :rows="employees.data" empty-title="Tidak ada data ASN" empty-text="Ubah filter pencarian atau tambahkan ASN baru.">
            <template #cell-full_name="{ row }"><span class="font-semibold text-primary-900">{{ row.full_name }}</span><span v-if="row.role && row.role !== 'guru'" class="ml-2 text-xs text-neutral-600">({{ row.role_label }})</span></template>
            <template #cell-nip="{ row }"><span class="num">{{ formatNip(row.nip) }}</span></template>
            <template #cell-last_login_at="{ row }">{{ row.last_login_at ? formatDate(row.last_login_at, true) : 'Belum pernah' }}</template>
            <template #cell-is_active="{ row }"><AppBadge :tone="row.is_active ? 'success' : 'neutral'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge></template>
            <template v-if="canManage" #actions="{ row }">
                <AppDropdown :items="[{ label: 'Ubah data', action: () => edit(row) }, { label: 'Reset password', action: () => reset(row), hidden: !row.has_account }, { label: row.is_active ? 'Nonaktifkan' : 'Aktifkan', action: () => toggle(row), danger: row.is_active }, { label: 'Hapus', action: () => remove(row), danger: true }]">
                    <template #trigger><button type="button" class="flex size-10 items-center justify-center rounded-lg hover:bg-neutral-100" aria-label="Aksi"><MoreHorizontal class="size-5" /></button></template>
                </AppDropdown>
            </template>
        </AppTable>
        <AppPagination :page="employees" />
    </AppCard>

    <AppDialog v-model:open="open" :title="editing ? 'Ubah Data ASN' : 'Tambah ASN'" :description="editing ? undefined : 'Password awal acak akan dibuat dan ditampilkan sekali.'" size="lg">
        <form id="emp-form" class="grid gap-3 sm:grid-cols-2" @submit.prevent="save">
            <AppInput v-model="form.nip" label="NIP" inputmode="numeric" :error="form.errors.nip" required class="sm:col-span-2" />
            <AppInput v-model="form.full_name" label="Nama lengkap (beserta gelar)" :error="form.errors.full_name" required class="sm:col-span-2" />
            <AppSelect v-model="form.employment_status" label="Status ASN" :options="statusOpts" :error="form.errors.employment_status" required />
            <AppInput v-model="form.rank" label="Golongan / Ruang" :error="form.errors.rank" />
            <AppInput v-model="form.position" label="Jabatan" :error="form.errors.position" />
            <AppInput v-model="form.unit" label="Unit kerja" :error="form.errors.unit" />
            <AppInput v-model="form.bank_account_number" label="Nomor rekening" inputmode="numeric" :error="form.errors.bank_account_number" />
            <AppInput v-model="form.npwp" label="NPWP" :error="form.errors.npwp" />
            <AppInput v-model="form.email" label="Email (opsional)" type="email" :error="form.errors.email" />
            <AppSelect v-model="form.role" label="Role" :options="roles.map((r) => ({ value: r.name, label: r.label }))" :error="form.errors.role" required />
            <AppInput v-if="!editing" v-model="form.password" label="Password awal (opsional)" type="password" hint="Kosongkan untuk membuat password acak." :error="form.errors.password" class="sm:col-span-2" />
        </form>
        <template #footer>
            <AppButton variant="outline" @click="open = false">Batal</AppButton>
            <AppButton type="submit" form="emp-form" :loading="form.processing">Simpan</AppButton>
        </template>
    </AppDialog>

    <AppDialog v-model:open="bulkOpen" title="Buat Akun dari Excel" description="Gunakan file Excel penggajian (format yang sama dengan impor gaji). Hanya NIP yang belum terdaftar yang dibuat." >
        <form id="bulk-form" class="space-y-3" @submit.prevent="importBulk">
            <AppSelect v-model="bulk.employment_status" label="Status ASN pada file" :options="statusOpts" required />
            <div><label for="bulk-file" class="mb-1.5 block text-[13px] font-semibold">File Excel (.xls / .xlsx)</label><input id="bulk-file" type="file" accept=".xls,.xlsx" class="block w-full rounded-lg border border-neutral-200 p-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:font-semibold" @change="pickFile" /><p v-if="bulk.errors.file" class="mt-1 text-[13px] text-danger-fg">{{ bulk.errors.file }}</p></div>
        </form>
        <template #footer>
            <AppButton variant="outline" @click="bulkOpen = false">Batal</AppButton>
            <AppButton type="submit" form="bulk-form" :disabled="!bulk.file" :loading="bulk.processing">Buat Akun</AppButton>
        </template>
    </AppDialog>
</template>
