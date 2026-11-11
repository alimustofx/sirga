<script setup lang="ts">
import type { Period } from '@/types';
import { router } from '@inertiajs/vue3';

const props = defineProps<{ periods: Period[]; current?: number | null; path: string; query?: Record<string, string> }>();
function go(e: Event): void {
    router.get(props.path, { ...(props.query ?? {}), period: (e.target as HTMLSelectElement).value }, { preserveScroll: true });
}
</script>

<template>
    <div>
        <label for="period-filter" class="sr-only">Periode</label>
        <select id="period-filter" :value="current ?? ''" class="h-11 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm font-semibold focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 focus:outline-none sm:h-10 sm:w-52" @change="go">
            <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.label }} · {{ p.status_label }}</option>
        </select>
    </div>
</template>
