<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import { copyText } from '@/composables/useToast';
import { rupiah } from '@/lib/format';
import { Copy } from '@lucide/vue';

const props = defineProps<{ total: string; note: string }>();
const copyAmount = () => copyText(String(Math.round(Number(props.total))), 'Nominal');
</script>

<template>
    <AppCard title="Nominal yang Harus Ditransfer" accent="gold">
        <div class="flex items-center justify-between gap-3">
            <p class="num text-3xl font-bold tracking-tight text-primary-900">{{ rupiah(total) }}</p>
            <AppButton size="sm" variant="outline" @click="copyAmount"><Copy class="size-3.5" />Salin angka</AppButton>
        </div>
        <div class="mt-4 rounded-xl border border-dashed border-neutral-400 bg-neutral-50 p-3.5">
            <p class="text-[11px] font-semibold tracking-wider text-neutral-600 uppercase">Berita transfer</p>
            <p class="mt-1 text-sm font-medium text-neutral-950">{{ note }}</p>
            <AppButton class="mt-2.5" size="sm" variant="outline" @click="copyText(note, 'Berita transfer')"><Copy class="size-3.5" />Salin berita</AppButton>
        </div>
    </AppCard>
</template>
