<?php

namespace App\Support;

class HelpText
{
    private const STOP = [
        'como', 'hago', 'hacer', 'hacemos', 'hace', 'hacen', 'creo', 'crear', 'creamos',
        'quiero', 'necesito', 'puedo', 'dime', 'explicame', 'explica', 'aclarar',
        'ver', 'ves', 'consultar', 'consulta', 'listar', 'mostrar', 'buscar',
        'un', 'una', 'uno', 'el', 'la', 'los', 'las', 'de', 'del', 'en', 'por', 'para',
        'que', 'se', 'mi', 'mis', 'tu', 'su', 'al', 'a', 'y', 'o', 'es', 'son',
        'donde', 'esta', 'este', 'esto', 'eso', 'aqui', 'alla',
        'porfa', 'please', 'ayudame', 'oye', 'parce', 'pana', 'hermano',
        'podes', 'puedes', 'podrias', 'podria', 'digame', 'sabe', 'sabes',
        'dentro', 'sistema', 'hola', 'buenas',
        'tengo', 'hay', 'sirve', 'funciona', 'manera',
    ];

    /** Palabra de la pregunta → término canónico de Arena Players. */
    private const CANONICAL = [
        'fixtures' => 'fixture',
        'calendario' => 'fixture',
        'calendarios' => 'fixture',
        'cronograma' => 'fixture',
        'cronogramas' => 'fixture',
        'programacion' => 'fixture',
        'programaciones' => 'fixture',
        'agenda' => 'fixture',
        'horarios' => 'horario',
        'plantillas' => 'plantilla',
        'plantel' => 'plantilla',
        'planteles' => 'plantilla',
        'nomina' => 'plantilla',
        'nominas' => 'plantilla',
        'roster' => 'plantilla',
        'jugadores' => 'jugador',
        'ficha' => 'jugador',
        'fichas' => 'jugador',
        'inscripto' => 'inscribir',
        'inscrito' => 'inscribir',
        'inscripcion' => 'inscribir',
        'anotar' => 'inscribir',
        'anotarme' => 'inscribir',
        'sumar' => 'inscribir',
        'agregar' => 'agregar',
        'cargar' => 'cargar',
        'subir' => 'cargar',
        'aplazar' => 'aplazo',
        'aplazado' => 'aplazo',
        'aplazamiento' => 'aplazo',
        'aplazamientos' => 'aplazo',
        'posponer' => 'aplazo',
        'pospuesto' => 'aplazo',
        'postergar' => 'aplazo',
        'postergado' => 'aplazo',
        'postergacion' => 'aplazo',
        'reprogramar' => 'aplazo',
        'reprogramado' => 'aplazo',
        'reprogramacion' => 'aplazo',
        'suspender' => 'aplazo',
        'suspendido' => 'aplazo',
        'suspension' => 'sancion',
        'correr' => 'aplazo',
        'corrido' => 'aplazo',
        'mover' => 'mover',
        'movido' => 'mover',
        'cambiar' => 'mover',
        'pasarlo' => 'mover',
        'jornada' => 'fecha',
        'jornadas' => 'fecha',
        'encuentro' => 'partido',
        'encuentros' => 'partido',
        'partidos' => 'partido',
        'juego' => 'partido',
        'juegos' => 'partido',
        'marcador' => 'resultado',
        'marcadores' => 'resultado',
        'score' => 'resultado',
        'resultados' => 'resultado',
        'goles' => 'gol',
        'tanto' => 'gol',
        'tantos' => 'gol',
        'goleadores' => 'goleador',
        'scorer' => 'goleador',
        'posiciones' => 'tabla',
        'clasificacion' => 'tabla',
        'standing' => 'tabla',
        'standings' => 'tabla',
        'puntaje' => 'tabla',
        'puntos' => 'tabla',
        'arbitros' => 'arbitro',
        'referi' => 'arbitro',
        'referis' => 'arbitro',
        'referee' => 'arbitro',
        'juez' => 'arbitro',
        'jueces' => 'arbitro',
        'oficial' => 'arbitro',
        'oficiales' => 'arbitro',
        'terna' => 'terna',
        'ternas' => 'terna',
        'asistentes' => 'terna',
        'lineas' => 'terna',
        'coordinador' => 'coordinador',
        'coordinadora' => 'coordinador',
        'mesa' => 'mesa',
        'planilla' => 'planilla',
        'planillas' => 'planilla',
        'walkover' => 'wo',
        'walkovers' => 'wo',
        'wo' => 'wo',
        'w.o' => 'wo',
        'inasistencia' => 'wo',
        'campeonato' => 'torneo',
        'campeonatos' => 'torneo',
        'liga' => 'torneo',
        'ligas' => 'torneo',
        'copa' => 'torneo',
        'copas' => 'torneo',
        'certamen' => 'torneo',
        'torneos' => 'torneo',
        'evento' => 'torneo',
        'eventos' => 'torneo',
        'organizadores' => 'organizador',
        'admin' => 'organizador',
        'delegados' => 'delegado',
        'dt' => 'delegado',
        'tecnico' => 'delegado',
        'representante' => 'delegado',
        'cedulas' => 'cedula',
        'dni' => 'cedula',
        'documento' => 'cedula',
        'documentos' => 'cedula',
        'identificacion' => 'cedula',
        'identidad' => 'cedula',
        'apk' => 'app',
        'android' => 'app',
        'celular' => 'app',
        'telefono' => 'app',
        'renovacion' => 'renovar',
        'renovaciones' => 'renovar',
        'renueve' => 'renovar',
        'renuevo' => 'renovar',
        'temporada' => 'temporada',
        'temporadas' => 'temporada',
        'vencido' => 'vencer',
        'vencida' => 'vencer',
        'vencio' => 'vencer',
        'cerro' => 'cerrar',
        'cerrado' => 'cerrar',
        'bloqueado' => 'cerrar',
        'bloqueo' => 'cerrar',
        'readonly' => 'cerrar',
        'billing' => 'pago',
        'tarifa' => 'pago',
        'precio' => 'pago',
        'cuesta' => 'pago',
        'cobro' => 'pago',
        'cobran' => 'pago',
        'vale' => 'pago',
        'valor' => 'pago',
        'plata' => 'pago',
        'dinero' => 'pago',
        'comprobante' => 'pago',
        'gratis' => 'pago',
        'free' => 'pago',
        '50000' => 'pago',
        '50.000' => 'pago',
        'manual' => 'manual',
        'amano' => 'manual',
        'sanciones' => 'sancion',
        'roja' => 'sancion',
        'amarilla' => 'sancion',
        'tarjeta' => 'sancion',
        'expulsado' => 'sancion',
        'suspendido' => 'sancion',
        'reglamento' => 'reglamento',
        'reglas' => 'reglamento',
        'normas' => 'reglamento',
        'estatuto' => 'reglamento',
        'contrasena' => 'clave',
        'password' => 'clave',
        'clave' => 'clave',
        'resetear' => 'resetear',
        'borrar' => 'resetear',
        'eliminar' => 'resetear',
        'generar' => 'generar',
        'automatico' => 'generar',
        'master' => 'master',
        'dueno' => 'master',
    ];

    /** Frases enteras → tokens que el buscador ya entiende. */
    private const PHRASES = [
        'a mano' => 'fixture manual',
        'partido por partido' => 'fixture manual',
        'uno por uno' => 'fixture manual',
        'yo lo armo' => 'fixture manual',
        'lo armo yo' => 'fixture manual',
        'sin generar' => 'fixture manual',
        'sin automatico' => 'fixture manual',
        'solo este partido' => 'aplazo partido mover',
        'solo un partido' => 'aplazo partido mover',
        'un solo partido' => 'aplazo partido mover',
        'ese partido' => 'aplazo partido mover',
        'esta fecha' => 'aplazo fecha',
        'toda la fecha' => 'aplazo fecha',
        'fecha completa' => 'aplazo fecha',
        'toda la jornada' => 'aplazo fecha',
        'jornada completa' => 'aplazo fecha',
        'por lluvia' => 'aplazo fecha',
        'cancha mojada' => 'aplazo fecha',
        'sin luz' => 'aplazo partido',
        'no se presento' => 'wo',
        'no se presentaron' => 'wo',
        'no se presento nadie' => 'wo',
        'no aparecio' => 'wo',
        'no llego el equipo' => 'wo',
        'cuanto vale' => 'pago torneo',
        'cuanto sale' => 'pago torneo',
        'cuanto cobran' => 'pago torneo',
        'cuanto cuesta' => 'pago torneo',
        'que vale' => 'pago torneo',
        'es gratis' => 'pago torneo',
        'primer torneo' => 'pago torneo',
        'otra cuenta' => 'cedula organizador',
        'otra cedula' => 'cedula organizador',
        'cedula repetida' => 'cedula organizador',
        'mis fechas' => 'jugador',
        'proximas fechas' => 'jugador',
        'mis partidos' => 'jugador',
        'mis juegos' => 'jugador',
        'soy jugador' => 'jugador cedula',
        'soy delegado' => 'delegado',
        'soy arbitro' => 'arbitro mesa',
        'soy organizador' => 'organizador torneo',
        'cuerpo arbitral' => 'arbitro terna',
        'mesa arbitral' => 'mesa arbitro resultado',
        'cargar goles' => 'resultado gol',
        'cargar resultado' => 'resultado',
        'ver la tabla' => 'tabla',
        'ver posiciones' => 'tabla',
        'quienes juegan' => 'plantilla',
        'quien juega' => 'plantilla',
        'link publico' => 'publico torneo',
        'enlace del torneo' => 'publico torneo',
        'temporada vencida' => 'vencer renovar',
        'ya vencio' => 'vencer renovar',
        'se bloqueo' => 'cerrar renovar',
        'solo consulta' => 'cerrar renovar',
        'foto del dni' => 'plantilla cedula',
        'foto cedula' => 'plantilla cedula',
    ];

    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
        $text = preg_replace('/[^\p{L}\p{N}\s\.]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    public static function expandPhrases(string $normalized): string
    {
        foreach (self::PHRASES as $phrase => $replacement) {
            if (str_contains($normalized, $phrase)) {
                $normalized .= ' '.$replacement;
            }
        }

        return trim($normalized);
    }

    public static function canonical(string $word): string
    {
        $word = self::normalize($word);
        if ($word === '') {
            return '';
        }

        if (isset(self::CANONICAL[$word])) {
            return self::CANONICAL[$word];
        }

        if (str_ends_with($word, 'ciones') && mb_strlen($word) > 8) {
            $word = mb_substr($word, 0, -2);
        } elseif (str_ends_with($word, 'es') && mb_strlen($word) > 4) {
            $word = mb_substr($word, 0, -2);
        } elseif (str_ends_with($word, 's') && mb_strlen($word) > 3 && ! str_ends_with($word, 'ss')) {
            $word = mb_substr($word, 0, -1);
        }

        return self::CANONICAL[$word] ?? $word;
    }

    /** @return list<string> */
    public static function tokens(string $text): array
    {
        $normalized = self::expandPhrases(self::normalize($text));
        $parts = preg_split('/[\s\/,\.\-]+/', $normalized) ?: [];

        return collect($parts)
            ->map(fn ($word) => self::canonical((string) $word))
            ->filter(function ($word) {
                if ($word === '' || mb_strlen($word) < 3) {
                    return false;
                }

                return ! in_array($word, self::STOP, true);
            })
            ->unique()
            ->values()
            ->all();
    }

    public static function closeMatch(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $len = max(mb_strlen($a), mb_strlen($b));
        if ($len < 5) {
            return false;
        }

        return levenshtein($a, $b) <= ($len >= 8 ? 2 : 1);
    }
}
