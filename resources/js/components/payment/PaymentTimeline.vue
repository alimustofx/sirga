<script setup lang="ts">
import type { PaymentStatus } from '@/types';
import { Check, X } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ status: PaymentStatus }>();
const steps = ['Tagihan terbit', 'Bukti diunggah', 'Diverifikasi', 'Lunas'];
// jumlah langkah selesai & apakah langkah aktif bermasalah
const done = computed(() => ({ belum_dibayar: 1, menunggu_verifikasi: 2, ditolak: 1, lunas: 4 })[props.status]);
const failed = computed(() => props.status === 'ditolak');
</script>

<template>
    <ol class="flex items-start" aria-label="Tahapan pembayaran">
        <li v-for="(s, i) in steps" :key="s" class="relative flex flex-1 flex-col items-center text-center" :aria-current="i === done ? 'step' : undefined">
            <span v-if="i > 0" :class="['absolute top-4 right-1/2 h-0.5 w-full transition-colors duration-500', i < done ? 'bg-primary-600' : 'bg-neutral-200']" aria-hidden="true" />
            <span :class="['relative z-10 flex size-8 items-center justify-center rounded-full text-xs font-bold ring-4 ring-white transition-all duration-500',
                i < done ? 'bg-primary-600 text-white' : i === done ? (failed ? 'bg-danger-bg text-danger-fg ring-danger-bg/60' : 'bg-gold-100 text-gold-700 ring-gold-100/60') : 'bg-neutral-100 text-neutral-400']">
                <Check v-if="i < done" class="size-4" /><X v-else-if="i === done && failed" class="size-4" /><template v-else>{{ i + 1 }}</template>
            </span>
            <span :class="['mt-2 px-1 text-[11px] leading-tight font-semibold sm:text-xs', i < done ? 'text-primary-900' : i === done ? (failed ? 'text-danger-fg' : 'text-gold-700') : 'text-neutral-400']">{{ i === 1 && failed ? 'Bukti ditolak' : s }}</span>
        </li>
    </ol>
</template>
