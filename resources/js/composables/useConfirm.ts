import { reactive } from 'vue';

type ConfirmOptions = {
    title: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    danger?: boolean;
};

export const confirmState = reactive({
    open: false,
    options: { title: '' } as ConfirmOptions,
    resolve: null as ((v: boolean) => void) | null,
});

/** Dialog konfirmasi global (aksi penting: publikasi, tutup periode, nonaktifkan akun, dst). */
export function confirmAction(options: ConfirmOptions): Promise<boolean> {
    confirmState.options = options;
    confirmState.open = true;

    return new Promise((resolve) => {
        confirmState.resolve = resolve;
    });
}

export function answerConfirm(value: boolean): void {
    confirmState.open = false;
    confirmState.resolve?.(value);
    confirmState.resolve = null;
}
