<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Estrae e decodifica il blocco JSON-LD dalla landing. */
function landingJsonLd(): array
{
    $html = test()->get('/')->assertOk()->getContent();

    expect($html)->toContain('application/ld+json');

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    $data = json_decode($m[1] ?? '', true);

    expect($data)->toBeArray()->not->toBeNull();

    return $data;
}

it('include un blocco JSON-LD valido nella landing', function () {
    $data = landingJsonLd();

    expect($data['@context'])->toBe('https://schema.org')
        ->and($data['@graph'])->toBeArray();
});

it('descrive Organization, WebSite e SoftwareApplication', function () {
    $types = collect(landingJsonLd()['@graph'])->pluck('@type');

    expect($types)->toContain('Organization')
        ->toContain('WebSite')
        ->toContain('SoftwareApplication');
});

it('espone le offerte dei piani coi prezzi da config', function () {
    $graph = collect(landingJsonLd()['@graph']);
    $app = $graph->firstWhere('@type', 'SoftwareApplication');

    $offers = collect($app['offers']['offers']);

    expect($app['offers']['@type'])->toBe('AggregateOffer')
        ->and($offers)->toHaveCount(count(config('plans.plans')))
        ->and($app['offers']['lowPrice'])->toBe((string) collect(config('plans.plans'))->min('price'))
        ->and($app['offers']['highPrice'])->toBe((string) collect(config('plans.plans'))->max('price'));

    // Ogni piano di config è rappresentato con nome e prezzo.
    foreach (config('plans.plans') as $plan) {
        $offer = $offers->firstWhere('name', $plan['name']);
        expect($offer)->not->toBeNull()
            ->and($offer['price'])->toBe((string) $plan['price'])
            ->and($offer['priceCurrency'])->toBe('EUR');
    }
});

it('espone i meta tag Open Graph e canonical', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('rel="canonical"', false)
        ->assertSee('property="og:url"', false)
        ->assertSee('name="twitter:card"', false);
});
