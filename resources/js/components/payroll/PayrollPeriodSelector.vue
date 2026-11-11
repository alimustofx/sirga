<script setup lang="ts">
import type { Period } from '@/types';
import { router } from '@inertiajs/vue3';

const props = defineProps<{ periods: Period[]; current: number | null | undefined; base: string }>();
function go(e: Event): void {
    router.get(`${props.base}/${(e.target as HTMLSelectElement).value}`, {}, { preserveScroll: true });
}
</script>

<template>
    <div v-if="periods.length">
        <label for="period-select" class="sr-only">Pilih periode</label>
        <select id="period-select" :value="current ?? ''" class="h-11 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-950 focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 focus:outline-none sm:h-10 sm:w-56" @change="go">
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.label }}</option>
        </select>
    </div>
</template>
