<script setup lang="ts">
import type { Paginated } from '@/types';
import AppButton from './AppButton.vue';

defineProps<{ page: Paginated<unknown> }>();
</script>

<template>
    <nav v-if="page.last_page > 1" class="flex flex-col items-center justify-between gap-3 border-t border-neutral-200 px-4 py-3 sm:flex-row" aria-label="Halaman">
        <p class="text-[13px] text-neutral-600">Menampilkan {{ page.from }}–{{ page.to }} dari {{ page.total }}</p>
        <div class="flex items-center gap-2">
            <AppButton v-if="page.prev_page_url" variant="outline" size="sm" :href="page.prev_page_url">Sebelumnya</AppButton>
            <span class="px-2 text-[13px] text-neutral-600">Hal. {{ page.current_page }} / {{ page.last_page }}</span>
            <AppButton v-if="page.next_page_url" variant="outline" size="sm" :href="page.next_page_url">Berikutnya</AppButton>
        </div>
    </nav>
</template>
