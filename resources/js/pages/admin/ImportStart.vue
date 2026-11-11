<script setup lang="ts">
import PayrollImportWizard from '@/components/admin/PayrollImportWizard.vue';
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import { fileSize } from '@/lib/format';
import type { Period } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ period: Period; canImport: boolean }>();
const form = useForm<{ employment_status: 'PNS' | 'PPPK' | ''; file: File | null }>({ employment_status: '', file: null });
const clientError = ref<string | null>(null);
const step = computed(() => (form.processing ? 3 : form.file ? 2 : form.employment_status ? 2 : 1));

function pick(e: Event): void {
    const f = (e.target as HTMLInputElement).files?.[0] ?? null;
    clientError.value = null;
    form.file = null;
    if (!f) return;
    if (!/\.(xls|xlsx)$/i.test(f.name)) { clientError.value = 'File harus berformat .xls atau .xlsx.'; return; }
    if (f.size > 10 * 1024 * 1024) { clientError.value = 'Ukuran file maksimal 10 MB.'; return; }
    form.file = f;
}
const submit = () => form.post(`/admin/periode/${props.period.id}/impor`, { forceFormData: true });
</script>

<template>
    <Head title="Impor Excel" />
    <AppButton variant="ghost" size="sm" :href="`/admin/periode/${period.id}`" class="mb-2 -ml-2">← {{ period.label }}</AppButton>
    <AppPageHeader title="Impor Data Penghasilan" :subtitle="`Periode ${period.label} · data baru disimpan hanya setelah Anda mengonfirmasi hasil validasi.`" />
    <PayrollImportWizard :current="step" />

    <AppCard v-if="!canImport"><p class="text-sm text-neutral-800">Impor hanya dapat dilakukan pada periode berstatus Draft atau Review.</p></AppCard>
    <form v-else class="grid gap-4 lg:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-4 lg:col-span-2">
            <AppCard title="1. Pilih jenis ASN pada file">
                <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Jenis ASN">
                    <label v-for="s in ['PNS', 'PPPK']" :key="s" :class="['flex min-h-16 cursor-pointer items-center gap-3 rounded-xl border-2 p-4 transition-colors', form.employment_status === s ? 'border-primary-600 bg-[#e6f2f4]' : 'border-neutral-200 hover:bg-neutral-50']">
                        <input v-model="form.employment_status" type="radio" :value="s" class="size-5 accent-primary-700" />
                        <span><span class="block text-base font-bold text-primary-900">{{ s }}</span><span class="text-[13px] text-neutral-600">File gaji {{ s }}</span></span>
                    </label>
                </div>
                <p v-if="form.errors.employment_status" class="mt-2 text-[13px] text-danger-fg">{{ form.errors.employment_status }}</p>
            </AppCard>
            <AppCard title="2. Unggah file Excel">
                <label for="src-file" class="flex min-h-32 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-neutral-400 px-4 py-6 text-center hover:bg-neutral-50">
                    <span class="text-sm font-semibold text-primary-700">{{ form.file ? 'Ganti file' : 'Pilih file .xls / .xlsx' }}</span>
                    <span class="text-xs text-neutral-600">Maksimal 10 MB. Header dua baris seperti file sumber penggajian.</span>
                    <input id="src-file" type="file" accept=".xls,.xlsx" class="sr-only" @change="pick" />
                </label>
                <p v-if="clientError || form.errors.file" class="mt-2 text-[13px] font-medium text-danger-fg" role="alert">{{ clientError || form.errors.file }}</p>
                <p v-if="form.file" class="mt-3 rounded-lg bg-neutral-50 p-3 text-sm"><strong>{{ form.file.name }}</strong> · {{ fileSize(form.file.size) }}</p>
            </AppCard>
            <AppButton type="submit" size="lg" :disabled="!form.file || !form.employment_status" :loading="form.processing">Proses & Validasi File</AppButton>
        </div>
        <div class="space-y-4">
            <AppCard title="Template">
                <p class="text-[13px] text-neutral-600">Gunakan template bila ingin menyusun file baru dengan struktur kolom yang benar.</p>
                <div class="mt-3 flex flex-col gap-2"><AppButton variant="outline" external :href="`/admin/impor/template/PNS?period=${period.id}`">Unduh template PNS</AppButton><AppButton variant="outline" external :href="`/admin/impor/template/PPPK?period=${period.id}`">Unduh template PPPK</AppButton></div>
            </AppCard>
            <AppCard title="Yang diperiksa sistem">
                <ul class="space-y-1.5 text-[13px] text-neutral-800"><li>• NIP kosong, duplikat, atau tidak terdaftar</li><li>• Status ASN sesuai pilihan</li><li>• Nominal valid dan tidak negatif</li><li>• Total dihitung ulang dan dibandingkan dengan file</li><li>• Data yang sudah pernah diimpor pada periode ini</li></ul>
            </AppCard>
        </div>
    </form>
</template>
