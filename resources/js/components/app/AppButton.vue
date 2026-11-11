<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'gold';
        size?: 'sm' | 'md' | 'lg';
        loading?: boolean;
        href?: string;
        external?: boolean;
        type?: 'button' | 'submit';
        disabled?: boolean;
        block?: boolean;
    }>(),
    { variant: 'primary', size: 'md', type: 'button' },
);

const cls = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition-all duration-200 select-none',
    'active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50',
    props.block ? 'w-full' : '',
    { sm: 'min-h-9 px-3.5 text-[13px]', md: 'min-h-11 px-4.5 text-sm sm:min-h-10', lg: 'min-h-12 px-6 text-[15px]' }[props.size],
    {
        primary: 'bg-primary-700 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.14),0_1px_2px_rgba(20,43,69,0.3)] hover:bg-primary-800 hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.14),0_6px_16px_-4px_rgba(20,43,69,0.5)]',
        secondary: 'bg-neutral-100 text-primary-900 hover:bg-neutral-200',
        outline: 'border border-neutral-200 bg-white text-neutral-800 shadow-card hover:border-neutral-400 hover:bg-neutral-50',
        ghost: 'text-primary-700 hover:bg-primary-600/10',
        danger: 'bg-[#b91c1c] text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.14)] hover:bg-[#991b1b]',
        gold: 'bg-gold-500 text-primary-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.35)] hover:bg-gold-400',
    }[props.variant],
]);
</script>

<template>
    <a v-if="href && external" :href="href" :class="cls" target="_blank" rel="noopener"><slot /></a>
    <Link v-else-if="href" :href="href" :class="cls"><slot /></Link>
    <button v-else :type="type" :disabled="disabled || loading" :class="cls">
        <svg v-if="loading" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25" />
            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        </svg>
        <slot />
    </button>
</template>
