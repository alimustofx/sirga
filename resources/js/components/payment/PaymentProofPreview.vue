<script setup lang="ts">
import type { Proof } from '@/types';

defineProps<{ proof: Proof }>();
</script>

<template>
    <a v-if="proof.mime.startsWith('image/')" :href="proof.url" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50">
        <img :src="proof.url" :alt="`Bukti transfer ${proof.filename}`" class="mx-auto max-h-96 object-contain" loading="lazy" />
    </a>
    <div v-else class="overflow-hidden rounded-lg border border-neutral-200">
        <iframe :src="proof.url" :title="proof.filename" class="h-96 w-full bg-neutral-50" />
        <a :href="proof.url" target="_blank" rel="noopener" class="block border-t border-neutral-200 px-3 py-2 text-center text-[13px] font-semibold text-primary-600">Buka PDF di tab baru</a>
    </div>
</template>
