<script setup lang="ts">
import AuditLogTable, { type Log } from '@/components/admin/AuditLogTable.vue';
import AppCard from '@/components/app/AppCard.vue';
import AppInput from '@/components/app/AppInput.vue';
import AppPageHeader from '@/components/app/AppPageHeader.vue';
import AppPagination from '@/components/app/AppPagination.vue';
import { useFilters } from '@/composables/useFilters';
import type { Paginated } from '@/types';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ logs: Paginated<Log>; filters: { q: string; from: string; to: string } }>();
const f = useFilters('/admin/audit', props.filters);
</script>

<template>
    <Head title="Audit Aktivitas" />
    <AppPageHeader title="Audit Aktivitas" subtitle="Catatan perubahan data penting. Log tidak dapat diubah atau dihapus." />
    <AppCard flush>
        <div class="grid gap-3 border-b border-neutral-200 p-4 sm:grid-cols-3">
            <AppInput v-model="f.q" placeholder="Cari aksi, pengguna, atau alasan…" inputmode="search" />
            <AppInput v-model="f.from" type="date" />
            <AppInput v-model="f.to" type="date" />
        </div>
        <AuditLogTable :logs="logs.data" />
        <AppPagination :page="logs" />
    </AppCard>
</template>
