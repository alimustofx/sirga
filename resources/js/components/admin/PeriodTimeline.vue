<script setup lang="ts">
import { formatDate } from '@/lib/format';
import type { PeriodStatus } from '@/types';
import { Check } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{ status: PeriodStatus; publishedAt?: string | null; closedAt?: string | null }>();
const steps = [
    { key: 'draft', label: 'Draft', hint: 'Impor & input data' },
    { key: 'review', label: 'Review', hint: 'Pemeriksaan kelengkapan' },
    { key: 'published', label: 'Published', hint: 'Terlihat oleh guru' },
    { key: 'closed', label: 'Closed', hint: 'Terkunci' },
];
const idx = computed(() => steps.findIndex((s) => s.key === props.status));
const note = (k: string) => (k === 'published' ? props.publishedAt : k === 'closed' ? props.closedAt : null);
</script>

<template>
    <ol class="grid grid-cols-4 gap-1" aria-label="Alur periode">
        <li v-for="(s, i) in steps" :key="s.key" class="relative flex flex-col items-center text-center" :aria-current="i === idx ? 'step' : undefined">
            <span v-if="i > 0" :class="['absolute top-4 right-1/2 h-0.5 w-full transition-colors duration-500', i <= idx ? 'bg-primary-600' : 'bg-neutral-200']" aria-hidden="true" />
            <span :class="['relative z-10 flex size-8 items-center justify-center rounded-full text-xs font-bold ring-4 ring-white transition-all duration-500', i < idx ? 'bg-primary-600 text-white' : i === idx ? 'bg-primary-900 text-white shadow-[0_6px_14px_-4px_rgba(20,43,69,0.6)]' : 'bg-neutral-100 text-neutral-400']"><Check v-if="i < idx" class="size-4" /><template v-else>{{ i + 1 }}</template></span>
            <span :class="['mt-2 text-xs font-semibold', i <= idx ? 'text-primary-900' : 'text-neutral-400']">{{ s.label }}</span>
            <span class="hidden px-1 text-[11px] text-neutral-600 sm:block">{{ note(s.key) ? formatDate(note(s.key)) : s.hint }}</span>
        </li>
    </ol>
</template>
