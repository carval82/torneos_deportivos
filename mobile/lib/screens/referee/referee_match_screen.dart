import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';

class RefereeMatchScreen extends StatefulWidget {
  const RefereeMatchScreen({super.key, required this.gameId});

  final int gameId;

  @override
  State<RefereeMatchScreen> createState() => _RefereeMatchScreenState();
}

class _RefereeMatchScreenState extends State<RefereeMatchScreen> {
  Map<String, dynamic>? _game;
  bool _loading = true;
  bool _saving = false;
  String? _error;
  late final TextEditingController _home;
  late final TextEditingController _away;
  String _status = 'scheduled';

  @override
  void initState() {
    super.initState();
    _home = TextEditingController(text: '0');
    _away = TextEditingController(text: '0');
    _load();
  }

  @override
  void dispose() {
    _home.dispose();
    _away.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await context.read<AppState>().api.get('/referee/games/${widget.gameId}') as Map<String, dynamic>;
      _apply(data);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudo abrir el partido.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _apply(Map<String, dynamic> data) {
    _game = data;
    _home.text = (data['home_score'] ?? 0).toString();
    _away.text = (data['away_score'] ?? 0).toString();
    _status = (data['status'] as String?) ?? 'scheduled';
  }

  Future<void> _save({String? forceStatus}) async {
    if (!(_game?['can_edit'] as bool? ?? false)) {
      setState(() => _error = 'Este partido no está asignado a tu usuario.');
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final status = forceStatus ?? _status;
      final data = await context.read<AppState>().api.patch(
        '/referee/games/${widget.gameId}/score',
        {
          'home_score': int.tryParse(_home.text.trim()) ?? 0,
          'away_score': int.tryParse(_away.text.trim()) ?? 0,
          'status': status,
        },
      ) as Map<String, dynamic>;
      final game = data['game'];
      if (game is Map) {
        _apply(Map<String, dynamic>.from(game));
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(data['message']?.toString() ?? 'Resultado actualizado.')),
      );
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudo guardar el marcador.');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final home = (_game?['home_team'] as Map?)?['name'] ?? 'Local';
    final away = (_game?['away_team'] as Map?)?['name'] ?? 'Visita';
    final canEdit = _game?['can_edit'] as bool? ?? false;

    return Scaffold(
      appBar: AppBar(title: Text('$home vs $away')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(20),
              children: [
                Text(
                  (_game?['tournament'] as Map?)?['name']?.toString() ?? '',
                  style: TextStyle(color: Colors.grey.shade700),
                ),
                const SizedBox(height: 4),
                Text(
                  '${_game?['venue'] ?? ''} · ${_game?['referees_label'] ?? ''}',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
                const SizedBox(height: 24),
                Row(
                  children: [
                    Expanded(child: _ScoreBox(label: home.toString(), controller: _home, enabled: canEdit)),
                    const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 8),
                      child: Text('–', style: TextStyle(fontSize: 28, fontWeight: FontWeight.w800)),
                    ),
                    Expanded(child: _ScoreBox(label: away.toString(), controller: _away, enabled: canEdit)),
                  ],
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: _status,
                  decoration: const InputDecoration(labelText: 'Estado del partido'),
                  items: const [
                    DropdownMenuItem(value: 'scheduled', child: Text('Programado')),
                    DropdownMenuItem(value: 'live', child: Text('En juego')),
                    DropdownMenuItem(value: 'finished', child: Text('Finalizado')),
                    DropdownMenuItem(value: 'postponed', child: Text('Aplazado')),
                  ],
                  onChanged: canEdit ? (value) => setState(() => _status = value ?? _status) : null,
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(_error!, style: const TextStyle(color: Colors.red)),
                ],
                const SizedBox(height: 20),
                if (canEdit) ...[
                  FilledButton(
                    onPressed: _saving ? null : () => _save(forceStatus: 'live'),
                    child: const Text('Marcar en juego y guardar'),
                  ),
                  const SizedBox(height: 10),
                  ElevatedButton(
                    onPressed: _saving ? null : () => _save(),
                    child: _saving
                        ? const SizedBox(
                            height: 18,
                            width: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Guardar marcador'),
                  ),
                  const SizedBox(height: 10),
                  OutlinedButton(
                    onPressed: _saving ? null : () => _save(forceStatus: 'finished'),
                    child: const Text('Cerrar partido'),
                  ),
                ] else
                  const Text('Solo el árbitro asignado, el coordinador o el master pueden editar este marcador.'),
              ],
            ),
    );
  }
}

class _ScoreBox extends StatelessWidget {
  const _ScoreBox({
    required this.label,
    required this.controller,
    required this.enabled,
  });

  final String label;
  final TextEditingController controller;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(label, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          enabled: enabled,
          keyboardType: TextInputType.number,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 32, fontWeight: FontWeight.w800),
          decoration: const InputDecoration(border: OutlineInputBorder()),
        ),
      ],
    );
  }
}
