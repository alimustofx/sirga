<script setup lang="ts" generic="T extends Record<string, any>">
import AppEmptyState from './AppEmptyState.vue';

export type Column = { key: string; label: string; align?: 'left' | 'right'; mobile?: boolean; primary?: boolean; class?: string };

const props = defineProps<{ columns: Column[]; rows: T[]; rowKey?: string; emptyTitle?: string; emptyText?: string }>();
const key = (r: T, i: number) => String(r[props.rowKey ?? 'id'] ?? i);
</script>

<template>
    <div v-if="!rows.length"><AppEmptyState :title="emptyTitle ?? 'Belum ada data'" :description="emptyText"><slot name="empty" /></AppEmptyState></div>
    <template v-else>
        <!-- Desktop / tablet -->
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 bg-neutral-50/80">
                        <th v-for="c in columns" :key="c.key" scope="col" :class="['px-4 py-3 text-[11px] font-semibold tracking-wider whitespace-nowrap text-neutral-600 uppercase', c.align === 'right' ? 'text-right' : 'text-left']">{{ c.label }}</th>
                        <th v-if="$slots.actions" class="w-12 px-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(r, i) in rows" :key="key(r, i)" class="group border-b border-neutral-100 transition-colors last:border-0 hover:bg-primary-600/[0.04]">
                        <td v-for="c in columns" :key="c.key" :class="['px-4 py-3.5 align-middle', c.align === 'right' ? 'num text-right' : '', c.class]">
                            <slot :name="`cell-${c.key}`" :row="r" :value="r[c.key]">{{ r[c.key] }}</slot>
                        </td>
                        <td v-if="$slots.actions" class="px-2 py-2 text-right"><slot name="actions" :row="r" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Mobile: kartu -->
        <ul class="divide-y divide-neutral-100 md:hidden">
            <li v-for="(r, i) in rows" :key="key(r, i)" class="animate-fade-up space-y-2.5 px-4 py-3.5" :style="{ animationDelay: `${Math.min(i, 8) * 40}ms` }">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 font-semibold text-primary-900">
                        <template v-for="c in columns.filter((x) => x.primary)" :key="c.key"><slot :name="`cell-${c.key}`" :row="r" :value="r[c.key]">{{ r[c.key] }}</slot></template>
                    </div>
                    <slot name="actions" :row="r" />
                </div>
                <dl class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-[13px]">
                    <template v-for="c in columns.filter((x) => !x.primary && x.mobile !== false)" :key="c.key">
                        <dt class="text-neutral-600">{{ c.label }}</dt>
                        <dd :class="['text-right text-neutral-950', c.align === 'right' ? 'num' : '']"><slot :name="`cell-${c.key}`" :row="r" :value="r[c.key]">{{ r[c.key] }}</slot></dd>
                    </template>
                </dl>
            </li>
        </ul>
    </template>
</template>
