<script setup lang="ts">
import { DropdownMenuContent, DropdownMenuItem, DropdownMenuPortal, DropdownMenuRoot, DropdownMenuTrigger } from 'reka-ui';

export type DropdownItem = { label: string; action: () => void; danger?: boolean; hidden?: boolean };
defineProps<{ items: DropdownItem[]; align?: 'start' | 'center' | 'end'; label?: string }>();
</script>

<template>
    <DropdownMenuRoot>
        <DropdownMenuTrigger as-child :aria-label="label ?? 'Menu aksi'"><slot name="trigger" /></DropdownMenuTrigger>
        <DropdownMenuPortal>
            <DropdownMenuContent :align="align ?? 'end'" :side-offset="6" class="z-50 min-w-48 rounded-lg border border-neutral-200 bg-white p-1 shadow-modal">
                <slot name="header" />
                <template v-for="it in items" :key="it.label">
                    <DropdownMenuItem
                        v-if="!it.hidden"
                        :class="['flex min-h-10 cursor-pointer items-center rounded-md px-3 text-sm outline-none data-[highlighted]:bg-neutral-100', it.danger ? 'text-danger-fg' : 'text-neutral-950']"
                        @select="it.action()"
                    >
                        {{ it.label }}
                    </DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
