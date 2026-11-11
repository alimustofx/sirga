import { toast } from 'vue-sonner';

export async function copyText(text: string, label = 'Teks'): Promise<void> {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            const el = document.createElement('textarea');
            el.value = text;
            el.setAttribute('readonly', '');
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        }
        toast.success(`${label} berhasil disalin.`);
    } catch {
        toast.error(`${label} gagal disalin. Salin secara manual.`);
    }
}
