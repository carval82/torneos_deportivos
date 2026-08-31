import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';
import '../tournament/tournament_screen.dart';
import '../welcome_screen.dart';

enum TournamentListMode { organizer, delegate }

class TournamentsScreen extends StatefulWidget {
  const TournamentsScreen({super.key, this.mode = TournamentListMode.organizer});

  final TournamentListMode mode;

  @override
  State<TournamentsScreen> createState() => _TournamentsScreenState();
}

class _TournamentsScreenState extends State<TournamentsScreen> {
  List<Map<String, dynamic>> _items = [];
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
      final api = context.read<AppState>().api;
      if (widget.mode == TournamentListMode.delegate) {
        final data = await api.get('/delegate/tournaments') as Map<String, dynamic>;
        final raw = (data['tournaments'] as List?) ?? [];
        setState(() {
          _items = raw.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
        });
      } else {
        final data = await api.get('/tournaments');
        final raw = data is List ? data : [];
        setState(() {
          _items = raw.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
        });
      }
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudieron cargar los torneos.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _open(Map<String, dynamic> tournament) {
    final slug = tournament['public_slug']?.toString();
    if (slug == null || slug.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Este torneo todavía no tiene link público.')),
      );
      return;
    }
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
    final isMaster = state.role == 'admin';
    final isDelegate = widget.mode == TournamentListMode.delegate;

    return Scaffold(
      appBar: AppBar(
        title: Text(isDelegate
            ? 'Mis torneos'
            : (isMaster ? 'Master · Torneos' : 'Mis torneos')),
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
                  Text('Hola, ${state.name ?? ''}',
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  Text(
                    isDelegate
                        ? 'Solo ves los torneos de tus equipos. Entrá para fixture, tabla y goleadores.'
                        : 'Creación y cobros se gestionan en la web. Acá consultás fixture, tabla y reglamento.',
                  ),
                  const SizedBox(height: 16),
                  if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
                  if (_items.isEmpty && _error == null)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Text(isDelegate
                            ? 'Todavía no hay torneos vinculados a tus equipos.'
                            : 'Todavía no hay torneos.'),
                      ),
                    ),
                  ..._items.map((tournament) {
                    final slug = tournament['public_slug']?.toString();
                    final subtitle = [
                      tournament['status_label'] ?? tournament['status'] ?? '',
                      if (tournament['sport'] != null) tournament['sport'],
                      if (tournament['teams_count'] != null) '${tournament['teams_count']} equipos',
                    ].where((e) => e.toString().trim().isNotEmpty).join(' · ');

                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              tournament['name']?.toString() ?? 'Torneo',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                            ),
                            if (subtitle.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(subtitle, style: const TextStyle(color: Colors.black54)),
                            ],
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: FilledButton.icon(
                                onPressed: slug == null || slug.isEmpty ? null : () => _open(tournament),
                                icon: const Icon(Icons.sports_soccer),
                                label: const Text('Entrar al torneo'),
                              ),
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
