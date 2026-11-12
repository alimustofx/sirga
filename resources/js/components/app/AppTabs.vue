<script setup lang="ts">
const model = defineModel<string>({ required: true });
defineProps<{ tabs: { value: string; label: string; count?: number }[] }>();
</script>

<template>
    <div class="-mx-1 flex gap-1 overflow-x-auto border-b border-neutral-200 px-1" role="tablist">
        <button v-for="t in tabs" :key="t.value" type="button" role="tab" :aria-selected="model === t.value"
            :class="['relative flex min-h-11 shrink-0 items-center gap-2 px-4 text-sm font-semibold transition-colors', model === t.value ? 'text-primary-900 after:absolute after:inset-x-3 after:bottom-[-1px] after:h-0.5 after:rounded-full after:bg-primary-600' : 'text-neutral-600 hover:text-neutral-950']"
            @click="model = t.value">
            {{ t.label }}<span v-if="t.count !== undefined" class="num rounded-md bg-neutral-100 px-1.5 py-0.5 text-[11px] text-neutral-600">{{ t.count }}</span>
        </button>
    </div>
</template>
