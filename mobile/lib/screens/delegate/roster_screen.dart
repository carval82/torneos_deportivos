import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';

class RosterScreen extends StatefulWidget {
  const RosterScreen({super.key, required this.teamId, required this.teamName});

  final int teamId;
  final String teamName;

  @override
  State<RosterScreen> createState() => _RosterScreenState();
}

class _RosterScreenState extends State<RosterScreen> {
  List<dynamic> _players = [];
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
      final data = await context.read<AppState>().api.get('/delegate/teams/${widget.teamId}/roster')
          as Map<String, dynamic>;
      final team = data['team'] as Map<String, dynamic>?;
      setState(() => _players = (team?['players'] as List?) ?? []);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudo cargar la plantilla.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _addPlayer() async {
    final first = TextEditingController();
    final last = TextEditingController();
    final doc = TextEditingController();
    final birth = TextEditingController(text: '2005-01-01');
    String gender = 'masculino';

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Agregar jugador'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(controller: first, decoration: const InputDecoration(labelText: 'Nombre')),
              TextField(controller: last, decoration: const InputDecoration(labelText: 'Apellido')),
              TextField(
                controller: doc,
                decoration: const InputDecoration(labelText: 'Cédula'),
                keyboardType: TextInputType.number,
              ),
              TextField(
                controller: birth,
                decoration: const InputDecoration(labelText: 'Nacimiento (YYYY-MM-DD)'),
              ),
              DropdownButtonFormField<String>(
                value: gender,
                items: const [
                  DropdownMenuItem(value: 'masculino', child: Text('Masculino')),
                  DropdownMenuItem(value: 'femenino', child: Text('Femenino')),
                  DropdownMenuItem(value: 'mixto', child: Text('Mixto')),
                ],
                onChanged: (v) => gender = v ?? 'masculino',
                decoration: const InputDecoration(labelText: 'Género'),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancelar')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Guardar')),
        ],
      ),
    );

    if (ok != true || !mounted) return;

    try {
      await context.read<AppState>().api.post('/delegate/teams/${widget.teamId}/players', {
        'first_name': first.text.trim(),
        'last_name': last.text.trim(),
        'document_type': 'Cédula',
        'document_number': doc.text.trim(),
        'birthdate': birth.text.trim(),
        'gender': gender,
      });
      await _load();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Jugador agregado')));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.teamName)),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _addPlayer,
        icon: const Icon(Icons.person_add),
        label: const Text('Jugador'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
                  Text('${_players.length} jugadores', style: const TextStyle(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 12),
                  ..._players.map((item) {
                    final p = item as Map<String, dynamic>;
                    return Card(
                      child: ListTile(
                        title: Text('${p['first_name'] ?? ''} ${p['last_name'] ?? ''}'),
                        subtitle: Text('Cédula ${p['document_number'] ?? ''} · #${p['jersey_number'] ?? '—'}'),
                      ),
                    );
                  }),
                ],
              ),
            ),
    );
  }
}
