import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Bundling manuale di Livewire/Alpine dentro Vite: lo script di Livewire diventa
// un asset statico in public/build (servito come qualsiasi JS), così non
// dipendiamo dalla route dinamica /livewire/livewire.js (problematica dietro
// Varnish/CloudPanel). Vedi deploy/README.
Livewire.start();
