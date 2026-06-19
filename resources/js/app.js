import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Bundling manuale di Livewire/Alpine dentro Vite: lo script di Livewire diventa
// un asset statico in public/build (servito come qualsiasi JS), così non
// dipendiamo dalla route dinamica /livewire/livewire.js (problematica dietro
// Varnish/CloudPanel). Vedi deploy/README.

// Rete di sicurezza UI (E4.2.3): un'eccezione non gestita in un'azione Livewire
// restituisce un 500. Invece dell'overlay d'errore di Livewire, mostriamo un
// toast generico in alto a destra (vedi <x-toast-hub />). Il messaggio reale
// resta nei log del server e non viene esposto al client in produzione. Gli
// errori di dominio "attesi" sono invece gestiti lato server con un toast
// specifico via $this->dispatch('toast', ...).
Livewire.hook('request', ({ fail }) => {
    fail(({ status, preventDefault }) => {
        if (status >= 500) {
            preventDefault();
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: 'error', message: 'Si è verificato un errore. Riprova tra poco.' },
            }));
        }
    });
});

Livewire.start();
