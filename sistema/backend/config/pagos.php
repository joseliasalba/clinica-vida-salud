<?php

return [
    'habilitado' => (bool) env('PAGOS_HABILITADOS', false),
    'stripe_secret' => env('STRIPE_SECRET_KEY'),
    'stripe_public' => env('STRIPE_PUBLISHABLE_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'conciliacion_secret' => env('PAGOS_CONCILIACION_SECRET'),
    'account_id' => env('STRIPE_ACCOUNT_ID'),
    'moneda' => 'bob',
    'minutos_retencion' => 30,
];
