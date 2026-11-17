<!-- Modal Konfirmasi Universal (Pastel, Lega, Kalem) -->
<div
    x-data="confirmDialog()"
    x-cloak
    @open-confirm.window="open($event.detail)"
    x-show="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop Blur Pastel -->
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/35 backdrop-blur-xs transition-opacity"
        @click="cancel()"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-3xl bg-white p-6 sm:p-7 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200/90"
        >
            <div class="flex items-start gap-4">
                <div
                    class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                    :class="type === 'danger' ? 'bg-rose-100/80 text-rose-700' : (type === 'warning' ? 'bg-amber-100/80 text-amber-700' : 'bg-indigo-100/80 text-indigo-700')"
                >
                    <i data-lucide="alert-triangle" class="w-6 h-6" x-show="type === 'danger' || type === 'warning'"></i>
                    <i data-lucide="help-circle" class="w-6 h-6" x-show="type === 'info'"></i>
                </div>
                <div class="flex-1 mt-0.5">
                    <h3 class="text-base font-bold text-slate-800 tracking-tight" id="modal-title" x-text="title"></h3>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed" x-text="message"></p>
                </div>
            </div>

            <div class="mt-6 sm:mt-7 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-4 border-t border-slate-100">
                <button
                    type="button"
                    @click="cancel()"
                    class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-slate-200/90 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-800 transition cursor-pointer"
                    x-text="cancelText"
                ></button>
                <button
                    type="button"
                    @click="confirm()"
                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-semibold text-white shadow-2xs transition cursor-pointer"
                    :class="type === 'danger' ? 'bg-rose-600 hover:bg-rose-700' : (type === 'warning' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-slate-800 hover:bg-slate-900')"
                    x-text="confirmText"
                ></button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDialog() {
    return {
        isOpen: false,
        title: 'Konfirmasi Tindakan',
        message: 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: 'Lanjutkan',
        cancelText: 'Batal',
        type: 'danger', // 'danger' | 'warning' | 'info'
        callback: null,

        open(options) {
            this.title = options.title || 'Konfirmasi';
            this.message = options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            this.confirmText = options.confirmText || 'Ya, Lanjutkan';
            this.cancelText = options.cancelText || 'Batal';
            this.type = options.type || 'danger';
            this.callback = options.onConfirm || null;
            this.isOpen = true;
            this.$nextTick(() => { lucide.createIcons(); });
        },

        confirm() {
            this.isOpen = false;
            if (typeof this.callback === 'function') {
                this.callback();
            }
        },

        cancel() {
            this.isOpen = false;
        }
    }
}

// Hook helper global untuk form delete/submit
window.optimaConfirm = function(event, message, title = 'Konfirmasi Tindakan', type = 'danger') {
    event.preventDefault();
    const form = event.target.closest('form');
    window.dispatchEvent(new CustomEvent('open-confirm', {
        detail: {
            title: title,
            message: message,
            type: type,
            confirmText: 'Ya, Lanjutkan',
            cancelText: 'Batal',
            onConfirm: () => {
                if (form) form.submit();
            }
        }
    }));
    return false;
};
</script>
