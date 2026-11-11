<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import { fileSize } from '@/lib/format';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ periodId: number; maxKb: number; retry?: boolean }>();
const form = useForm<{ proof: File | null }>({ proof: null });
const local = ref<string | null>(null);
const clientError = ref<string | null>(null);
const input = ref<HTMLInputElement | null>(null);
const OK = ['image/jpeg', 'image/png', 'application/pdf'];

function pick(e: Event): void {
    const f = (e.target as HTMLInputElement).files?.[0] ?? null;
    clientError.value = null;
    local.value = null;
    form.proof = null;
    if (!f) return;
    if (!OK.includes(f.type)) {
        clientError.value = 'Format file harus JPG, PNG, atau PDF.';
        return;
    }
    if (f.size > props.maxKb * 1024) {
        clientError.value = `Ukuran file maksimal ${Math.round(props.maxKb / 1024)} MB.`;
        return;
    }
    form.proof = f;
    if (f.type.startsWith('image/')) local.value = URL.createObjectURL(f);
}

const error = computed(() => clientError.value || (form.errors.proof as string | undefined));
const submit = () =>
    form.post(`/saya/pembayaran/${props.periodId}/bukti`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            local.value = null;
            if (input.value) input.value.value = '';
        },
    });
</script>

<template>
    <AppCard :title="retry ? 'Unggah Ulang Bukti Transfer' : 'Unggah Bukti Transfer'">
        <form class="space-y-3" @submit.prevent="submit">
            <label for="proof" :class="['flex min-h-32 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed px-4 py-6 text-center transition-colors hover:bg-neutral-50', error ? 'border-[#dc2626]' : 'border-neutral-400']">
                <span class="text-sm font-semibold text-primary-700">{{ form.proof ? 'Ganti file' : 'Pilih file bukti transfer' }}</span>
                <span class="text-xs text-neutral-600">JPG, PNG, atau PDF · maksimal {{ Math.round(maxKb / 1024) }} MB</span>
                <input id="proof" ref="input" type="file" accept="image/jpeg,image/png,application/pdf" class="sr-only" @change="pick" />
            </label>
            <p v-if="error" class="text-[13px] font-medium text-danger-fg" role="alert">{{ error }}</p>
            <div v-if="form.proof" class="flex items-center gap-3 rounded-lg bg-neutral-50 p-3">
                <img v-if="local" :src="local" alt="Pratinjau bukti" class="size-16 rounded object-cover" />
                <div class="min-w-0 text-sm"><p class="truncate font-semibold">{{ form.proof.name }}</p><p class="text-xs text-neutral-600">{{ fileSize(form.proof.size) }}</p></div>
            </div>
            <div v-if="form.progress" class="h-2 overflow-hidden rounded-full bg-neutral-100" role="progressbar" :aria-valuenow="form.progress.percentage"><div class="h-full bg-primary-600 transition-all" :style="{ width: `${form.progress.percentage}%` }" /></div>
            <AppButton type="submit" block size="lg" :disabled="!form.proof" :loading="form.processing">Kirim Bukti Transfer</AppButton>
        </form>
    </AppCard>
</template>
