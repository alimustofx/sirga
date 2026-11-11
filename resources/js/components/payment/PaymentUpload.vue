<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import { fileSize } from '@/lib/format';
import { useForm } from '@inertiajs/vue3';
import { CloudUpload, FileCheck2 } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{ periodId: number; maxKb: number; retry?: boolean }>();
const form = useForm<{ proof: File | null }>({ proof: null });
const local = ref<string | null>(null);
const clientError = ref<string | null>(null);
const input = ref<HTMLInputElement | null>(null);
const drag = ref(false);
const OK = ['image/jpeg', 'image/png', 'application/pdf'];

function accept(f: File | null): void {
    clientError.value = null;
    local.value = null;
    form.proof = null;
    if (!f) return;
    if (!OK.includes(f.type)) { clientError.value = 'Format file harus JPG, PNG, atau PDF.'; return; }
    if (f.size > props.maxKb * 1024) { clientError.value = `Ukuran file maksimal ${Math.round(props.maxKb / 1024)} MB.`; return; }
    form.proof = f;
    if (f.type.startsWith('image/')) local.value = URL.createObjectURL(f);
}
const pick = (e: Event) => accept((e.target as HTMLInputElement).files?.[0] ?? null);
function drop(e: DragEvent): void {
    drag.value = false;
    accept(e.dataTransfer?.files?.[0] ?? null);
}

const error = computed(() => clientError.value || (form.errors.proof as string | undefined));
const submit = () =>
    form.post(`/saya/pembayaran/${props.periodId}/bukti`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { form.reset(); local.value = null; if (input.value) input.value.value = ''; },
    });
</script>

<template>
    <AppCard :title="retry ? 'Unggah Ulang Bukti Transfer' : 'Unggah Bukti Transfer'" accent="teal">
        <form class="space-y-3" @submit.prevent="submit">
            <label for="proof" :class="['flex min-h-40 cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-4 py-7 text-center transition-all duration-200', drag ? 'scale-[1.01] border-primary-600 bg-primary-600/5' : error ? 'border-[#dc2626] bg-danger-bg/30' : 'border-neutral-400 hover:border-primary-600 hover:bg-neutral-50']"
                @dragover.prevent="drag = true" @dragleave="drag = false" @drop.prevent="drop">
                <span :class="['flex size-12 items-center justify-center rounded-2xl transition-colors', form.proof ? 'bg-success-bg text-success-fg' : 'bg-primary-600/10 text-primary-600']"><FileCheck2 v-if="form.proof" class="size-6" /><CloudUpload v-else class="size-6" /></span>
                <span class="text-sm font-semibold text-primary-900">{{ form.proof ? 'Ganti file' : 'Seret file ke sini atau klik untuk memilih' }}</span>
                <span class="text-xs text-neutral-600">JPG, PNG, atau PDF · maksimal {{ Math.round(maxKb / 1024) }} MB</span>
                <input id="proof" ref="input" type="file" accept="image/jpeg,image/png,application/pdf" class="sr-only" @change="pick" />
            </label>
            <p v-if="error" class="text-[13px] font-medium text-danger-fg" role="alert">{{ error }}</p>
            <div v-if="form.proof" class="flex animate-pop items-center gap-3 rounded-xl bg-neutral-50 p-3 ring-1 ring-neutral-200">
                <img v-if="local" :src="local" alt="Pratinjau bukti" class="size-16 rounded-lg object-cover" />
                <div class="min-w-0 text-sm"><p class="truncate font-semibold">{{ form.proof.name }}</p><p class="text-xs text-neutral-600">{{ fileSize(form.proof.size) }}</p></div>
            </div>
            <div v-if="form.progress" class="h-2 overflow-hidden rounded-full bg-neutral-100" role="progressbar" :aria-valuenow="form.progress.percentage"><div class="h-full rounded-full bg-primary-600 transition-all" :style="{ width: `${form.progress.percentage}%` }" /></div>
            <AppButton type="submit" block size="lg" :disabled="!form.proof" :loading="form.processing">Kirim Bukti Transfer</AppButton>
        </form>
    </AppCard>
</template>
