<script setup lang="ts">
import { Check } from '@lucide/vue';

defineProps<{ current: number }>();
const steps = ['Pilih status', 'Unggah file', 'Pemrosesan', 'Validasi', 'Konfirmasi', 'Selesai'];
</script>

<template>
    <ol class="mb-6 flex items-center gap-1 overflow-x-auto pb-1" aria-label="Langkah impor">
        <li v-for="(s, i) in steps" :key="s" class="flex shrink-0 items-center gap-1" :aria-current="current === i + 1 ? 'step' : undefined">
            <span :class="['flex size-7 items-center justify-center rounded-full text-xs font-bold', current > i + 1 ? 'bg-primary-600 text-white' : current === i + 1 ? 'bg-primary-900 text-white' : 'bg-neutral-100 text-neutral-600']">
                <Check v-if="current > i + 1" class="size-4" /><template v-else>{{ i + 1 }}</template>
            </span>
            <span :class="['text-[13px] font-semibold', current === i + 1 ? 'text-primary-900' : 'text-neutral-600']">{{ s }}</span>
            <span v-if="i < steps.length - 1" class="mx-1 h-px w-4 bg-neutral-200 sm:w-6" aria-hidden="true" />
        </li>
    </ol>
</template>
