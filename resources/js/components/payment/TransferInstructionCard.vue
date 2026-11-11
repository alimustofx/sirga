<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import { copyText } from '@/composables/useToast';
import { rupiah } from '@/lib/format';

defineProps<{ total: string; note: string }>();
</script>

<template>
    <AppCard title="Nominal yang Harus Ditransfer">
        <p class="num text-3xl font-bold text-primary-900">{{ rupiah(total) }}</p>
        <div class="mt-4 rounded-lg border border-dashed border-neutral-400 p-3">
            <p class="text-xs font-semibold tracking-wide text-neutral-600 uppercase">Berita transfer</p>
            <p class="mt-1 text-sm font-medium text-neutral-950">{{ note }}</p>
            <AppButton class="mt-2" size="sm" variant="outline" @click="copyText(note, 'Berita transfer')">Salin berita</AppButton>
        </div>
    </AppCard>
</template>
