<script setup lang="ts">
import { DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';

const open = defineModel<boolean>('open', { default: false });
defineProps<{ title: string }>();
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-50 bg-neutral-950/45" />
            <DialogContent class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col bg-primary-900 text-white shadow-modal">
                <DialogTitle class="sr-only">{{ title }}</DialogTitle>
                <DialogDescription class="sr-only">Menu navigasi</DialogDescription>
                <slot />
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
