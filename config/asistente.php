<?php

return [

    'enabled' => env('ASISTENTE_ENABLED', true),

    /*
     * Chat iframe (bot arena-ayuda). En local podés apuntar a
     * http://127.0.0.1:8088/w/arena-ayuda si tenés el chatbot en XAMPP.
     */
    'frame_url' => env(
        'ASISTENTE_FRAME_URL',
        'https://chatbot-production-ab9d.up.railway.app/w/arena-ayuda'
    ),

];
