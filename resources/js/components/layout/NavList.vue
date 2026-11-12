<script setup lang="ts">
import { sidebar } from '@/composables/useSidebar';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import type { Component } from 'vue';

export type NavItem = { label: string; href: string; icon: Component; exact?: boolean };
export type NavGroup = { title?: string; items: NavItem[] };

defineProps<{ groups: NavGroup[]; collapsed?: boolean }>();
defineEmits<{ navigate: [] }>();
const page = usePage();

const active = (i: NavItem): boolean => {
    const path = page.url.split('?')[0] ?? '';

    return i.exact ? path === i.href : path === i.href || path.startsWith(`${i.href}/`);
};
// grup tetap terbuka bila berisi halaman aktif
const isOpen = (g: NavGroup): boolean => !g.title || !sidebar.closed[g.title] || g.items.some(active);
const toggle = (g: NavGroup): void => { if (g.title) sidebar.closed[g.title] = !sidebar.closed[g.title]; };
</script>

<template>
    <nav aria-label="Navigasi utama" :class="collapsed ? 'space-y-2' : 'space-y-1'">
        <div v-for="(g, gi) in groups" :key="gi" :class="collapsed && gi > 0 ? 'border-t border-white/10 pt-2' : ''">
            <button v-if="g.title && !collapsed" type="button" class="flex min-h-8 w-full items-center justify-between rounded-md px-3 text-[11px] font-semibold tracking-wider text-white/50 uppercase transition-colors hover:text-white/80" :aria-expanded="isOpen(g)" @click="toggle(g)">
                {{ g.title }}<ChevronDown :class="['size-3.5 transition-transform duration-200', isOpen(g) ? '' : '-rotate-90']" />
            </button>
            <div :class="['grid transition-[grid-template-rows] duration-200 ease-out', collapsed || isOpen(g) ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]']">
                <ul class="min-h-0 space-y-0.5 overflow-hidden">
                    <li v-for="i in g.items" :key="i.href">
                        <Link :href="i.href" :title="collapsed ? i.label : undefined" :aria-label="collapsed ? i.label : undefined" :aria-current="active(i) ? 'page' : undefined"
                            :class="['relative flex min-h-11 items-center rounded-lg text-sm font-medium transition-colors', collapsed ? 'justify-center px-0' : 'gap-3 px-3', active(i) ? 'bg-white/12 text-white before:absolute before:top-2.5 before:bottom-2.5 before:left-0 before:w-[3px] before:rounded-full before:bg-gold-400' : 'text-white/70 hover:bg-white/7 hover:text-white']"
                            @click="$emit('navigate')">
                            <component :is="i.icon" class="size-[18px] shrink-0" />
                            <span :class="collapsed ? 'sr-only' : ''">{{ i.label }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>
