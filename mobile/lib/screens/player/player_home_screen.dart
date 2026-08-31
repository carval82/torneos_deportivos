import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';
import '../../theme/arena_theme.dart';
import '../tournament/tournament_screen.dart';
import '../welcome_screen.dart';

class PlayerHomeScreen extends StatefulWidget {
  const PlayerHomeScreen({super.key});

  @override
  State<PlayerHomeScreen> createState() => _PlayerHomeScreenState();
}

class _PlayerHomeScreenState extends State<PlayerHomeScreen> {
  Map<String, dynamic>? _data;
  String? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await context.read<AppState>().api.get('/player/home') as Map<String, dynamic>;
      setState(() => _data = data);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'Error al cargar tu información.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final player = _data?['player'] as Map<String, dynamic>?;
    final team = player?['team'] as Map<String, dynamic>?;
    final tournaments = (_data?['tournaments'] as List?) ?? [];
    final upcoming = (_data?['upcoming'] as List?) ?? [];
    final results = (_data?['results'] as List?) ?? [];

    return Scaffold(
      appBar: AppBar(
        title: Text(state.name ?? 'Jugador'),
        actions: [
          IconButton(
            onPressed: () async {
              await state.logout();
              if (!context.mounted) return;
              Navigator.of(context).pushAndRemoveUntil(
                MaterialPageRoute(builder: (_) => const WelcomeScreen()),
                (_) => false,
              );
            },
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (_error != null)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Text(_error!, style: const TextStyle(color: Colors.red)),
                      ),
                    ),
                  Card(
                    color: ArenaColors.mist,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('DOCUMENTO VERIFICADO',
                              style: TextStyle(
                                color: ArenaColors.limeDark,
                                fontWeight: FontWeight.w800,
                                fontSize: 11,
                                letterSpacing: 1.2,
                              )),
                          const SizedBox(height: 8),
                          Text(player?['first_name'] != null
                              ? '${player?['first_name']} ${player?['last_name']}'
                              : state.name ?? ''),
                          Text('Cédula ${player?['document_number'] ?? _data?['document_number'] ?? ''}'),
                          Text('Equipo ${team?['name'] ?? '—'}'),
                          Text('Torneos vinculados: ${tournaments.length}'),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text('Mis torneos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  if (tournaments.isEmpty)
                    const Card(
                      child: Padding(
                        padding: EdgeInsets.all(16),
                        child: Text('Todavía no estás vinculado a un torneo.'),
                      ),
                    )
                  else
                    ...tournaments.map((t) {
                      final map = t as Map<String, dynamic>;
                      final slug = map['public_slug']?.toString();
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                map['name']?.toString() ?? 'Torneo',
                                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(height: 4),
                              Text(map['status']?.toString() ?? '', style: const TextStyle(color: Colors.black54)),
                              const SizedBox(height: 12),
                              SizedBox(
                                width: double.infinity,
                                child: FilledButton.icon(
                                  onPressed: slug == null || slug.isEmpty
                                      ? null
                                      : () {
                                          Navigator.of(context).push(
                                            MaterialPageRoute(
                                              builder: (_) => TournamentScreen(
                                                slug: slug,
                                                title: map['name']?.toString(),
                                              ),
                                            ),
                                          );
                                        },
                                  icon: const Icon(Icons.sports_soccer),
                                  label: const Text('Entrar al torneo'),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                  const SizedBox(height: 16),
                  const Text('Próximas fechas', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  if (upcoming.isEmpty)
                    const Text('Sin partidos pendientes.')
                  else
                    ...upcoming.map((g) {
                      final map = g as Map<String, dynamic>;
                      final home = map['home_team'] ?? map['homeTeam'];
                      final away = map['away_team'] ?? map['awayTeam'];
                      final field = map['field_name']?.toString();
                      final venue = map['venue']?.toString();
                      final place = (field != null && field.isNotEmpty)
                          ? field
                          : ((venue != null && venue.isNotEmpty) ? venue : 'Sin cancha');
                      final when = map['scheduled_at']?.toString() ?? '';
                      final time = when.length >= 16
                          ? when.replaceFirst('T', ' ').substring(11, 16)
                          : when;
                      return Card(
                        child: ListTile(
                          title: Text(
                            '${(home is Map ? home['name'] : null) ?? 'Local'} vs ${(away is Map ? away['name'] : null) ?? 'Visita'}',
                          ),
                          subtitle: Text('Fecha ${map['matchday'] ?? '—'} · $time · $place'),
                        ),
                      );
                    }),
                  const SizedBox(height: 16),
                  const Text('Últimos resultados', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  if (results.isEmpty)
                    const Text('Sin resultados.')
                  else
                    ...results.map((g) {
                      final map = g as Map<String, dynamic>;
                      final home = map['home_team'] ?? map['homeTeam'];
                      final away = map['away_team'] ?? map['awayTeam'];
                      return Card(
                        child: ListTile(
                          title: Text(
                            '${(home is Map ? home['short_name'] ?? home['name'] : 'L')} vs ${(away is Map ? away['short_name'] ?? away['name'] : 'V')}',
                          ),
                          trailing: Text(
                            '${map['home_score'] ?? 0} – ${map['away_score'] ?? 0}',
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                        ),
                      );
                    }),
                ],
              ),
            ),
    );
  }
}
