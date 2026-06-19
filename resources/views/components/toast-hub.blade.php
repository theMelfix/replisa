{{--
    Toast hub (E4.2.3): notifiche effimere in alto a destra.

    Ascolta l'evento browser `toast`, emesso in due modi:
      - lato server da un componente Livewire:
            $this->dispatch('toast', type: 'error', message: '...');
      - lato client (rete di sicurezza in resources/js/app.js):
            window.dispatchEvent(new CustomEvent('toast', { detail: {...} }));

    detail: { type: 'error'|'success'|'info', message: string, timeout?: ms }
--}}
<div
    x-data="{
        toasts: [],
        add(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({
                id,
                type: detail?.type ?? 'error',
                message: detail?.message ?? 'Si è verificato un errore.',
            });
            setTimeout(() => this.remove(id), detail?.timeout ?? 6000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    }"
    x-on:toast.window="add($event.detail)"
    class="fixed top-4 right-4 z-50 flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2"
    aria-live="assertive"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            role="alert"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="flex items-start gap-3 rounded-lg px-4 py-3 text-sm shadow-lg ring-1"
            :class="{
                'bg-red-50 text-red-800 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-900': toast.type === 'error',
                'bg-green-50 text-green-800 ring-green-200 dark:bg-green-950 dark:text-green-200 dark:ring-green-900': toast.type === 'success',
                'bg-blue-50 text-blue-800 ring-blue-200 dark:bg-blue-950 dark:text-blue-200 dark:ring-blue-900': toast.type === 'info',
            }"
        >
            <span class="flex-1" x-text="toast.message"></span>
            <button type="button" class="shrink-0 opacity-60 hover:opacity-100" @click="remove(toast.id)" aria-label="Chiudi">&times;</button>
        </div>
    </template>
</div>