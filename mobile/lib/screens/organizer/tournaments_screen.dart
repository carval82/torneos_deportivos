import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';
import '../tournament/tournament_screen.dart';
import '../welcome_screen.dart';

class TournamentsScreen extends StatefulWidget {
  const TournamentsScreen({super.key});

  @override
  State<TournamentsScreen> createState() => _TournamentsScreenState();
}

class _TournamentsScreenState extends State<TournamentsScreen> {
  List<dynamic> _items = [];
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
      final data = await context.read<AppState>().api.get('/tournaments');
      setState(() => _items = data is List ? data : []);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudieron cargar los torneos.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    return Scaffold(
      appBar: AppBar(
        title: Text(state.role == 'admin' ? 'Master · Torneos' : 'Mis torneos'),
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
                  Text('Hola, ${state.name ?? ''}', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  const Text('Creación y cobros se gestionan en la web. Acá consultás fixture y tabla.'),
                  const SizedBox(height: 16),
                  if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
                  if (_items.isEmpty && _error == null)
                    const Card(
                      child: Padding(
                        padding: EdgeInsets.all(16),
                        child: Text('Todavía no hay torneos.'),
                      ),
                    ),
                  ..._items.map((item) {
                    final t = item as Map<String, dynamic>;
                    final slug = t['public_slug']?.toString();
                    return Card(
                      child: ListTile(
                        title: Text(t['name']?.toString() ?? 'Torneo'),
                        subtitle: Text('${t['status'] ?? ''} · ${t['season'] ?? ''}'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: slug == null || slug.isEmpty
                            ? null
                            : () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(
                                    builder: (_) => TournamentScreen(
                                      slug: slug,
                                      title: t['name']?.toString(),
                                    ),
                                  ),
                                );
                              },
                      ),
                    );
                  }),
                ],
              ),
            ),
    );
  }
}
