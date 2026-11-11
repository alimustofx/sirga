import type { AuthUser, Credentials, NotificationItem } from '@/types';

declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: { user: AuthUser | null };
            school: { name: string; address: string | null; has_logo: boolean } | null;
            notifications: { unread: number; items: NotificationItem[] } | null;
            credentials: Credentials | null;
            [key: string]: unknown;
        };
    }
}
