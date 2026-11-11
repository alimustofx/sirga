<script setup lang="ts">
import AppCard from '@/components/app/AppCard.vue';
import AppEmptyState from '@/components/app/AppEmptyState.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppPagination from '@/components/app/AppPagination.vue';
import { formatDate } from '@/lib/format';
import type { Paginated } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ items: Paginated<{ id: number; title: string; excerpt: string; published_at: string }> }>();
</script>

<template>
    <Head title="Pengumuman" />
    <AppPageHeader title="Pengumuman" subtitle="Informasi resmi dari sekolah." />
    <AppCard flush>
        <AppEmptyState v-if="!items.data.length" title="Belum ada pengumuman" />
        <ul v-else class="divide-y divide-neutral-100">
            <li v-for="a in items.data" :key="a.id">
                <Link :href="`/saya/pengumuman/${a.id}`" class="block px-4 py-4 hover:bg-neutral-50 sm:px-5">
                    <p class="text-base font-semibold text-primary-900">{{ a.title }}</p>
                    <p class="mt-1 line-clamp-2 text-sm text-neutral-600">{{ a.excerpt }}</p>
                    <p class="mt-1.5 text-xs text-neutral-400">{{ formatDate(a.published_at) }}</p>
                </Link>
            </li>
        </ul>
        <AppPagination :page="items" />
    </AppCard>
</template>
