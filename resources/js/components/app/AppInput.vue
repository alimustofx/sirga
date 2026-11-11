<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppField from './AppField.vue';

const model = defineModel<string | number | null>();
const props = defineProps<{
    label?: string;
    error?: string | string[];
    hint?: string;
    type?: string;
    placeholder?: string;
    required?: boolean;
    autocomplete?: string;
    inputmode?: 'text' | 'numeric' | 'decimal' | 'email' | 'search';
    disabled?: boolean;
    prefix?: string;
    maxlength?: number;
    autofocus?: boolean;
}>();

const show = ref(false);
const isPassword = computed(() => props.type === 'password');
const inputType = computed(() => (isPassword.value && show.value ? 'text' : props.type || 'text'));
const base =
    'h-11 w-full rounded-lg border bg-white px-3 text-sm text-neutral-950 placeholder:text-neutral-400 transition-colors focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20 focus:outline-none disabled:bg-neutral-100 disabled:text-neutral-600 sm:h-10';
</script>

<template>
    <AppField v-slot="{ id, invalid }" :label="label" :error="error" :hint="hint" :required="required">
        <div class="relative">
            <span v-if="prefix" class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-neutral-600">{{ prefix }}</span>
            <input
                :id="id"
                v-model="model"
                :type="inputType"
                :placeholder="placeholder"
                :required="required"
                :autocomplete="autocomplete"
                :inputmode="inputmode"
                :disabled="disabled"
                :maxlength="maxlength"
                :autofocus="autofocus"
                :aria-invalid="invalid"
                :class="[base, invalid ? 'border-[#dc2626]' : 'border-neutral-200', prefix ? 'pl-10' : '', isPassword ? 'pr-11' : '']"
            />
            <button
                v-if="isPassword"
                type="button"
                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-neutral-600 hover:text-neutral-950"
                :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                @click="show = !show"
            >
                <EyeOff v-if="show" class="size-4" />
                <Eye v-else class="size-4" />
            </button>
        </div>
    </AppField>
</template>
