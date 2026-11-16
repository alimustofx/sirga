<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import { copyText } from '@/composables/useToast';
import { rupiah } from '@/lib/format';
import { Copy } from '@lucide/vue';

const props = defineProps<{ total: string; note: string; noteMax?: number }>();
const copyAmount = () => copyText(String(Math.round(Number(props.total))), 'Nominal');
</script>

<template>
    <AppCard title="Yang Harus Ditransfer" accent="gold">
        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
            <div class="min-w-0">
                <p class="eyebrow">Nominal</p>
                <p class="num text-3xl font-bold tracking-tight text-primary-900">{{ rupiah(total) }}</p>
            </div>
            <AppButton size="sm" variant="outline" @click="copyAmount"><Copy class="size-3.5" />Salin angka</AppButton>
        </div>

        <div class="mt-4 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
            <p class="eyebrow">Berita / keterangan transfer</p>
            <p class="mt-1.5 text-2xl font-bold tracking-wide text-neutral-950 [overflow-wrap:anywhere]">{{ note }}</p>
            <p class="num mt-1 text-xs text-neutral-600">{{ note.length }}<template v-if="noteMax"> dari {{ noteMax }}</template> karakter · muat di kolom berita semua bank</p>
            <AppButton class="mt-3" size="sm" variant="outline" @click="copyText(note, 'Berita transfer')"><Copy class="size-3.5" />Salin berita</AppButton>
        </div>
        <p class="mt-3 text-[13px] text-neutral-600">Tulis berita persis seperti di atas agar bendahara mudah mencocokkan pembayaran Anda.</p>
    </AppCard>
</template>
