<?php

return [
    // New customers require delivery verification unless explicitly disabled.
    'delivery_pin_required' => env('DAIRYNEX_DELIVERY_PIN_REQUIRED', true),
];
