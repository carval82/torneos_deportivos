<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$help = app(App\Services\ArenaHelpDesk::class);

$expect = [
    'como entro de jugador' => 'Entrar como jugador',
    'entrar con la cedula' => 'Entrar como jugador',
    'ver mis fechas' => 'Entrar como jugador',
    'soy el dt del equipo' => 'Entrar como delegado',
    'como cargo la nomina' => 'Cargar la plantilla',
    'quienes juegan en mi equipo' => 'Cargar la plantilla',
    'foto del dni' => 'Cargar la plantilla',
    'el fixture lo armo yo' => 'Armar el fixture a mano',
    'quiero cargar los partidos a mano' => 'Armar el fixture a mano',
    'partido por partido' => 'Armar el fixture a mano',
    'programar un partido yo' => 'Armar el fixture a mano',
    'se corto la luz en una cancha' => 'Aplazar un solo partido',
    'correr solo un partido' => 'Aplazar un solo partido',
    'mover ese encuentro' => 'Aplazar un solo partido',
    'llovio y se cayo la fecha' => 'Aplazar toda la fecha',
    'postergar toda la jornada' => 'Aplazar toda la fecha',
    'cancha mojada' => 'Aplazar toda la fecha',
    'cuanto sale un torneo' => 'Cuánto cuesta un torneo',
    'es gratis el primero' => 'Cuánto cuesta un torneo',
    'quiero evadir con otra cuenta' => 'Crear cuenta de organizador',
    'cedula repetida' => 'Crear cuenta de organizador',
    'ya vencio el torneo' => 'Temporada vencida o renovar',
    'como renuevo la temporada' => 'Temporada vencida o renovar',
    'quien carga el resultado' => 'Cargar resultado, goles y W.O.',
    'el equipo no se presento' => 'Cargar resultado, goles y W.O.',
    'terna de arbitros' => 'Árbitros y terna',
    'asignar el referi' => 'Árbitros y terna',
    'ver la tabla' => 'Tabla, goleadores y fixture público',
    'descargar el apk' => 'Descargar la app',
    'olvide la contrasena del delegado' => 'Contraseña inicial',
    'generar el calendario solo' => 'Generar el fixture automático',
];

$fail = 0;
foreach ($expect as $q => $title) {
    $got = $help->ask($q)['title'];
    $ok = $got === $title;
    if (! $ok) {
        $fail++;
    }
    echo ($ok ? 'OK  ' : 'FAIL')."  {$q}  →  {$got}".($ok ? '' : " (esperaba {$title})").PHP_EOL;
}

echo PHP_EOL.($fail === 0 ? 'TODOS OK' : "FALLARON {$fail}").PHP_EOL;
exit($fail === 0 ? 0 : 1);
