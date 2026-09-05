<?php

return [

    'enabled' => env('ASISTENTE_ENABLED', true),

    /*
     * local = ayuda de esta app (entiende sinónimos de Arena Players).
     * iframe = widget del chatbot Railway (arena-ayuda).
     */
    'mode' => env('ASISTENTE_MODE', 'local'),

    'frame_url' => env(
        'ASISTENTE_FRAME_URL',
        'https://chatbot-production-ab9d.up.railway.app/w/arena-ayuda'
    ),

];
