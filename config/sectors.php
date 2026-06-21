<?php

/*
|--------------------------------------------------------------------------
| Settori attività (E3.2.6)
|--------------------------------------------------------------------------
|
| Categoria merceologica del tenant. Determina quali scadenze nazionali sono
| rilevanti (es. le fiscali IMU/730 valgono per i commercialisti, non per un
| dentista). Una scadenza nazionale senza settore vale per tutti.
|
*/

return [
    'commercialista' => 'Commercialista / CAF / Patronato',
    'studio_medico' => 'Studio medico / dentistico',
    'studio_legale' => 'Studio legale',
    'benessere' => 'Parrucchiere / Estetista',
    'ristorazione' => 'Ristorazione',
    'altro' => 'Altro',
];
