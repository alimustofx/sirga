<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppCheckbox from '@/components/app/AppCheckbox.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppTabs from '@/components/app/AppTabs.vue';
import AppTextarea from '@/components/app/AppTextarea.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    school: { school_name: string; school_address: string | null; has_logo: boolean; has_letterhead: boolean };
    document: { treasurer_name: string | null; treasurer_nip: string | null; has_qr: boolean; show_zero_deductions: boolean; default_bank_information: string | null };
    account: { bank_name: string | null; account_number: string | null; account_holder: string | null; payment_instruction: string | null };
    isSuper: boolean;
}>();

const tab = ref(props.isSuper ? 'school' : 'account');
const tabs = computed(() => [...(props.isSuper ? [{ value: 'school', label: 'Identitas Sekolah' }, { value: 'document', label: 'Dokumen Slip' }] : []), { value: 'account', label: 'Rekening Bendahara' }]);

const school = useForm<{ school_name: string; school_address: string; logo: File | null; letterhead: File | null }>({ school_name: props.school.school_name, school_address: props.school.school_address ?? '', logo: null, letterhead: null });
const doc = useForm<{ treasurer_name: string; treasurer_nip: string; show_zero_deductions: boolean; default_bank_information: string; qr: File | null }>({ treasurer_name: props.document.treasurer_name ?? '', treasurer_nip: props.document.treasurer_nip ?? '', show_zero_deductions: props.document.show_zero_deductions, default_bank_information: props.document.default_bank_information ?? '', qr: null });
const acc = useForm({ bank_name: props.account.bank_name ?? '', account_number: props.account.account_number ?? '', account_holder: props.account.account_holder ?? '', payment_instruction: props.account.payment_instruction ?? '' });
const file = (e: Event) => (e.target as HTMLInputElement).files?.[0] ?? null;
</script>

<template>
    <Head title="Pengaturan" />
    <AppPageHeader title="Pengaturan" subtitle="Identitas sekolah, tampilan slip, dan rekening pembayaran." />
    <AppTabs v-model="tab" class="mb-4" :tabs="tabs" />

    <AppCard v-if="tab === 'school'" title="Identitas Sekolah">
        <form class="max-w-xl space-y-3" @submit.prevent="school.post('/admin/pengaturan/sekolah', { forceFormData: true, preserveScroll: true })">
            <AppInput v-model="school.school_name" label="Nama sekolah" :error="school.errors.school_name" required />
            <AppTextarea v-model="school.school_address" label="Alamat" :rows="2" :error="school.errors.school_address" />
            <div><label for="logo" class="mb-1.5 block text-[13px] font-semibold">Logo sekolah (PNG/JPG, maks. 2 MB)</label><div class="flex items-center gap-3"><img v-if="props.school.has_logo" src="/admin/aset/logo" alt="Logo saat ini" class="size-12 rounded-lg border border-neutral-200 object-contain" /><input id="logo" type="file" accept="image/png,image/jpeg" class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-2 file:font-semibold" @change="school.logo = file($event)" /></div><p v-if="school.errors.logo" class="mt-1 text-[13px] text-danger-fg">{{ school.errors.logo }}</p></div>
            <AppButton type="submit" :loading="school.processing">Simpan identitas</AppButton>
        </form>
    </AppCard>

    <AppCard v-else-if="tab === 'document'" title="Konfigurasi Dokumen Slip Gaji">
        <form class="max-w-xl space-y-3" @submit.prevent="doc.post('/admin/pengaturan/dokumen', { forceFormData: true, preserveScroll: true })">
            <AppInput v-model="doc.treasurer_name" label="Nama bendahara" :error="doc.errors.treasurer_name" />
            <AppInput v-model="doc.treasurer_nip" label="NIP bendahara" inputmode="numeric" :error="doc.errors.treasurer_nip" />
            <div><label for="qr" class="mb-1.5 block text-[13px] font-semibold">QR / tanda tangan visual (PNG/JPG, maks. 1 MB)</label><div class="flex items-center gap-3"><img v-if="props.document.has_qr" src="/admin/aset/qr" alt="QR saat ini" class="size-12 rounded-lg border border-neutral-200 object-contain" /><input id="qr" type="file" accept="image/png,image/jpeg" class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-2 file:font-semibold" @change="doc.qr = file($event)" /></div><p class="mt-1 text-xs text-neutral-600">Bukan tanda tangan elektronik tersertifikasi; slip memuat catatan tersebut.</p></div>
            <AppCheckbox v-model="doc.show_zero_deductions" label="Tampilkan potongan sekolah bernilai Rp0" hint="Berlaku pada rincian gaji dan slip PDF." />
            <AppTextarea v-model="doc.default_bank_information" label="Informasi bank bawaan (catatan internal)" :rows="2" />
            <AppButton type="submit" :loading="doc.processing">Simpan konfigurasi</AppButton>
        </form>
    </AppCard>

    <AppCard v-else title="Rekening Bendahara">
        <form class="max-w-xl space-y-3" @submit.prevent="acc.post('/admin/pengaturan/rekening', { preserveScroll: true })">
            <AppInput v-model="acc.bank_name" label="Nama bank" :error="acc.errors.bank_name" required />
            <AppInput v-model="acc.account_number" label="Nomor rekening" inputmode="numeric" :error="acc.errors.account_number" required />
            <AppInput v-model="acc.account_holder" label="Atas nama" :error="acc.errors.account_holder" required />
            <AppTextarea v-model="acc.payment_instruction" label="Petunjuk pembayaran (tampil ke guru)" :rows="2" :error="acc.errors.payment_instruction" />
            <p class="rounded-lg bg-warning-bg p-3 text-[13px] text-warning-fg">Rekening wajib lengkap sebelum periode dapat dipublikasikan. Perubahan rekening tercatat di audit log.</p>
            <AppButton type="submit" :loading="acc.processing">Simpan rekening</AppButton>
        </form>
    </AppCard>
</template>
