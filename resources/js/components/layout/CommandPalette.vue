<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CornerDownLeft, Search } from '@lucide/vue';
import { DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { computed, nextTick, ref, watch } from 'vue';
import type { NavGroup } from './NavList.vue';

const open = defineModel<boolean>('open', { default: false });
const props = defineProps<{ groups: NavGroup[] }>();

const q = ref('');
const idx = ref(0);
const input = ref<HTMLInputElement | null>(null);
const all = computed(() => props.groups.flatMap((g) => g.items.map((i) => ({ ...i, group: g.title ?? 'Umum' }))));
const results = computed(() => {
    const s = q.value.trim().toLowerCase();

    return all.value.filter((i) => !s || i.label.toLowerCase().includes(s) || i.group.toLowerCase().includes(s));
});

watch(q, () => { idx.value = 0; });
watch(open, async (v) => {
    if (v) {
        q.value = '';
        idx.value = 0;
        await nextTick();
        input.value?.focus();
    }
});

function go(href: string): void {
    open.value = false;
    router.visit(href);
}
function onKey(e: KeyboardEvent): void {
    if (e.key === 'ArrowDown') { e.preventDefault(); idx.value = Math.min(results.value.length - 1, idx.value + 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); idx.value = Math.max(0, idx.value - 1); }
    else if (e.key === 'Enter') { const r = results.value[idx.value]; if (r) go(r.href); }
}
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-primary-900/50 backdrop-blur-[2px]" />
            <DialogContent class="fixed top-[14%] left-1/2 z-50 w-[min(34rem,calc(100vw-1.5rem))] -translate-x-1/2 animate-pop overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-modal" @keydown="onKey">
                <DialogTitle class="sr-only">Pencarian cepat</DialogTitle>
                <DialogDescription class="sr-only">Ketik untuk mencari menu, gunakan panah untuk memilih, Enter untuk membuka.</DialogDescription>
                <div class="flex items-center gap-3 border-b border-neutral-200 px-4">
                    <Search class="size-4 text-neutral-400" />
                    <input ref="input" v-model="q" type="text" placeholder="Cari menu… (mis. potongan, pembayaran)" class="h-14 w-full bg-transparent text-[15px] outline-none placeholder:text-neutral-400" aria-label="Cari menu" />
                    <kbd class="hidden rounded-md border border-neutral-200 bg-neutral-50 px-1.5 py-0.5 text-[11px] font-semibold text-neutral-600 sm:block">Esc</kbd>
                </div>
                <ul class="max-h-80 overflow-y-auto p-2" role="listbox">
                    <li v-for="(r, i) in results" :key="r.href" role="option" :aria-selected="i === idx">
                        <button type="button" :class="['flex min-h-12 w-full items-center gap-3 rounded-xl px-3 text-left transition-colors', i === idx ? 'bg-primary-600/10' : 'hover:bg-neutral-50']" @mousemove="idx = i" @click="go(r.href)">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary-900/5 text-primary-700"><component :is="r.icon" class="size-4" /></span>
                            <span class="flex-1 text-sm font-medium text-neutral-950">{{ r.label }}</span>
                            <span class="text-xs text-neutral-600">{{ r.group }}</span>
                            <CornerDownLeft v-if="i === idx" class="size-3.5 text-neutral-400" />
                        </button>
                    </li>
                    <li v-if="!results.length" class="px-3 py-8 text-center text-sm text-neutral-600">Tidak ada menu yang cocok dengan “{{ q }}”.</li>
                </ul>
                <div class="flex items-center justify-between border-t border-neutral-100 bg-neutral-50 px-4 py-2 text-[11px] text-neutral-600"><span>↑↓ pilih · Enter buka</span><span>⌘K / Ctrl+K</span></div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
