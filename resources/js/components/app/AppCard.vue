<script setup lang="ts">
withDefaults(defineProps<{ title?: string; subtitle?: string; padded?: boolean; flush?: boolean; accent?: 'teal' | 'gold' | 'navy' | 'none'; lift?: boolean }>(), { padded: true, accent: 'none' });
const bars = { teal: 'border-t-primary-600', gold: 'border-t-gold-500', navy: 'border-t-primary-900', none: '' };
</script>

<template>
    <section :class="['rounded-xl border border-neutral-200 bg-white', accent !== 'none' ? `border-t-2 ${bars[accent]}` : '', lift ? 'transition-colors hover:border-neutral-400' : '']">
        <header v-if="title || $slots.header" class="flex items-center justify-between gap-3 border-b border-neutral-200 px-4 py-3 sm:px-5">
            <div class="min-w-0"><h2 v-if="title" class="text-[15px] font-semibold text-primary-900">{{ title }}</h2><p v-if="subtitle" class="mt-0.5 text-[13px] text-neutral-600">{{ subtitle }}</p></div>
            <slot name="header" />
        </header>
        <div :class="padded && !flush ? 'p-4 sm:p-5' : ''"><slot /></div>
    </section>
</template>
