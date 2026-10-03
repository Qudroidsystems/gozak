<?php

/*
| Customer ↔ agent live chat settings.
| Times are Africa/Lagos wall-clock.
*/
return [
    // Support hours shown in the app when no agent is online.
    'office_hours' => [
        'timezone' => 'Africa/Lagos',
        'days'     => [1, 2, 3, 4, 5, 6],   // ISO weekday: 1 = Mon … 7 = Sun
        'open'     => env('CHAT_OPEN_AT', '08:00'),
        'close'    => env('CHAT_CLOSE_AT', '20:00'),
        'label'    => env('CHAT_HOURS_LABEL', 'Mon–Sat, 8am – 8pm'),
    ],

    // An agent counts as online only if their inbox pinged within this many seconds.
    'presence_ttl' => 180,

    // Default number of chats an agent can hold at once (auto-assign stops at this).
    'max_chats' => 5,

    // Hand new chats to the least-busy online agent automatically.
    'auto_assign' => env('CHAT_AUTO_ASSIGN', true),

    // Close active chats where nobody wrote for this many hours.
    'auto_close_hours' => 24,

    // Image uploads (KB).
    'max_image_kb' => 6144,

    'topics' => [
        'order'    => 'Order issue',
        'delivery' => 'Delivery',
        'payment'  => 'Payment',
        'refund'   => 'Return / refund',
        'product'  => 'Product question',
        'account'  => 'My account',
        'general'  => 'Something else',
    ],
];
