<?php

/**
 * Genera tutti gli asset di brand Replisa a partire da un solo marchio vettoriale.
 *
 * Il marchio è una bolla di messaggio con una spunta ricavata in negativo: la
 * bolla dice "WhatsApp", la spunta dice la cosa specifica che fa Replisa — il
 * promemoria che torna indietro confermato — ed è lo stesso segno con cui
 * WhatsApp indica un messaggio consegnato.
 *
 * Sostituisce il vecchio `generate-icon.php`, che disegnava una "R" in DejaVu
 * Sans dentro un quadrato e produceva **solo** l'icona per Meta: gli altri
 * formati venivano poi ricavati a mano da quel PNG, ridimensionandolo.
 *
 * Uso:  php brand/generate-brand-assets.php
 * Richiede: estensione Imagick con delegato SVG (verificato all'avvio).
 */

// ---------------------------------------------------------------- palette

const BRAND = '#16A34A';       // verde brand
const INK = '#14231A';         // verde-nero, sfondo della card social
const MUTED = '#8FA096';       // verde-grigio per il testo secondario
const WHITE = '#FFFFFF';

// ---------------------------------------------------------------- marchio

/**
 * Bolla di messaggio. Riquadro utile: x 6..94, y 12..94 (la coda scende
 * sotto il corpo, che finisce a y 78).
 */
const BUBBLE = 'M22 12H78A16 16 0 0 1 94 28V62A16 16 0 0 1 78 78H46L26 94V78H22A16 16 0 0 1 6 62V28A16 16 0 0 1 22 12Z';

/**
 * Spunta, come spezzata di tre punti al centro del corpo della bolla.
 *
 * Viene convertita in un tracciato **pieno** da {@see thickPolyline()} invece di
 * essere disegnata con uno `stroke`: il renderer SVG interno di ImageMagick (qui
 * non c'è il delegato librsvg) riempie i tracciati ma **ignora gli stroke senza
 * segnalare nulla**, e la prima versione di questo marchio è uscita con la bolla
 * e senza la spunta. Un contorno pieno si comporta allo stesso modo ovunque.
 */
const CHECK_POINTS = [[31, 45], [44, 58], [69, 31]];

const CHECK_WIDTH = 11;

/**
 * Trasforma una spezzata in un poligono chiuso di spessore `$width`, con giunto
 * a punta e estremità arrotondate — cioè quello che `stroke-linejoin="miter"` e
 * `stroke-linecap="round"` produrrebbero, ma come geometria vera.
 *
 * @param  array<int, array{0: float, 1: float}>  $points
 */
function thickPolyline(array $points, float $width): string
{
    $h = $width / 2;

    [$a, $b, $c] = $points;

    $unit = function (array $from, array $to): array {
        $dx = $to[0] - $from[0];
        $dy = $to[1] - $from[1];
        $len = sqrt($dx * $dx + $dy * $dy);

        return [$dx / $len, $dy / $len];
    };

    $d1 = $unit($a, $b);
    $d2 = $unit($b, $c);

    // Normale sinistra di ciascun segmento.
    $n1 = [-$d1[1], $d1[0]];
    $n2 = [-$d2[1], $d2[0]];

    // Punto di giunzione su un lato: incrocio delle due rette traslate.
    $joint = function (float $side) use ($a, $b, $d1, $d2, $n1, $n2, $h): array {
        $p = [$a[0] + $side * $h * $n1[0], $a[1] + $side * $h * $n1[1]];
        $q = [$b[0] + $side * $h * $n2[0], $b[1] + $side * $h * $n2[1]];

        // p + t·d1 = q + u·d2
        $den = $d1[0] * $d2[1] - $d1[1] * $d2[0];
        $t = (($q[0] - $p[0]) * $d2[1] - ($q[1] - $p[1]) * $d2[0]) / $den;

        return [$p[0] + $t * $d1[0], $p[1] + $t * $d1[1]];
    };

    $num = fn (float $v): string => rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');
    $pt = fn (array $p): string => $num($p[0]).' '.$num($p[1]);

    $start = [$a[0] + $h * $n1[0], $a[1] + $h * $n1[1]];
    $endOuter = [$c[0] + $h * $n2[0], $c[1] + $h * $n2[1]];
    $endInner = [$c[0] - $h * $n2[0], $c[1] - $h * $n2[1]];
    $backInner = [$a[0] - $h * $n1[0], $a[1] - $h * $n1[1]];

    // Le calotte sono semicerchi di raggio $h attorno agli estremi della spezzata.
    return sprintf(
        'M%s L%s L%s A%s %s 0 0 0 %s L%s L%s A%s %s 0 0 0 %s Z',
        $pt($start),
        $pt($joint(1)),
        $pt($endOuter),
        $num($h), $num($h),
        $pt($endInner),
        $pt($joint(-1)),
        $pt($backInner),
        $num($h), $num($h),
        $pt($start),
    );
}

/**
 * Marchio dentro una piastrella quadrata: bolla bianca su verde, con la spunta
 * tracciata nel verde della piastrella così da leggersi come un ritaglio.
 *
 * `$radius` in unità della griglia 0-100. Le favicon vogliono gli angoli già
 * arrotondati; le icone di iOS e Android no, perché il sistema operativo
 * applica la propria maschera e arrotondare due volte taglia il disegno.
 */
function tileSvg(int $px, float $radius, float $scale = 0.6): string
{
    // Il marchio è centrato sul suo riquadro utile dentro la piastrella.
    $tx = (100 - 88 * $scale) / 2 - 6 * $scale;
    $ty = (100 - 82 * $scale) / 2 - 12 * $scale;

    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="%1$d" height="%1$d" role="img" aria-label="Replisa">'
        .'<rect width="100" height="100" rx="%2$s" fill="%3$s"/>'
        .'<g transform="translate(%4$s %5$s) scale(%6$s)">'
        .'<path fill="%7$s" d="%8$s"/>'
        .'<path fill="%3$s" d="%9$s"/>'
        .'</g></svg>',
        $px,
        rtrim(rtrim(number_format($radius, 2, '.', ''), '0'), '.'),
        BRAND,
        rtrim(rtrim(number_format($tx, 2, '.', ''), '0'), '.'),
        rtrim(rtrim(number_format($ty, 2, '.', ''), '0'), '.'),
        $scale,
        WHITE,
        BUBBLE,
        thickPolyline(CHECK_POINTS, CHECK_WIDTH),
    );
}

/**
 * Marchio nudo, senza piastrella: bolla verde con spunta bianca, ritagliato sul
 * riquadro utile così che chi lo inserisce non debba compensare margini vuoti.
 */
function markSvg(int $px = 0): string
{
    $size = $px ? sprintf(' width="%d"', $px) : '';

    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="6 12 88 82"%1$s role="img" aria-label="Replisa">'
        .'<path fill="%2$s" d="%3$s"/>'
        .'<path fill="%4$s" d="%5$s"/>'
        .'</svg>',
        $size,
        BRAND,
        BUBBLE,
        WHITE,
        thickPolyline(CHECK_POINTS, CHECK_WIDTH),
    );
}

// ---------------------------------------------------------------- utilità

function rasterize(string $svg): Imagick
{
    $im = new Imagick;
    $im->setBackgroundColor(new ImagickPixel('transparent'));
    $im->readImageBlob($svg);
    $im->setImageFormat('png32');

    return $im;
}

function writePng(string $svg, string $path): void
{
    $im = rasterize($svg);
    $im->writeImage($path);
    $im->clear();

    report($path);
}

function report(string $path): void
{
    $size = getimagesize($path);
    $dim = $size ? $size[0].'×'.$size[1] : 'vettoriale';
    printf("  %-44s %-12s %s KB\n", str_replace(dirname(__DIR__).'/', '', $path), $dim, round(filesize($path) / 1024, 1));
}

function fontPath(): string
{
    // Sul sito il nome è in Figtree; se il file è a portata di mano lo usiamo,
    // altrimenti si ripiega su un grottesco di sistema. Riguarda solo la card
    // social: nell'app il testo resta HTML e usa il font vero.
    $candidates = [
        __DIR__.'/Figtree-Bold.ttf',
        '/usr/share/fonts/truetype/ubuntu/Ubuntu-B.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    ];

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    fwrite(STDERR, "Nessun font disponibile per la card social.\n");
    exit(1);
}

// ---------------------------------------------------------------- avvio

if (! extension_loaded('imagick')) {
    fwrite(STDERR, "Serve l'estensione Imagick.\n");
    exit(1);
}

if (! Imagick::queryFormats('SVG')) {
    fwrite(STDERR, "Imagick non sa leggere gli SVG (manca il delegato).\n");
    exit(1);
}

$public = dirname(__DIR__).'/public';
$images = $public.'/images';

echo "Marchio vettoriale\n";

// Sorgente dell'interfaccia: sidebar, topbar, login, pagina "sospeso".
file_put_contents($images.'/logo.svg', tileSvg(512, 22)."\n");
report($images.'/logo.svg');

// Marchio nudo, per chi ha bisogno del segno senza piastrella.
file_put_contents($images.'/logo-mark.svg', markSvg()."\n");
report($images.'/logo-mark.svg');

echo "\nFavicon (angoli arrotondati: le disegna il browser così come sono)\n";

// Scalatura ottica: sotto i 48 px il marchio va disegnato più grande dentro la
// piastrella, altrimenti la spunta si chiude e resta una macchia bianca. È la
// stessa ragione per cui un carattere da testo non è il suo titolo rimpicciolito.
const TINY_SCALE = 0.72;

writePng(tileSvg(16, 22, TINY_SCALE), $public.'/favicon-16x16.png');
writePng(tileSvg(32, 22, TINY_SCALE), $public.'/favicon-32x32.png');

$ico = new Imagick;
foreach ([16, 32, 48] as $size) {
    $ico->addImage(rasterize(tileSvg($size, 22, $size < 48 ? TINY_SCALE : 0.6)));
}
$ico->setFormat('ico');
$ico->writeImages($public.'/favicon.ico', true);
$ico->clear();
report($public.'/favicon.ico');

echo "\nIcone di sistema (angoli vivi: la maschera la mette il sistema operativo)\n";

writePng(tileSvg(180, 0), $public.'/apple-touch-icon.png');
writePng(tileSvg(192, 0), $public.'/android-chrome-192x192.png');
writePng(tileSvg(512, 0), $public.'/android-chrome-512x512.png');

// Icona app richiesta da Meta per la configurazione dell'app WhatsApp.
writePng(tileSvg(1024, 0), __DIR__.'/replisa-icon-1024.png');

// Raster della piastrella, per la brochure e per chi non regge l'SVG.
writePng(tileSvg(512, 22), $images.'/logo.png');

// ---------------------------------------------------------------- card social

echo "\nAnteprima social\n";

$og = new Imagick;
$og->newImage(1200, 630, new ImagickPixel(INK));
$og->setImageFormat('png32');

// Il marchio sta a destra, intero e grande: nel feed la card si vede piccola,
// e un logo tagliato a metà si legge come un disturbo, non come un logo.
$tile = rasterize(tileSvg(264, 22));
$og->compositeImage($tile, Imagick::COMPOSITE_OVER, 1200 - 96 - 264, (630 - 264) / 2);
$tile->clear();

$font = fontPath();

$line = new ImagickDraw;
$line->setFont($font);
$line->setFillColor(new ImagickPixel(WHITE));
$line->setFontSize(96);
$og->annotateImage($line, 96, 290, 0, 'Replisa');

$line->setFillColor(new ImagickPixel(MUTED));
$line->setFontSize(36);
$og->annotateImage($line, 96, 352, 0, 'Automazione WhatsApp');
$og->annotateImage($line, 96, 398, 0, 'per le piccole imprese');

$line->setFillColor(new ImagickPixel(BRAND));
$line->setFontSize(30);
$og->annotateImage($line, 96, 468, 0, 'replisa.com');

$og->writeImage($public.'/og-image.png');
$og->clear();
report($public.'/og-image.png');

// ---------------------------------------------------------------- brochure

echo "\nBrochure\n";

$brochure = __DIR__.'/brochure.html';
$html = file_get_contents($brochure);
$payload = base64_encode(file_get_contents($images.'/logo.png'));

$updated = preg_replace(
    '#data:image/png;base64,[A-Za-z0-9+/=]+#',
    'data:image/png;base64,'.$payload,
    $html,
    -1,
    $count,
);

file_put_contents($brochure, $updated);
printf("  brand/brochure.html%s%d loghi incorporati aggiornati\n", str_repeat(' ', 26), $count);

echo "\nFatto.\n";
