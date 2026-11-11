<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppCard from '@/components/app/AppCard.vue';
import { formatDate } from '@/lib/format';
import { Head } from '@inertiajs/vue3';

defineProps<{ item: { id: number; title: string; content: string; published_at: string } }>();
</script>

<template>
    <Head :title="item.title" />
    <AppButton variant="ghost" size="sm" href="/saya/pengumuman" class="mb-3 -ml-2">← Semua pengumuman</AppButton>
    <AppCard>
        <h1 class="text-xl font-bold text-primary-900 sm:text-2xl">{{ item.title }}</h1>
        <p class="mt-1 text-xs text-neutral-600">{{ formatDate(item.published_at, true) }}</p>
        <p class="mt-4 text-[15px] leading-relaxed whitespace-pre-line text-neutral-800">{{ item.content }}</p>
    </AppCard>
</template>
