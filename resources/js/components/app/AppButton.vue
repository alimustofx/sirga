<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
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
    'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-colors select-none',
    'disabled:pointer-events-none disabled:opacity-50',
    props.block ? 'w-full' : '',
    { sm: 'min-h-9 px-3 text-[13px]', md: 'min-h-11 px-4 text-sm sm:min-h-10', lg: 'min-h-12 px-5 text-base' }[props.size],
    {
        primary: 'bg-primary-700 text-white hover:bg-primary-800 active:bg-primary-900',
        secondary: 'bg-neutral-100 text-primary-900 hover:bg-neutral-200',
        outline: 'border border-neutral-200 bg-white text-neutral-800 hover:bg-neutral-50',
        ghost: 'text-primary-700 hover:bg-neutral-100',
        danger: 'bg-[#b91c1c] text-white hover:bg-[#991b1b]',
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
