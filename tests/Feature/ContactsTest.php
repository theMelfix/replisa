<?php

use App\Livewire\Contacts;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tenant = Tenant::create(['name' => 'A', 'phone_number_id' => 'PA', 'access_token' => 'TA']);
    $this->other = Tenant::create(['name' => 'B', 'phone_number_id' => 'PB', 'access_token' => 'TB']);

    $this->tenant->contacts()->create(['phone' => '393331110001', 'name' => 'Anna']);
    $this->tenant->contacts()->create(['phone' => '393331110002', 'name' => 'Marco']);
    $this->other->contacts()->create(['phone' => '393339990003', 'name' => 'Estraneo']);

    $this->owner = User::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->owner->assignRole(User::ROLE_OWNER);
});

it('reindirizza gli ospiti al login', function () {
    $this->get('/contacts')->assertRedirect(route('login'));
});

it('mostra solo i contatti del proprio tenant', function () {
    $this->actingAs($this->owner);

    Livewire::test(Contacts::class)
        ->assertSee('Anna')
        ->assertSee('Marco')
        ->assertDontSee('Estraneo');
});

it('filtra per ricerca senza far trapelare altri tenant', function () {
    $this->actingAs($this->owner);

    Livewire::test(Contacts::class)
        ->set('search', 'Anna')
        ->assertSee('Anna')
        ->assertDontSee('Marco');
});

it('apre la conversazione di un contatto e ne mostra i messaggi', function () {
    $this->actingAs($this->owner);

    $anna = $this->tenant->contacts()->where('name', 'Anna')->first();
    $anna->messages()->create([
        'tenant_id' => $this->tenant->id,
        'direction' => 'inbound', 'type' => 'text',
        'content' => ['body' => 'Ciao, avete posto?'], 'status' => 'received',
    ]);
    $anna->messages()->create([
        'tenant_id' => $this->tenant->id,
        'direction' => 'outbound', 'type' => 'text',
        'content' => ['body' => 'Sì, quando preferisci?'], 'status' => 'delivered',
    ]);

    Livewire::test(Contacts::class)
        ->call('showConversation', $anna->id)
        ->assertSet('selectedId', $anna->id)
        ->assertSee('Ciao, avete posto?')
        ->assertSee('Sì, quando preferisci?');
});

it('mostra i messaggi in ordine cronologico', function () {
    $this->actingAs($this->owner);

    $anna = $this->tenant->contacts()->where('name', 'Anna')->first();
    $old = $anna->messages()->create([
        'tenant_id' => $this->tenant->id, 'direction' => 'inbound', 'type' => 'text',
        'content' => ['body' => 'primo'], 'status' => 'received',
    ]);
    $old->forceFill(['created_at' => now()->subHour()])->save();
    $anna->messages()->create([
        'tenant_id' => $this->tenant->id, 'direction' => 'outbound', 'type' => 'text',
        'content' => ['body' => 'secondo'], 'status' => 'sent',
    ]);

    $conversation = Livewire::test(Contacts::class)
        ->call('showConversation', $anna->id)
        ->get('conversation');

    expect($conversation->pluck('content.body')->all())->toBe(['primo', 'secondo']);
});

it('non apre la conversazione di un contatto di un altro tenant', function () {
    // Recupero l'id senza scope: da autenticato il TenantScope lo nasconderebbe.
    $estraneoId = Contact::withoutGlobalScopes()->where('name', 'Estraneo')->value('id');

    $this->actingAs($this->owner);

    Livewire::test(Contacts::class)
        ->call('showConversation', $estraneoId)
        ->assertSet('selectedId', null);
});

it('chiude il pannello conversazione', function () {
    $this->actingAs($this->owner);

    $anna = $this->tenant->contacts()->where('name', 'Anna')->first();

    Livewire::test(Contacts::class)
        ->call('showConversation', $anna->id)
        ->assertSet('selectedId', $anna->id)
        ->call('closeConversation')
        ->assertSet('selectedId', null);
});

it('rende leggibili i vari tipi di contenuto', function () {
    $this->actingAs($this->owner);

    $anna = $this->tenant->contacts()->where('name', 'Anna')->first();
    $anna->messages()->create([
        'tenant_id' => $this->tenant->id, 'direction' => 'outbound', 'type' => 'template',
        'content' => ['template' => 'appointment_reminder'], 'status' => 'sent',
    ]);
    $anna->messages()->create([
        'tenant_id' => $this->tenant->id, 'direction' => 'inbound', 'type' => 'button',
        'content' => ['text' => 'Confermo'], 'status' => 'received',
    ]);

    Livewire::test(Contacts::class)
        ->call('showConversation', $anna->id)
        ->assertSee('Modello: appointment_reminder')
        ->assertSee('Confermo');
});

it('assegna una nuova etichetta al contatto aperto', function () {
    $this->actingAs($this->owner);
    $anna = $this->tenant->contacts()->where('name', 'Anna')->first();

    Livewire::test(Contacts::class)
        ->call('showConversation', $anna->id)
        ->set('newTag', 'VIP')
        ->call('addTag')
        ->assertSet('newTag', '');

    expect($anna->fresh()->tags->pluck('name')->all())->toBe(['VIP']);
    expect(Tag::withoutGlobalScopes()->where('name', 'VIP')->where('tenant_id', $this->tenant->id)->exists())->toBeTrue();
});

it('riusa un\'etichetta esistente invece di duplicarla', function () {
    $this->actingAs($this->owner);
    $tag = Tag::create(['name' => 'Cliente']);
    $anna = $this->tenant->contacts()->where('name', 'Anna')->first();

    Livewire::test(Contacts::class)
        ->call('showConversation', $anna->id)
        ->set('newTag', 'Cliente')
        ->call('addTag');

    expect(Tag::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->count())->toBe(1);
    expect($anna->fresh()->tags->pluck('id')->all())->toBe([$tag->id]);
});

it('rimuove un\'etichetta dal contatto', function () {
    $this->actingAs($this->owner);
    $tag = Tag::create(['name' => 'Temp']);
    $anna = $this->tenant->contacts()->where('name', 'Anna')->first();
    $anna->tags()->attach($tag->id);

    Livewire::test(Contacts::class)
        ->call('showConversation', $anna->id)
        ->call('removeTag', $tag->id);

    expect($anna->fresh()->tags)->toHaveCount(0);
});

it('filtra la lista contatti per etichetta', function () {
    $this->actingAs($this->owner);
    $tag = Tag::create(['name' => 'VIP']);
    $anna = $this->tenant->contacts()->where('name', 'Anna')->first();
    $anna->tags()->attach($tag->id);

    Livewire::test(Contacts::class)
        ->set('tagFilter', (string) $tag->id)
        ->assertSee('Anna')
        ->assertDontSee('Marco');
});
