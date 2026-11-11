<script setup lang="ts">
withDefaults(defineProps<{ title?: string; subtitle?: string; padded?: boolean; flush?: boolean; accent?: 'teal' | 'gold' | 'navy' | 'none'; lift?: boolean }>(), { padded: true, accent: 'none' });
const bars = { teal: 'bg-primary-600', gold: 'bg-gold-500', navy: 'bg-primary-900', none: '' };
</script>

<template>
    <section :class="['relative overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-card', lift ? 'card-lift' : '']">
        <span v-if="accent !== 'none'" :class="['absolute inset-x-0 top-0 h-[3px]', bars[accent]]" aria-hidden="true" />
        <header v-if="title || $slots.header" class="flex items-center justify-between gap-3 border-b border-neutral-100 px-4 py-3.5 sm:px-5">
            <div class="min-w-0"><h2 v-if="title" class="text-[15px] font-semibold tracking-tight text-primary-900">{{ title }}</h2><p v-if="subtitle" class="text-[13px] text-neutral-600">{{ subtitle }}</p></div>
            <slot name="header" />
        </header>
        <div :class="padded && !flush ? 'p-4 sm:p-5' : ''"><slot /></div>
    </section>
</template>
