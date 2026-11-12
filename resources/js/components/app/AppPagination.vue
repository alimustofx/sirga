<script setup lang="ts">
import type { Paginated } from '@/types';
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ page: Paginated<unknown>; perPage?: number; perPageOptions?: number[] }>(), { perPageOptions: () => [] });

function go(params: Record<string, string | number | null>): void {
    const u = new URL(window.location.href);
    Object.entries(params).forEach(([k, v]) => (v === null ? u.searchParams.delete(k) : u.searchParams.set(k, String(v))));
    router.get(u.pathname + u.search, {}, { preserveScroll: true, preserveState: true });
}

// jendela nomor halaman: 1 … 4 5 6 … 12
const pages = computed<(number | '…')[]>(() => {
    const { current_page: c, last_page: l } = props.page;
    if (l <= 7) return Array.from({ length: l }, (_, i) => i + 1);
    const set = new Set([1, l, c - 1, c, c + 1].filter((n) => n >= 1 && n <= l));
    const sorted = [...set].sort((a, b) => a - b);
    const out: (number | '…')[] = [];
    sorted.forEach((n, i) => { if (i && n - (sorted[i - 1] as number) > 1) out.push('…'); out.push(n); });

    return out;
});
</script>

<template>
    <nav v-if="page.total > 0" class="flex flex-col items-center justify-between gap-3 border-t border-neutral-200 px-4 py-3 sm:flex-row" aria-label="Halaman">
        <div class="flex items-center gap-3 text-[13px] text-neutral-600">
            <span class="num">Menampilkan {{ page.from }}–{{ page.to }} dari {{ page.total }}</span>
            <label v-if="perPageOptions.length" class="flex items-center gap-2">per halaman
                <select :value="perPage" class="h-8 rounded-md border border-neutral-200 bg-white px-2 text-[13px] font-medium text-neutral-950" @change="go({ per_page: ($event.target as HTMLSelectElement).value, page: null })">
                    <option v-for="o in perPageOptions" :key="o" :value="o">{{ o }}</option>
                </select>
            </label>
        </div>
        <div v-if="page.last_page > 1" class="flex items-center gap-1">
            <button type="button" class="flex size-9 items-center justify-center rounded-lg border border-neutral-200 text-neutral-800 hover:bg-neutral-50 disabled:opacity-40" :disabled="page.current_page <= 1" aria-label="Halaman sebelumnya" @click="go({ page: page.current_page - 1 })"><ChevronLeft class="size-4" /></button>
            <template v-for="(n, i) in pages" :key="i">
                <span v-if="n === '…'" class="px-1 text-neutral-400">…</span>
                <button v-else type="button" :aria-current="n === page.current_page ? 'page' : undefined" :class="['num flex size-9 items-center justify-center rounded-lg text-[13px] font-semibold', n === page.current_page ? 'bg-primary-900 text-white' : 'text-neutral-800 hover:bg-neutral-100']" @click="go({ page: n })">{{ n }}</button>
            </template>
            <button type="button" class="flex size-9 items-center justify-center rounded-lg border border-neutral-200 text-neutral-800 hover:bg-neutral-50 disabled:opacity-40" :disabled="page.current_page >= page.last_page" aria-label="Halaman berikutnya" @click="go({ page: page.current_page + 1 })"><ChevronRight class="size-4" /></button>
        </div>
    </nav>
</template>
