<?php

namespace App\Http\Controllers;

use App\Models\ReviewClick;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Short-link recensioni (E3.3.3): rotta pubblica `/r/{token}` che registra il
 * click e reindirizza all'URL recensioni Google del tenant. È il modo per
 * tracciare i click, che sui button URL dei template Meta non passano dal webhook.
 */
class ReviewRedirectController extends Controller
{
    public function __invoke(string $token): RedirectResponse
    {
        // Rotta pubblica (nessun auth) → TenantScope no-op; il token è unico.
        $link = ReviewClick::where('token', $token)->first();

        if (! $link) {
            throw new NotFoundHttpException;
        }

        $link->registerClick();

        // 302: il link nel template resta valido per più click (rate/analytics).
        return redirect()->away($link->destination_url);
    }
}
