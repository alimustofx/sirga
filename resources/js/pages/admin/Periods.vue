<script setup lang="ts">
import PayrollPeriodCard from '@/components/admin/PayrollPeriodCard.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppDialog from '@/components/app/AppDialog.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppSelect from '@/components/app/AppSelect.vue';
import { months } from '@/lib/format';
import type { Period } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type P = Period & { records: number; employees: number; completeness: number; published_at: string | null };
const props = defineProps<{ periods: P[]; defaults: { month: number; year: number } }>();

const open = ref(false);
const source = ref<P | null>(null);
const form = useForm({ month: props.defaults.month, year: props.defaults.year, notes: '' });

function create(): void { source.value = null; open.value = true; }
function duplicate(p: P): void { source.value = p; const next = p.month === 12 ? { m: 1, y: p.year + 1 } : { m: p.month + 1, y: p.year }; form.month = next.m; form.year = next.y; open.value = true; }
function save(): void {
    const url = source.value ? `/admin/periode/${source.value.id}/duplikasi` : '/admin/periode';
    form.post(url, { onSuccess: () => { open.value = false; } });
}
</script>

<template>
    <Head title="Periode Gaji" />
    <AppPageHeader title="Periode Gaji" subtitle="Siklus: Draft → Review → Published → Closed."><AppButton @click="create">Buat periode</AppButton></AppPageHeader>

    <AppCard v-if="!periods.length"><AppEmptyState title="Belum ada periode gaji" description="Buat periode pertama, lalu impor file Excel penggajian."><AppButton @click="create">Buat periode</AppButton></AppEmptyState></AppCard>
    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"><PayrollPeriodCard v-for="p in periods" :key="p.id" :period="p" @duplicate="duplicate(p)" /></div>

    <AppDialog v-model:open="open" :title="source ? `Gandakan ${source.label}` : 'Buat Periode Baru'" :description="source ? 'Jenis potongan dan nominal disalin sebagai bahan awal. Seluruh rincian wajib diverifikasi ulang sebelum publikasi. Data pembayaran tidak disalin.' : undefined">
        <form id="period-form" class="grid gap-3 sm:grid-cols-2" @submit.prevent="save">
            <AppSelect v-model="form.month" label="Bulan" :options="months" :error="form.errors.month" required />
            <AppInput v-model="form.year" label="Tahun" type="number" :error="form.errors.year" required />
            <AppInput v-if="!source" v-model="form.notes" label="Catatan (opsional)" class="sm:col-span-2" :error="form.errors.notes" />
        </form>
        <template #footer><AppButton variant="outline" @click="open = false">Batal</AppButton><AppButton type="submit" form="period-form" :loading="form.processing">{{ source ? 'Gandakan' : 'Buat periode' }}</AppButton></template>
    </AppDialog>
</template>
