<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppInput from '@/components/app/AppInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowRight, Lock, TriangleAlert, UserRound } from '@lucide/vue';
import { ref } from 'vue';

const form = useForm({ nip: '', password: '', remember: false });
const caps = ref(false);
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
const checkCaps = (e: KeyboardEvent) => { caps.value = e.getModifierState?.('CapsLock') ?? false; };
</script>

<template>
    <Head title="Masuk" />
    <p class="eyebrow mb-2">Akses ASN</p>
    <h1 class="page-title">Masuk ke akun Anda</h1>
    <p class="mt-1.5 text-sm text-neutral-600">Gunakan NIP dan password dari sekolah.</p>

    <form class="mt-8 space-y-4" @submit.prevent="submit" @keyup="checkCaps" @keydown="checkCaps">
        <AppInput v-model="form.nip" label="NIP" :icon="UserRound" inputmode="numeric" autocomplete="username" placeholder="18 digit NIP" :error="form.errors.nip" required autofocus />
        <AppInput v-model="form.password" label="Password" :icon="Lock" type="password" autocomplete="current-password" :error="form.errors.password" required />
        <p v-if="caps" class="flex items-center gap-2 rounded-lg bg-warning-bg px-3 py-2 text-[13px] font-medium text-warning-fg" role="status"><TriangleAlert class="size-4" />Caps Lock sedang aktif.</p>
        <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-neutral-800"><input v-model="form.remember" type="checkbox" class="size-5 rounded accent-primary-700" /> Ingat saya di perangkat ini</label>
        <AppButton type="submit" size="lg" block :loading="form.processing">Masuk<ArrowRight class="size-4" /></AppButton>
    </form>
    <p class="mt-8 border-t border-neutral-200 pt-4 text-[13px] text-neutral-600">Lupa password? Hubungi Super Admin sekolah untuk mengatur ulang.</p>
</template>
