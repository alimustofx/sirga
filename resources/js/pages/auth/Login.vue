<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppInput from '@/components/app/AppInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ nip: '', password: '', remember: false });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Masuk" />
    <h1 class="page-title">Masuk ke akun Anda</h1>
    <p class="mt-1.5 text-sm text-neutral-600">Gunakan NIP dan password yang diberikan oleh sekolah.</p>

    <form class="mt-7 space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.nip" label="NIP" inputmode="numeric" autocomplete="username" placeholder="Contoh: 196903081993032006" :error="form.errors.nip" required autofocus />
        <AppInput v-model="form.password" label="Password" type="password" autocomplete="current-password" :error="form.errors.password" required />
        <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-neutral-800">
            <input v-model="form.remember" type="checkbox" class="size-5 rounded accent-primary-700" /> Ingat saya di perangkat ini
        </label>
        <AppButton type="submit" size="lg" block :loading="form.processing">Masuk</AppButton>
    </form>
    <p class="mt-6 text-[13px] text-neutral-600">Lupa password? Hubungi Super Admin sekolah untuk mengatur ulang password Anda.</p>
</template>
