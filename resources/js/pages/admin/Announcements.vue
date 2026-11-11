<script setup lang="ts">
import AppBadge from '@/components/app/AppBadge.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import AppTextarea from '@/components/app/AppTextarea.vue';
import { confirmAction } from '@/composables/useConfirm';
import { formatDate } from '@/lib/format';
import type { Period } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Item = { id: number; title: string; content: string; target_type: string; state: string; period_id: number | null; published_at: string | null; recipients: number[] };
const props = defineProps<{ items: Item[]; employees: { id: number; full_name: string; nip: string }[]; periods: Period[] }>();

const open = ref(false);
const editing = ref<Item | null>(null);
const search = ref('');
const form = useForm({ title: '', content: '', target_type: 'all', recipients: [] as number[], period_id: '', publish_mode: 'now', publish_at: '' });
const filtered = computed(() => props.employees.filter((e) => !search.value || e.full_name.toLowerCase().includes(search.value.toLowerCase()) || e.nip.includes(search.value)));
const stateTone = { draft: 'neutral', scheduled: 'info', published: 'success', archived: 'neutral' } as const;
const stateLabel = { draft: 'Draft', scheduled: 'Terjadwal', published: 'Terbit', archived: 'Diarsipkan' } as const;
const targetLabel = { all: 'Semua ASN', pns: 'PNS', pppk: 'PPPK', individual: 'Individu' } as Record<string, string>;

function create(): void { editing.value = null; form.reset(); form.clearErrors(); open.value = true; }
function edit(a: Item): void {
    editing.value = a; form.clearErrors();
    Object.assign(form, { title: a.title, content: a.content, target_type: a.target_type, recipients: [...a.recipients], period_id: a.period_id ? String(a.period_id) : '', publish_mode: a.state === 'draft' ? 'draft' : a.state === 'scheduled' ? 'schedule' : 'now', publish_at: a.state === 'scheduled' && a.published_at ? a.published_at.slice(0, 16) : '' });
    open.value = true;
}
function save(): void {
    const o = { preserveScroll: true, onSuccess: () => { open.value = false; } };
    editing.value ? form.put(`/admin/pengumuman/${editing.value.id}`, o) : form.post('/admin/pengumuman', o);
}
async function remove(a: Item): Promise<void> {
    if (await confirmAction({ title: 'Hapus pengumuman?', message: `"${a.title}" akan dihapus permanen. Gunakan arsip bila ingin menyimpan.`, danger: true, confirmLabel: 'Hapus' })) router.delete(`/admin/pengumuman/${a.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Pengumuman" />
    <AppPageHeader title="Pengumuman" subtitle="Informasi untuk guru. Pengumuman yang terbit langsung memberi notifikasi."><AppButton @click="create">Buat pengumuman</AppButton></AppPageHeader>

    <AppCard v-if="!items.length"><AppEmptyState title="Belum ada pengumuman" /></AppCard>
    <div v-else class="space-y-3">
        <article v-for="a in items" :key="a.id" class="rounded-xl border border-neutral-200 bg-white p-4 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0"><h3 class="text-base font-semibold text-primary-900">{{ a.title }}</h3><p class="text-xs text-neutral-600">Untuk {{ targetLabel[a.target_type] }} · {{ a.published_at ? formatDate(a.published_at, true) : 'belum diterbitkan' }}</p></div>
                <AppBadge :tone="stateTone[a.state as keyof typeof stateTone]">{{ stateLabel[a.state as keyof typeof stateLabel] }}</AppBadge>
            </div>
            <p class="mt-2 line-clamp-2 text-sm text-neutral-800">{{ a.content }}</p>
            <div class="mt-3 flex flex-wrap gap-2"><AppButton size="sm" variant="outline" @click="edit(a)">Ubah</AppButton><AppButton size="sm" variant="ghost" @click="router.post(`/admin/pengumuman/${a.id}/arsip`, {}, { preserveScroll: true })">{{ a.state === 'archived' ? 'Aktifkan' : 'Arsipkan' }}</AppButton><AppButton size="sm" variant="ghost" @click="remove(a)">Hapus</AppButton></div>
        </article>
    </div>

    <AppDialog v-model:open="open" :title="editing ? 'Ubah Pengumuman' : 'Buat Pengumuman'" size="lg">
        <form id="ann-form" class="space-y-3" @submit.prevent="save">
            <AppInput v-model="form.title" label="Judul" :error="form.errors.title" required />
            <AppTextarea v-model="form.content" label="Isi pengumuman" :rows="5" :error="form.errors.content" required />
            <div class="grid gap-3 sm:grid-cols-2">
                <AppSelect v-model="form.target_type" label="Ditujukan kepada" :options="[{ value: 'all', label: 'Semua ASN' }, { value: 'pns', label: 'PNS' }, { value: 'pppk', label: 'PPPK' }, { value: 'individual', label: 'ASN tertentu' }]" />
                <AppSelect v-model="form.period_id" label="Periode terkait (opsional)" placeholder="Tidak terkait" :options="periods.map((p) => ({ value: p.id, label: p.label }))" />
            </div>
            <div v-if="form.target_type === 'individual'" class="rounded-lg border border-neutral-200 p-3">
                <AppInput v-model="search" placeholder="Cari ASN…" inputmode="search" />
                <ul class="mt-2 max-h-44 divide-y divide-neutral-100 overflow-y-auto"><li v-for="e in filtered" :key="e.id"><label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm"><input v-model="form.recipients" type="checkbox" :value="e.id" class="size-5 accent-primary-700" />{{ e.full_name }}</label></li></ul>
                <p class="mt-1 text-[13px] text-neutral-600">{{ form.recipients.length }} dipilih</p>
                <p v-if="form.errors.recipients" class="text-[13px] text-danger-fg">{{ form.errors.recipients }}</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <AppSelect v-model="form.publish_mode" label="Penerbitan" :options="[{ value: 'now', label: 'Terbitkan sekarang' }, { value: 'schedule', label: 'Jadwalkan' }, { value: 'draft', label: 'Simpan sebagai draft' }]" />
                <AppInput v-if="form.publish_mode === 'schedule'" v-model="form.publish_at" label="Tanggal & jam terbit" type="datetime-local" :error="form.errors.publish_at" />
            </div>
        </form>
        <template #footer><AppButton variant="outline" @click="open = false">Batal</AppButton><AppButton type="submit" form="ann-form" :loading="form.processing">Simpan</AppButton></template>
    </AppDialog>
</template>
