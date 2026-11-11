<script setup lang="ts">
import AppButton from '@/components/app/AppButton.vue';
import AppInput from '@/components/app/AppInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps<{ forced: boolean }>();
const form = useForm({ current_password: '', password: '', password_confirmation: '' });
const submit = () => form.put('/ganti-password', { onFinish: () => form.reset() });
</script>

<template>
    <Head title="Ganti Password" />
    <h1 class="page-title">{{ forced ? 'Buat password baru' : 'Ganti password' }}</h1>
    <p class="mt-1.5 text-sm text-neutral-600">
        {{ forced ? 'Demi keamanan, Anda wajib mengganti password awal sebelum melanjutkan.' : 'Gunakan kombinasi huruf dan angka, minimal 8 karakter.' }}
    </p>
    <form class="mt-7 space-y-4" @submit.prevent="submit">
        <AppInput v-model="form.current_password" label="Password saat ini" type="password" autocomplete="current-password" :error="form.errors.current_password" required />
        <AppInput v-model="form.password" label="Password baru" type="password" autocomplete="new-password" hint="Minimal 8 karakter, mengandung huruf dan angka." :error="form.errors.password" required />
        <AppInput v-model="form.password_confirmation" label="Ulangi password baru" type="password" autocomplete="new-password" required />
        <AppButton type="submit" size="lg" block :loading="form.processing">Simpan password</AppButton>
        <AppButton variant="ghost" block @click="router.post('/logout')">Keluar</AppButton>
    </form>
</template>
