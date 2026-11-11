<script setup lang="ts">
import AppBadge from '@/components/app/AppBadge.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppCheckbox from '@/components/app/AppCheckbox.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTable from '@/components/app/AppTable.vue';
import { confirmAction } from '@/composables/useConfirm';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Comp = { id: number; name: string; type: string; type_label: string; applicable_status: string; display_order: number; is_active: boolean; usage: number };
defineProps<{ components: Comp[] }>();

const open = ref(false);
const editing = ref<Comp | null>(null);
const form = useForm({ name: '', component_type: 'earning', applicable_status: 'all', display_order: 0, is_active: true });
const columns = [
    { key: 'name', label: 'Nama komponen', primary: true },
    { key: 'type_label', label: 'Jenis' },
    { key: 'applicable_status', label: 'Berlaku untuk' },
    { key: 'display_order', label: 'Urutan', align: 'right' as const },
    { key: 'is_active', label: 'Status' },
];
function create(): void { editing.value = null; form.reset(); form.clearErrors(); open.value = true; }
function edit(c: Comp): void { editing.value = c; form.clearErrors(); Object.assign(form, { name: c.name, component_type: c.type, applicable_status: c.applicable_status, display_order: c.display_order, is_active: c.is_active }); open.value = true; }
function save(): void {
    const o = { preserveScroll: true, onSuccess: () => { open.value = false; } };
    editing.value ? form.put(`/admin/komponen/${editing.value.id}`, o) : form.post('/admin/komponen', o);
}
async function remove(c: Comp): Promise<void> {
    if (await confirmAction({ title: 'Hapus komponen?', message: `"${c.name}" akan dihapus. Komponen yang sudah dipakai tidak dapat dihapus (nonaktifkan saja).`, danger: true, confirmLabel: 'Hapus' })) router.delete(`/admin/komponen/${c.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Komponen Gaji" />
    <AppPageHeader title="Komponen Gaji" subtitle="Komponen penghasilan dan potongan resmi. Perubahan nama tidak mengubah data gaji periode lama.">
        <AppButton @click="create">Tambah komponen</AppButton>
    </AppPageHeader>
    <AppCard flush>
        <AppTable :columns="columns" :rows="components">
            <template #cell-applicable_status="{ row }">{{ row.applicable_status === 'all' ? 'PNS & PPPK' : row.applicable_status }}</template>
            <template #cell-is_active="{ row }"><AppBadge :tone="row.is_active ? 'success' : 'neutral'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge></template>
            <template #actions="{ row }"><div class="flex gap-1"><AppButton size="sm" variant="ghost" @click="edit(row)">Ubah</AppButton><AppButton v-if="!row.usage" size="sm" variant="ghost" @click="remove(row)">Hapus</AppButton></div></template>
        </AppTable>
    </AppCard>

    <AppDialog v-model:open="open" :title="editing ? 'Ubah Komponen' : 'Tambah Komponen'">
        <form id="comp-form" class="space-y-3" @submit.prevent="save">
            <AppInput v-model="form.name" label="Nama komponen" :error="form.errors.name" required />
            <AppSelect v-model="form.component_type" label="Jenis" :disabled="!!editing" :options="[{ value: 'earning', label: 'Penghasilan' }, { value: 'official_deduction', label: 'Potongan Resmi' }]" />
            <AppSelect v-model="form.applicable_status" label="Berlaku untuk" :options="[{ value: 'all', label: 'PNS & PPPK' }, { value: 'PNS', label: 'PNS saja' }, { value: 'PPPK', label: 'PPPK saja' }]" />
            <AppInput v-model="form.display_order" label="Urutan tampil" type="number" :error="form.errors.display_order" />
            <AppCheckbox v-model="form.is_active" label="Komponen aktif" hint="Komponen nonaktif tidak muncul pada input manual baru." />
        </form>
        <template #footer><AppButton variant="outline" @click="open = false">Batal</AppButton><AppButton type="submit" form="comp-form" :loading="form.processing">Simpan</AppButton></template>
    </AppDialog>
</template>
