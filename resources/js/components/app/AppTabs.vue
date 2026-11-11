<script setup lang="ts">
const model = defineModel<string>({ required: true });
defineProps<{ tabs: { value: string; label: string }[] }>();
</script>

<template>
    <div class="-mx-1 flex gap-1 overflow-x-auto border-b border-neutral-200 px-1" role="tablist">
        <button
            v-for="t in tabs"
            :key="t.value"
            type="button"
            role="tab"
            :aria-selected="model === t.value"
            :class="['min-h-11 shrink-0 border-b-2 px-4 text-sm font-semibold transition-colors', model === t.value ? 'border-primary-600 text-primary-900' : 'border-transparent text-neutral-600 hover:text-neutral-950']"
            @click="model = t.value"
        >
            {{ t.label }}
        </button>
    </div>
</template>
