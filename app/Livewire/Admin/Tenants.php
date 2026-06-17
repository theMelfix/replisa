<?php

namespace App\Livewire\Admin;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Area super-admin (E4.1.4): elenco di tutti i tenant con possibilità di
 * attivarli/disattivarli. Protetta dal middleware `role:super-admin`.
 */
#[Layout('layouts.app')]
class Tenants extends Component
{
    public function toggle(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update(['active' => ! $tenant->active]);
    }

    public function render(): View
    {
        return view('livewire.admin.tenants', [
            'tenants' => Tenant::withCount(['contacts', 'messages'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
