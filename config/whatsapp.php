<?php

/*
|--------------------------------------------------------------------------
| Stima costi WhatsApp (E4.2.1)
|--------------------------------------------------------------------------
|
| Meta fattura le conversazioni WhatsApp per categoria, non i singoli
| messaggi. Una conversazione business-initiated si apre inviando un template
| fuori dalla finestra di servizio 24h. Questi valori sono STIME in EUR per il
| mercato Italia (fonte: listino Meta "WhatsApp conversation-based pricing"),
| usate solo per dare al tenant un ordine di grandezza in dashboard — la
| fatturazione reale la emette Meta. Vanno riviste quando Meta aggiorna il
| listino (le tariffe cambiano periodicamente e per Paese).
|
*/

return [

    'pricing' => [
        'currency' => 'EUR',

        // Costo stimato per conversazione, per categoria.
        'categories' => [
            'marketing' => 0.0691,
            'utility' => 0.0400,
            'authentication' => 0.0378,
            'service' => 0.0, // conversazioni avviate dall'utente: gratuite
        ],

        // Tariffa usata per la stima aggregata finché non tracciamo la
        // categoria di ogni conversazione: prudenzialmente quella marketing.
        'estimate_rate' => 0.0691,
    ],

];
