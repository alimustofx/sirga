<script setup lang="ts">
import { answerConfirm, confirmState } from '@/composables/useConfirm';
import AppButton from './AppButton.vue';
import AppDialog from './AppDialog.vue';
</script>

<template>
    <AppDialog :open="confirmState.open" :title="confirmState.options.title" :description="confirmState.options.message" @update:open="(v: boolean) => !v && confirmState.open && answerConfirm(false)">
        <template #footer>
            <AppButton variant="outline" @click="answerConfirm(false)">{{ confirmState.options.cancelLabel ?? 'Batal' }}</AppButton>
            <AppButton :variant="confirmState.options.danger ? 'danger' : 'primary'" @click="answerConfirm(true)">{{ confirmState.options.confirmLabel ?? 'Ya, lanjutkan' }}</AppButton>
        </template>
    </AppDialog>
</template>
