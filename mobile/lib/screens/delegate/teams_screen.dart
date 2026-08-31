import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';
import '../welcome_screen.dart';
import 'roster_screen.dart';

class TeamsScreen extends StatefulWidget {
  const TeamsScreen({super.key});

  @override
  State<TeamsScreen> createState() => _TeamsScreenState();
}

class _TeamsScreenState extends State<TeamsScreen> {
  List<dynamic> _teams = [];
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
      setState(() => _teams = (data['teams'] as List?) ?? []);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudieron cargar los equipos.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
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
                padding: const EdgeInsets.all(16),
                children: [
                  Text('Hola, ${state.name ?? ''}', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 8),
                  const Text('Gestioná la plantilla de tus equipos. Sin costo para delegados.'),
                  const SizedBox(height: 16),
                  if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
                  if (_teams.isEmpty && _error == null)
                    const Card(
                      child: Padding(
                        padding: EdgeInsets.all(16),
                        child: Text('No tenés equipos asignados. Pedí el link de invitación al organizador.'),
                      ),
                    ),
                  ..._teams.map((item) {
                    final t = item as Map<String, dynamic>;
                    return Card(
                      child: ListTile(
                        title: Text(t['name']?.toString() ?? 'Equipo'),
                        subtitle: Text('${t['players_count'] ?? t['players_count'] ?? 0} jugadores'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () async {
                          await Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => RosterScreen(
                                teamId: (t['id'] as num).toInt(),
                                teamName: t['name']?.toString() ?? 'Equipo',
                              ),
                            ),
                          );
                          if (mounted) _load();
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
