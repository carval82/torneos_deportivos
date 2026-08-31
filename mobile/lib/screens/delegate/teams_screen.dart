import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';
import '../tournament/tournament_screen.dart';
import '../welcome_screen.dart';
import 'roster_screen.dart';

class TeamsScreen extends StatefulWidget {
  const TeamsScreen({super.key});

  @override
  State<TeamsScreen> createState() => _TeamsScreenState();
}

class _TeamsScreenState extends State<TeamsScreen> {
  List<Map<String, dynamic>> _teams = [];
  bool _loading = true;
  String? _error;

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
      final data = await context.read<AppState>().api.get('/delegate/teams') as Map<String, dynamic>;
      final raw = (data['teams'] as List?) ?? [];
      setState(() {
        _teams = raw.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudieron cargar los equipos.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _openTournament(Map<String, dynamic> tournament) {
    final slug = tournament['public_slug']?.toString();
    if (slug == null || slug.isEmpty) return;
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => TournamentScreen(
          slug: slug,
          title: tournament['name']?.toString(),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mis equipos'),
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
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                children: [
                  Text('Hola, ${state.name ?? ''}', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  const Text('Gestioná la plantilla y entrá al torneo para ver fixture, tabla y goleadores.'),
                  const SizedBox(height: 16),
                  if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
                  if (_teams.isEmpty && _error == null)
                    const Card(
                      child: Padding(
                        padding: EdgeInsets.all(16),
                        child: Text('No tenés equipos asignados. Pedí el link de invitación al organizador.'),
                      ),
                    ),
                  ..._teams.map((team) {
                    final tournaments = ((team['tournaments'] as List?) ?? [])
                        .whereType<Map>()
                        .map((e) => Map<String, dynamic>.from(e))
                        .toList();
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            ListTile(
                              contentPadding: EdgeInsets.zero,
                              title: Text(team['name']?.toString() ?? 'Equipo',
                                  style: const TextStyle(fontWeight: FontWeight.w700)),
                              subtitle: Text('${team['players_count'] ?? 0} jugadores'),
                              trailing: const Icon(Icons.chevron_right),
                              onTap: () async {
                                await Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => RosterScreen(
                                      teamId: (team['id'] as num).toInt(),
                                      teamName: team['name']?.toString() ?? 'Equipo',
                                    ),
                                  ),
                                );
                                if (mounted) _load();
                              },
                            ),
                            if (tournaments.isEmpty)
                              const Padding(
                                padding: EdgeInsets.only(top: 4),
                                child: Text('Sin torneo vinculado todavía.',
                                    style: TextStyle(color: Colors.black54, fontSize: 13)),
                              )
                            else
                              ...tournaments.map((tournament) {
                                final slug = tournament['public_slug']?.toString();
                                return Padding(
                                  padding: const EdgeInsets.only(top: 8),
                                  child: SizedBox(
                                    width: double.infinity,
                                    child: FilledButton.icon(
                                      onPressed: slug == null || slug.isEmpty
                                          ? null
                                          : () => _openTournament(tournament),
                                      icon: const Icon(Icons.emoji_events),
                                      label: Text('Ver torneo: ${tournament['name'] ?? ''}'),
                                    ),
                                  ),
                                );
                              }),
                            const SizedBox(height: 4),
                            TextButton.icon(
                              onPressed: () async {
                                await Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => RosterScreen(
                                      teamId: (team['id'] as num).toInt(),
                                      teamName: team['name']?.toString() ?? 'Equipo',
                                    ),
                                  ),
                                );
                                if (mounted) _load();
                              },
                              icon: const Icon(Icons.badge_outlined),
                              label: const Text('Gestionar plantilla'),
                            ),
                          ],
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
