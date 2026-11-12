<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { onMounted, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ title: string; storageKey?: string; defaultOpen?: boolean; count?: number }>(), { defaultOpen: true });
const open = ref(props.defaultOpen);

onMounted(() => {
    if (props.storageKey) {
        try { const v = localStorage.getItem(`sirga.${props.storageKey}`); if (v !== null) open.value = v === '1'; } catch { /* abaikan */ }
    }
});
watch(open, (v) => { if (props.storageKey) { try { localStorage.setItem(`sirga.${props.storageKey}`, v ? '1' : '0'); } catch { /* abaikan */ } } });
</script>

<template>
    <section class="rounded-xl border border-neutral-200 bg-white">
        <button type="button" class="flex min-h-14 w-full items-center gap-3 px-4 py-3 text-left sm:px-5" :aria-expanded="open" @click="open = !open">
            <span class="flex min-w-0 flex-1 flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-4">
                <span class="flex items-center gap-2 text-[15px] font-semibold text-primary-900">{{ title }}<span v-if="count !== undefined" class="num rounded-md bg-neutral-100 px-1.5 py-0.5 text-[11px] font-semibold text-neutral-600">{{ count }}</span></span>
                <span v-if="!open && $slots.summary" class="min-w-0 flex-1"><slot name="summary" /></span>
            </span>
            <span class="flex shrink-0 items-center gap-1.5 text-[13px] font-medium text-neutral-600">{{ open ? 'Ciutkan' : 'Lihat' }}<ChevronDown :class="['size-4 transition-transform duration-200', open ? 'rotate-180' : '']" /></span>
        </button>
        <div :class="['grid transition-[grid-template-rows] duration-200 ease-out', open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]']">
            <div class="min-h-0 overflow-hidden"><div class="border-t border-neutral-200"><slot /></div></div>
        </div>
    </section>
</template>
