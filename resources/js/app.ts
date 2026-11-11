import { createInertiaApp } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import TeacherLayout from '@/layouts/TeacherLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'SIRGA ASN';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        if (name.startsWith('auth/')) {
            return AuthLayout;
        }
        if (name.startsWith('admin/')) {
            return AdminLayout;
        }
        if (name.startsWith('guru/')) {
            return TeacherLayout;
        }

        return null;
    },
    progress: {
        color: '#287B8C',
    },
});

// Menampilkan toast dari flash data server (Inertia::flash('toast', ...)).
initializeFlashToast();
