<?php

namespace App\Services;

use App\Support\HelpText;

class ArenaHelpDesk
{
    public function ask(string $text): array
    {
        $normalized = HelpText::normalize($text);
        $expanded = HelpText::expandPhrases($normalized);

        if ($this->isMenu($normalized)) {
            return [
                'title' => 'Temas de ayuda',
                'body' => $this->menu(),
                'suggestions' => $this->chips(),
            ];
        }

        $tokens = HelpText::tokens($text);
        $best = null;
        $bestScore = 0;

        foreach ($this->articles() as $article) {
            $score = $this->score($article, $expanded, $tokens);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $article;
            }
        }

        if ($best && $bestScore >= 10) {
            return [
                'title' => $best['title'],
                'body' => $best['body'],
                'suggestions' => $best['related'] ?? $this->chips(),
            ];
        }

        return [
            'title' => 'No di con eso',
            'body' => "Preguntá como en la cancha. Ejemplos:\n• *cómo entro de jugador*\n• *el fixture lo armo yo*\n• *correr solo un partido*\n• *aplazar toda la fecha por lluvia*\n• *cuánto sale un torneo*\n• *quién carga el resultado*\n\nO escribí *ayuda*.",
            'suggestions' => $this->chips(),
        ];
    }

    public function welcome(): string
    {
        return "Soy la ayuda de *Arena Players*. Preguntame como hables: «entrar con la cédula», «armar las fechas a mano», «correr un partido», «cuánto vale».\nTe digo *quién* lo hace y *cómo*.";
    }

    /** @return list<string> */
    public function chips(): array
    {
        return ['Jugador', 'Armar torneo', 'Fixture a mano', 'Aplazar partido', 'Aplazar fecha', 'Árbitro', 'Pago', 'App'];
    }

    private function isMenu(string $normalized): bool
    {
        $intents = [
            'ayuda', 'menu', 'temas', 'opciones', 'dudas', 'duda',
            'que puedes', 'que sabes', 'que puedo preguntar', 'que hago',
            'como funciona', 'que es esto', 'inicio',
        ];

        foreach ($intents as $intent) {
            if ($normalized === $intent || $normalized === HelpText::normalize($intent)) {
                return true;
            }
        }

        return $normalized === '' || in_array($normalized, ['hola', 'buenas', 'buenos dias', 'hey'], true);
    }

    private function menu(): string
    {
        return "Podés preguntar de mil formas. Temas:\n• *Jugador / cédula* — entrar a ver tus fechas\n• *Organizador* — crear torneo, pagar, renovar\n• *Delegado / plantilla* — quién juega\n• *Fixture* — automático o a mano\n• *Aplazos* — un partido o toda la fecha\n• *Árbitro / mesa* — resultado, goles, W.O.\n• *Tabla y goleadores*\n• *App Android*\n\nEjemplos: *el fixture lo armo yo*, *se cortó la luz en una cancha*, *llovió y se cae la fecha*.";
    }

    /**
     * @param  array<string, mixed>  $article
     * @param  list<string>  $tokens
     */
    private function score(array $article, string $normalized, array $tokens): int
    {
        $score = 0;
        $title = HelpText::normalize($article['title']);
        $titleTokens = HelpText::tokens($article['title']);
        $needles = collect($article['keywords'] ?? [])
            ->map(fn ($k) => HelpText::normalize((string) $k))
            ->filter();
        $keywordTokens = collect($article['keywords'] ?? [])
            ->flatMap(fn ($k) => HelpText::tokens((string) $k))
            ->unique()
            ->all();

        if ($normalized === $title) {
            $score += 120;
        }

        foreach ($needles as $needle) {
            if ($needle === '') {
                continue;
            }
            if ($normalized === $needle) {
                $score += 90;
            } elseif (str_contains($normalized, $needle)) {
                $score += 16 + min(24, mb_strlen($needle));
            }
        }

        foreach ($tokens as $token) {
            if (in_array($token, $titleTokens, true)) {
                $score += 28;
            }
            if (in_array($token, $keywordTokens, true)) {
                $score += 24;
            }
            foreach ($keywordTokens as $kw) {
                if (HelpText::closeMatch($token, $kw)) {
                    $score += 14;
                    break;
                }
            }
        }

        return $score;
    }

    private function guide(string $paraQue, string $quien, string $como): string
    {
        return "Para qué: {$paraQue}\nQuién: {$quien}\nCómo:\n{$como}";
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function articles(): array
    {
        return [
            [
                'title' => 'Qué es Arena Players',
                'keywords' => ['que es', 'arena players', 'para que sirve', 'sistema de torneos', 'plataforma', 'campeonato digital'],
                'related' => ['Armar torneo', 'Jugador', 'Pago'],
                'body' => $this->guide(
                    'Llevar un torneo (fútbol, vóley, básquet u otro) con plantillas, fixture, resultados y tabla, todos viendo lo mismo.',
                    'Organizador, delegado, árbitro, coordinador arbitral y jugador. Cada uno entra por su puerta.',
                    "En la portada:\n• *Soy organizador* — crea y paga el torneo.\n• *Soy delegado* — plantilla del equipo.\n• *Soy árbitro* — mesa y marcador.\n• *Soy jugador* — cédula, sin usuario.\nTambién hay *app Android*."
                ),
            ],
            [
                'title' => 'Entrar como jugador',
                'keywords' => ['jugador', 'soy jugador', 'entrar con cedula', 'mi ficha', 'mis partidos', 'mis fechas', 'ver mis fechas', 'ver mis juegos', 'proximas fechas', 'login jugador', 'casa del jugador'],
                'related' => ['Plantilla', 'Ver tabla', 'App'],
                'body' => $this->guide(
                    'Que el jugador vea su equipo, torneos, próximas fechas y resultados.',
                    'El jugador. No se le crea usuario: usa la *cédula* que cargó el delegado.',
                    "1. Portada → *Soy jugador* (o en la app, perfil jugador).\n2. Escribí la *cédula* (la misma de la plantilla).\n3. Entras a tu casa: equipo, fixture y últimos resultados.\nSi dice que no estás, pedile al *delegado* que te sume a la plantilla."
                ),
            ],
            [
                'title' => 'Entrar como delegado',
                'keywords' => ['delegado', 'soy delegado', 'representante', 'dt', 'tecnico del equipo', 'invitar delegado', 'usuario delegado'],
                'related' => ['Plantilla', 'Inscribir equipos'],
                'body' => $this->guide(
                    'Que el equipo cargue y cuide su plantilla.',
                    'El organizador invita o crea el usuario. La contraseña inicial es el *número de documento*.',
                    "1. Si te mandaron un *link de invitación*, abrilo y aceptá.\n2. O *Soy delegado* e iniciá sesión (documento = clave la primera vez).\n3. Menú *Delegado* → tu equipo → *plantilla*.\nEl organizador también arma delegados en *Delegados*."
                ),
            ],
            [
                'title' => 'Entrar como árbitro',
                'keywords' => ['soy arbitro', 'entrar arbitro', 'login arbitro', 'mesa arbitral', 'usuario referee', 'clave arbitro'],
                'related' => ['Cargar resultado', 'Terna arbitral'],
                'body' => $this->guide(
                    'Que el juez del partido cargue marcador, goles y asistencia desde la mesa o la app.',
                    'El organizador da de alta al árbitro o al coordinador. Contraseña inicial = *documento*.',
                    "1. Portada → *Soy árbitro*.\n2. Entrá con el usuario que te crearon.\n3. *Mesa arbitral*: ves los partidos asignados.\nSi no te asignaron, no ves el partido. Pedile al *coordinador* o al organizador."
                ),
            ],
            [
                'title' => 'Crear cuenta de organizador',
                'keywords' => ['registro', 'crear cuenta', 'soy organizador', 'cedula unica', 'otra cuenta', 'evitar pago', 'documento repetido', 'identidad'],
                'related' => ['Pago', 'Armar torneo'],
                'body' => $this->guide(
                    'Abrir el panel para crear torneos. La cédula del organizador es *única*.',
                    'Solo organizadores. No se puede evadir el pago abriendo otra cuenta con la misma persona.',
                    "1. *Crear cuenta* / *Soy organizador*.\n2. Nombre, correo, *cédula obligatoria* (no puede estar usada).\n3. En *Perfil* completá el documento si te faltó.\nDelegados, árbitros y jugadores *no pagan*."
                ),
            ],
            [
                'title' => 'Crear un torneo',
                'keywords' => ['crear torneo', 'armar torneo', 'nuevo torneo', 'abrir campeonato', 'nuevo campeonato', 'liga nueva', 'empezar torneo'],
                'related' => ['Pago', 'Inscribir equipos', 'Fixture a mano'],
                'body' => $this->guide(
                    'Dejar el campeonato armado: deporte, formato, días de juego, canchas y reglas.',
                    'El organizador (con crédito aprobado) o el master.',
                    "1. *Pagos*: pedí activación ($50.000 COP) y esperá al master.\n2. *Torneos* → crear: nombre, deporte, ida y vuelta o eliminación, días, horarios, canchas.\n3. Elegí si cada partido lleva *un árbitro* o *terna*.\n4. Inscribí equipos e invitá delegados.\nDespués: fixture automático *o* partidos a mano."
                ),
            ],
            [
                'title' => 'Inscribir equipos',
                'keywords' => ['inscribir equipo', 'sumar equipo', 'anotar equipo', 'meter equipo', 'cupo equipos', 'enroll'],
                'related' => ['Delegado', 'Plantilla'],
                'body' => $this->guide(
                    'Meter los clubes al torneo para poder armar fixture y plantillas.',
                    'El organizador, en el torneo → *Resumen*.',
                    "1. Primero creá el *equipo* en Equipos (si no existe).\n2. En el torneo, *Inscribir equipo*.\n3. Invitá al *delegado* de ese equipo.\nSi el torneo ya *venció* o está *cerrado por renovar*, no se agregan equipos."
                ),
            ],
            [
                'title' => 'Cargar la plantilla',
                'keywords' => ['plantilla', 'cargar jugadores', 'sumar jugador', 'foto dni', 'foto cedula', 'quienes juegan', 'plantel', 'nomina', 'ficha'],
                'related' => ['Jugador', 'Delegado'],
                'body' => $this->guide(
                    'Anotar quién puede jugar: nombre, cédula y, si la categoría pide, foto del jugador y del documento.',
                    'El *delegado* de ese equipo. El organizador puede ayudar.',
                    "1. *Delegado* → equipo → plantilla.\n2. Sumá jugadores (cédula, datos, fotos si aplica).\n3. Esa cédula es la que usa el jugador para entrar.\nCuando la planilla se cierra, no se cambia a último momento."
                ),
            ],
            [
                'title' => 'Generar el fixture automático',
                'keywords' => ['generar fixture', 'fixture automatico', 'armar fechas solo', 'todos contra todos', 'eliminacion', 'sortear partidos'],
                'related' => ['Fixture a mano', 'Aplazar fecha'],
                'body' => $this->guide(
                    'Que el sistema arme todos los partidos (quién vs quién, día, hora y cancha).',
                    'El organizador. Hace falta tener *al menos 2 equipos* y *ningún partido* todavía.',
                    "1. Inscribí los equipos.\n2. En el torneo: *Generar fixture*.\n3. Revisá en la pestaña *Fixture*.\nSi ya hay partidos, primero *Resetear fixture* (borra todos) o agregá/editá a mano sin resetear."
                ),
            ],
            [
                'title' => 'Armar el fixture a mano',
                'keywords' => ['fixture manual', 'a mano', 'agregar partido', 'cargar partido', 'yo armo el fixture', 'programar partido', 'partido por partido', 'sin automatico', 'editar fixture', 'quitar partido'],
                'related' => ['Aplazar partido', 'Generar fixture'],
                'body' => $this->guide(
                    'Que el organizador controle todo el calendario: él decide rivales, fecha N°, día, hora y cancha.',
                    'El organizador, pestaña *Fixture*. No hace falta generar el automático.',
                    "1. *Agregar partido a mano*: local, visitante, Fecha N°, día/hora, cancha.\n2. En cada fila: *Mover / aplazar* para corregir, o *Quitar* si no va.\n3. Podés mezclar: generar algunos y completar el resto a mano.\n*Resetear fixture* borra *todos* los partidos. Un partido ya jugado no se quita ni se edita ahí: se corrige en la planilla."
                ),
            ],
            [
                'title' => 'Aplazar un solo partido',
                'keywords' => ['aplazo partido', 'mover partido', 'correr un partido', 'reprogramar partido', 'solo este partido', 'sin luz', 'una cancha', 'ese encuentro', 'cambiar horario de un partido'],
                'related' => ['Aplazar fecha', 'Fixture a mano'],
                'body' => $this->guide(
                    'Mover *solo ese* encuentro (se cortó la luz, una cancha quedó mal, un equipo pidió cambio). El resto de la jornada *sigue*.',
                    'El organizador. En Fixture o en la planilla del partido.',
                    "1. Fixture → en ese partido *Mover / aplazar*.\n2. Nueva fecha/hora, cancha o Fecha N°.\n3. Estado *Aplazado (solo este)* y el motivo.\nO abrí la planilla → *Aplazar solo este partido*.\nSi la nueva fecha pasa el fin de temporada, el calendario *se estira solo*. No se corren los otros partidos de esa fecha."
                ),
            ],
            [
                'title' => 'Aplazar toda la fecha',
                'keywords' => ['aplazo fecha', 'aplazar fecha completa', 'lluvia', 'cancha mojada', 'clima', 'jornada completa', 'correr todas las fechas', 'postergar la fecha', 'se cayo la fecha'],
                'related' => ['Aplazar partido', 'Temporada vencida'],
                'body' => $this->guide(
                    'Correr *toda la jornada* al próximo día de juego (el domingo siguiente, etc.) y *también las fechas que vienen*, para que no se pisen.',
                    'El organizador. Botón *Aplazar fecha completa* en esa Fecha N°.',
                    "1. Fixture → encabezado de la Fecha → *Aplazar fecha completa*.\n2. Poné el motivo (lluvia, cancha natural, etc.).\n3. Se mueven los partidos que *siguen en esa jornada*.\nLos que ya habías movido *uno por uno* no se tocan. Las fechas siguientes sí se corren. El fin de temporada se estira si hace falta."
                ),
            ],
            [
                'title' => 'Temporada vencida o renovar',
                'keywords' => ['renovar', 'temporada', 'vencer', 'cerrado', 'solo consulta', 'locked', 'bloqueo', 'ya termino', 'seguir el torneo', 'nueva temporada'],
                'related' => ['Pago', 'Aplazar partido'],
                'body' => $this->guide(
                    'Cerrar una temporada pagada y abrir la siguiente, o terminar de jugar lo que quedó pendiente.',
                    'El organizador paga la renovación; el master aprueba.',
                    "• Si *pasó la fecha de fin* y *quedan partidos*: podés *aplazar* y cargar resultados. No se agregan equipos ni partidos nuevos hasta que el calendario se estire.\n• Si ya no queda nada pendiente: el torneo queda *solo consulta*. Para seguir, *renovar* ($50.000).\n• Al renovar, el torneo viejo se *congela* (no se toca). El nuevo es otra temporada."
                ),
            ],
            [
                'title' => 'Árbitros y terna',
                'keywords' => ['arbitro', 'terna', 'cuerpo arbitral', 'coordinador', 'asignar arbitro', 'un juez', 'tres arbitros', 'central y lineas'],
                'related' => ['Cargar resultado', 'Entrar como árbitro'],
                'body' => $this->guide(
                    'Cubrir cada partido con *un árbitro* o con *terna* (central y dos asistentes), según el reglamento del torneo.',
                    'El organizador crea oficiales en *Árbitros*. El *coordinador* (o el organizador) los asigna partido por partido.',
                    "1. Al crear/editar el torneo: 1 árbitro o terna.\n2. *Árbitros*: alta (clave = documento).\n3. En el partido o en la mesa: asignar central y asistentes.\nEl árbitro asignado carga el marcador. Si no hay nadie asignado, puede el organizador."
                ),
            ],
            [
                'title' => 'Cargar resultado, goles y W.O.',
                'keywords' => ['resultado', 'gol', 'marcador', 'mesa', 'planilla', 'wo', 'walkover', 'no se presento', 'asistencia', 'cargar goles'],
                'related' => ['Tabla', 'Árbitros'],
                'body' => $this->guide(
                    'Dejar el resultado oficial. De ahí salen tabla, goleadores y sanciones.',
                    'El *árbitro asignado*. Si no hay, el organizador o el coordinador.',
                    "1. *Mesa arbitral* o abrí el partido.\n2. Marcador y quién hizo los goles.\n3. Asistencia (titulares / suplentes / ausentes).\n4. Si un equipo no llegó: *W.O.*\n5. Guardá. La *tabla* se actualiza sola."
                ),
            ],
            [
                'title' => 'Tabla, goleadores y fixture público',
                'keywords' => ['tabla', 'posiciones', 'goleador', 'ver fixture', 'publico', 'link', 'hincha', 'donde veo', 'clasificacion'],
                'related' => ['Reglamento', 'Jugador'],
                'body' => $this->guide(
                    'Que cualquiera con el link vea fixture, tabla, goleadores y reglamento, sin ser organizador.',
                    'El organizador comparte *Vista torneo* / el link público.',
                    "En el torneo: *Vista torneo*. Ahí: Fixture, Tabla, Goleadores, Reglamento.\nEl jugador, además, ve *sus* fechas al entrar con la cédula."
                ),
            ],
            [
                'title' => 'Sanciones y tarjetas',
                'keywords' => ['sancion', 'roja', 'amarilla', 'tarjeta', 'expulsado', 'fechas de suspension', 'comite'],
                'related' => ['Reglamento', 'Cargar resultado'],
                'body' => $this->guide(
                    'Descontar fechas a quien vea roja o doble amarilla, y que el comité pueda emitir sentencias.',
                    'La planilla (tarjetas) y el organizador / comité disciplinario.',
                    "En el torneo se define cuántas fechas resta una *roja* y una *doble amarilla*.\nPestaña *Sanciones*. El comité (delegado marcado) también puede cargar una sentencia."
                ),
            ],
            [
                'title' => 'Reglamento del torneo',
                'keywords' => ['reglamento', 'reglas', 'normas', 'estatuto', 'publicar reglamento'],
                'related' => ['Crear un torneo', 'Sanciones'],
                'body' => $this->guide(
                    'Dejar por escrito cómo se juega y que el público lo lea.',
                    'El organizador, al editar el torneo.',
                    "1. *Editar* torneo → reglamento y reglas de competencia (terna, puntos, etc.).\n2. Publicá el reglamento.\n3. Se ve en *Reglamento* y en la vista pública."
                ),
            ],
            [
                'title' => 'Cuánto cuesta un torneo',
                'keywords' => ['pago', 'cuanto cuesta', '50000', '50.000', 'gratis', 'billing', 'comprobante', 'activar', 'credito', 'renovar precio'],
                'related' => ['Crear un torneo', 'Renovar'],
                'body' => $this->guide(
                    'Saber qué se cobra. *No hay torneo gratis*: cada creación o renovación vale *$50.000 COP*.',
                    'Paga solo el *organizador*. Delegado, árbitro y jugador no pagan. El *master* aprueba el comprobante.',
                    "1. *Pagos* / Activación.\n2. Pedí el crédito y subí el comprobante.\n3. Cuando el master aprueba, podés *crear* o *renovar*.\nLa cédula es única: otra cuenta de la misma persona no sirve para evadir."
                ),
            ],
            [
                'title' => 'Descargar la app',
                'keywords' => ['app', 'android', 'apk', 'celular', 'descargar', 'telefono'],
                'related' => ['Jugador', 'Árbitro'],
                'body' => $this->guide(
                    'Usar plantilla, fixture y mesa en el teléfono, en la cancha.',
                    'Cualquier perfil: organizador, delegado, árbitro o jugador.',
                    "En la web: *Descargar app* (Android).\nEntrá con el mismo perfil. El jugador solo pone la cédula."
                ),
            ],
            [
                'title' => 'Contraseña inicial',
                'keywords' => ['clave', 'contrasena', 'password', 'no puedo entrar', 'olvide la clave', 'documento es la clave'],
                'related' => ['Delegado', 'Árbitro'],
                'body' => $this->guide(
                    'Delegados y árbitros entran la primera vez con el *número de documento* como contraseña.',
                    'Quien recibió el usuario. Después pueden cambiarla en Perfil.',
                    "Usuario = el que te crearon.\nClave inicial = *cédula / documento* (sin espacios).\nSi no entra, pedile al organizador que revise el documento cargado."
                ),
            ],
        ];
    }
}
