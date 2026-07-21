{{--
    Banner cookie GDPR (E5.1.4). Il sito usa esclusivamente cookie tecnici
    (sessione, sicurezza), esenti da consenso preventivo ai sensi dell'art. 122
    Codice Privacy / ePrivacy: il banner è quindi INFORMATIVO, con presa visione.
    Quando si aggiungeranno cookie di misurazione/terze parti, va evoluto in
    accept/reject granulare (vedi privacy §8).

    Autonomo di proposito (CSS + JS inline, niente Tailwind/Alpine): così rende
    identico sulla landing, sulle pagine auth e sul layout legale, che non
    caricano lo stesso CSS. La presa visione è persistita in localStorage
    (non un cookie: nessun paradosso "un cookie per ricordare i cookie").
--}}
<div id="replisa-cookie-banner" role="dialog" aria-live="polite" aria-label="Informativa cookie" hidden>
    <div class="rcb-inner">
        <p class="rcb-text">
            Usiamo solo <strong>cookie tecnici</strong> necessari al funzionamento del sito.
            Nessun cookie di profilazione o di terze parti.
            <a href="/privacy#cookie">Maggiori informazioni</a>.
        </p>
        <button type="button" id="replisa-cookie-accept" class="rcb-btn">Ho capito</button>
    </div>

    <style>
        #replisa-cookie-banner {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 60;
            background: #ffffff; border-top: 1px solid #e6e6ef;
            box-shadow: 0 -4px 24px rgba(15, 23, 42, .08);
            font: 14px/1.55 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1a1a2e;
        }
        #replisa-cookie-banner .rcb-inner {
            max-width: 1100px; margin: 0 auto; padding: 14px 20px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        #replisa-cookie-banner .rcb-text { margin: 0; flex: 1 1 320px; }
        #replisa-cookie-banner .rcb-text a { color: #16a34a; text-decoration: underline; }
        #replisa-cookie-banner .rcb-btn {
            flex: 0 0 auto; cursor: pointer; border: 0; border-radius: 8px;
            background: #16a34a; color: #fff; font-weight: 600; font-size: 14px;
            padding: 10px 20px;
        }
        #replisa-cookie-banner .rcb-btn:hover { background: #15803d; }
        @media (prefers-color-scheme: dark) {
            #replisa-cookie-banner { background: #1f2937; border-top-color: #374151; color: #e5e7eb; }
        }
    </style>

    <script>
        (function () {
            var KEY = 'replisa-cookie-consent-v1';
            var banner = document.getElementById('replisa-cookie-banner');
            if (!banner) return;

            var stored;
            try { stored = window.localStorage.getItem(KEY); } catch (e) { stored = 'blocked'; }

            // Se localStorage è accessibile e non c'è ancora la presa visione, mostra il banner.
            if (stored === null) {
                banner.hidden = false;
            }

            var btn = document.getElementById('replisa-cookie-accept');
            if (btn) {
                btn.addEventListener('click', function () {
                    try { window.localStorage.setItem(KEY, new Date().toISOString()); } catch (e) {}
                    banner.hidden = true;
                });
            }
        })();
    </script>
</div>
