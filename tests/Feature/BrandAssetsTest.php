<?php

/**
 * Gli asset di brand sono file generati (`php brand/generate-brand-assets.php`),
 * non codice: nessuno se ne accorge se spariscono o se un tag torna a puntare
 * al file sbagliato. Questi test tengono insieme le due cose.
 */
it('serve il logo dell\'app come vettoriale', function () {
    expect(public_path('images/logo.svg'))->toBeReadableFile();

    $svg = file_get_contents(public_path('images/logo.svg'));

    expect($svg)->toContain('<svg')
        ->and(simplexml_load_string($svg))->not->toBeFalse();
});

it('genera tutti i formati di icona referenziati dai layout', function (string $file, int $width, int $height) {
    $path = public_path($file);

    expect($path)->toBeReadableFile();

    $size = getimagesize($path);

    expect($size[0])->toBe($width)
        ->and($size[1])->toBe($height);
})->with([
    ['favicon-16x16.png', 16, 16],
    ['favicon-32x32.png', 32, 32],
    ['apple-touch-icon.png', 180, 180],
    ['android-chrome-192x192.png', 192, 192],
    ['android-chrome-512x512.png', 512, 512],
    ['og-image.png', 1200, 630],
]);

it('dichiara l\'anteprima social nel formato che le piattaforme si aspettano', function () {
    // Regressione: og:image puntava ad apple-touch-icon.png (180×180) e la
    // condivisione usciva come un quadratino invece che come una card.
    $this->get('/')
        ->assertOk()
        ->assertSee('property="og:image" content="'.url('/og-image.png').'"', false)
        ->assertSee('name="twitter:image" content="'.url('/og-image.png').'"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        ->assertDontSee('property="og:image" content="'.url('/apple-touch-icon.png').'"', false);
});

it('dichiara le dimensioni della card, che alcune piattaforme leggono prima di scaricarla', function () {
    $size = getimagesize(public_path('og-image.png'));

    $this->get('/')
        ->assertSee('property="og:image:width" content="'.$size[0].'"', false)
        ->assertSee('property="og:image:height" content="'.$size[1].'"', false);
});
