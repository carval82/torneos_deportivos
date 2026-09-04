import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';
import '../welcome_screen.dart';
import 'referee_match_screen.dart';

class RefereeDeskScreen extends StatefulWidget {
  const RefereeDeskScreen({super.key});

  @override
  State<RefereeDeskScreen> createState() => _RefereeDeskScreenState();
}

class _RefereeDeskScreenState extends State<RefereeDeskScreen> {
  List<Map<String, dynamic>> _assigned = [];
  List<Map<String, dynamic>> _coordinated = [];
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
      final data = await context.read<AppState>().api.get('/referee/games') as Map<String, dynamic>;
      setState(() {
        _assigned = ((data['assigned'] as List?) ?? [])
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
        _coordinated = ((data['coordinated'] as List?) ?? [])
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudieron cargar los partidos.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _open(Map<String, dynamic> game) {
    Navigator.of(context)
        .push(
          MaterialPageRoute(builder: (_) => RefereeMatchScreen(gameId: (game['id'] as num).toInt())),
        )
        .then((_) => _load());
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final isCoordinator = state.role == 'referee_coordinator' || state.role == 'admin' || state.role == 'organizer';

    return Scaffold(
      appBar: AppBar(
        title: Text(state.name ?? 'Árbitro'),
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
                  Text(
                    isCoordinator
                        ? 'Asigná y seguí los encuentros. El árbitro puede cargar el marcador en vivo.'
                        : 'Tus partidos. Podés editar el resultado mientras el encuentro está en juego.',
                    style: TextStyle(color: Colors.grey.shade700),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                  ],
                  const SizedBox(height: 20),
                  const Text('Mis partidos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  if (_assigned.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 24),
                      child: Text('No tenés partidos asignados todavía.'),
                    )
                  else
                    ..._assigned.map((game) => _GameTile(game: game, onTap: () => _open(game))),
                  if (isCoordinator) ...[
                    const SizedBox(height: 20),
                    const Text('Encuentros del torneo', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    if (_coordinated.isEmpty)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 16),
                        child: Text('No hay partidos pendientes.'),
                      )
                    else
                      ..._coordinated.map((game) => _GameTile(game: game, onTap: () => _open(game))),
                  ],
                ],
              ),
            ),
    );
  }
}

class _GameTile extends StatelessWidget {
  const _GameTile({required this.game, required this.onTap});

  final Map<String, dynamic> game;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final home = (game['home_team'] as Map?)?['name'] ?? 'Local';
    final away = (game['away_team'] as Map?)?['name'] ?? 'Visita';
    final tournament = (game['tournament'] as Map?)?['name'] ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        onTap: onTap,
        title: Text('$home vs $away'),
        subtitle: Text(
          [
            tournament,
            game['venue'] ?? '',
            game['referees_label'] ?? '',
          ].where((e) => e.toString().isNotEmpty).join(' · '),
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              game['scoreline']?.toString() ?? 'vs',
              style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
            ),
            Text(
              game['status_label']?.toString() ?? '',
              style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
            ),
          ],
        ),
      ),
    );
  }
}
