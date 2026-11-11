<script setup lang="ts">
import { X } from '@lucide/vue';
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';

const open = defineModel<boolean>('open', { default: false });
withDefaults(defineProps<{ title: string; description?: string; size?: 'md' | 'lg' | 'xl' }>(), { size: 'md' });
const widths = { md: 'sm:max-w-lg', lg: 'sm:max-w-2xl', xl: 'sm:max-w-4xl' };
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-neutral-950/45 data-[state=open]:animate-in data-[state=open]:fade-in-0" />
            <DialogContent
                :class="['fixed inset-0 z-50 flex max-h-dvh w-full flex-col overflow-hidden bg-white shadow-modal sm:inset-auto sm:top-1/2 sm:left-1/2 sm:max-h-[90dvh] sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-[14px]', widths[size]]"
            >
                <div class="flex items-start justify-between gap-3 border-b border-neutral-200 px-5 py-4">
                    <div>
                        <DialogTitle class="text-lg font-semibold text-primary-900">{{ title }}</DialogTitle>
                        <DialogDescription v-if="description" class="mt-1 text-sm text-neutral-600">{{ description }}</DialogDescription>
                        <DialogDescription v-else class="sr-only">{{ title }}</DialogDescription>
                    </div>
                    <DialogClose class="-mr-2 flex size-10 shrink-0 items-center justify-center rounded-lg text-neutral-600 hover:bg-neutral-100" aria-label="Tutup">
                        <X class="size-5" />
                    </DialogClose>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-4"><slot /></div>
                <div v-if="$slots.footer" class="flex flex-col-reverse gap-2 border-t border-neutral-200 bg-neutral-50 px-5 py-3 sm:flex-row sm:justify-end"><slot name="footer" /></div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
