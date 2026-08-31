import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';
import 'player_form_screen.dart';

class RosterScreen extends StatefulWidget {
  const RosterScreen({super.key, required this.teamId, required this.teamName});

  final int teamId;
  final String teamName;

  @override
  State<RosterScreen> createState() => _RosterScreenState();
}

class _RosterScreenState extends State<RosterScreen> {
  List<Map<String, dynamic>> _players = [];
  Map<String, dynamic> _eligibility = {};
  Map<String, dynamic>? _rosterStatus;
  int? _tournamentId;
  bool _loading = true;
  String? _error;
  final _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Map<String, dynamic> _asMap(dynamic raw) {
    if (raw is Map) {
      return raw.map((key, value) => MapEntry(key.toString(), value));
    }
    if (raw is List) {
      final out = <String, dynamic>{};
      for (var i = 0; i < raw.length; i++) {
        if (raw[i] != null) out['$i'] = raw[i];
      }
      return out;
    }
    return {};
  }

  List<Map<String, dynamic>> _asPlayerList(dynamic raw) {
    if (raw is! List) return [];
    return raw.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final query = _tournamentId == null ? '' : '?tournament_id=$_tournamentId';
      final data = await context.read<AppState>().api.get(
            '/delegate/teams/${widget.teamId}/roster$query',
          );
      final map = _asMap(data);
      final team = _asMap(map['team']);
      final tournament = _asMap(map['tournament']);
      final players = _asPlayerList(map['players']).isNotEmpty
          ? _asPlayerList(map['players'])
          : _asPlayerList(team['players']);

      if (!mounted) return;
      setState(() {
        _players = players;
        _rosterStatus = map['roster_status'] is Map ? _asMap(map['roster_status']) : null;
        _eligibility = _asMap(map['eligibility']);
        _tournamentId = (tournament['id'] as num?)?.toInt() ?? _tournamentId;
        _error = null;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _error = e.message);
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = 'No se pudo cargar la plantilla.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _openForm([Map<String, dynamic>? player]) async {
    final saved = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => PlayerFormScreen(
          teamId: widget.teamId,
          tournamentId: _tournamentId,
          player: player,
        ),
      ),
    );
    if (saved == true) {
      await _load();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(player == null ? 'Jugador agregado' : 'Jugador actualizado')),
      );
    }
  }

  Future<void> _uploadPhotos(int playerId) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera),
              title: const Text('Cámara'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Galería'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return;
    final photo = await _picker.pickImage(source: source, imageQuality: 85, maxWidth: 1600);
    final doc = await _picker.pickImage(source: source, imageQuality: 85, maxWidth: 1600);
    if (photo == null && doc == null) return;
    if (!mounted) return;
    try {
      final files = <String, String>{};
      if (photo != null) files['photo'] = photo.path;
      if (doc != null) files['document_photo'] = doc.path;
      final api = context.read<AppState>().api;
      await api.postMultipart(
            '/delegate/teams/${widget.teamId}/players/$playerId/photos',
            fields: {
              if (_tournamentId != null) 'tournament_id': '$_tournamentId',
            },
            files: files,
          );
      await _load();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Fotos actualizadas')));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _requestException(int playerId) async {
    if (_tournamentId == null) return;
    try {
      await context.read<AppState>().api.post('/delegate/tournaments/$_tournamentId/exceptions', {
        'player_id': playerId,
        'team_id': widget.teamId,
        'reason': 'Jugador menor a la categoría. Solicito autorización del master.',
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Excepción enviada al master')),
      );
      await _load();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final open = _rosterStatus == null || _rosterStatus!['open'] == true;

    return Scaffold(
      appBar: AppBar(title: Text(widget.teamName)),
      floatingActionButton: open
          ? FloatingActionButton.extended(
              onPressed: () => _openForm(),
              icon: const Icon(Icons.person_add),
              label: const Text('Jugador'),
            )
          : null,
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
                children: [
                  if (_rosterStatus != null)
                    Card(
                      color: open ? const Color(0xFFF4F7F2) : const Color(0xFFFFF1F2),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Text(_rosterStatus!['message']?.toString() ?? ''),
                      ),
                    ),
                  if (_error != null) ...[
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                    const SizedBox(height: 8),
                    OutlinedButton(onPressed: _load, child: const Text('Reintentar')),
                  ],
                  Text(
                    '${_players.length} jugador${_players.length == 1 ? '' : 'es'}',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 12),
                  if (_players.isEmpty && _error == null)
                    const Card(
                      child: Padding(
                        padding: EdgeInsets.all(16),
                        child: Text('Todavía no hay jugadores. Tocá “Jugador” para cargar el primero.'),
                      ),
                    ),
                  ..._players.map((p) {
                    final id = (p['id'] as num?)?.toInt();
                    if (id == null) return const SizedBox.shrink();
                    final elig = _asMap(_eligibility['$id'] ?? _eligibility['$id.0']);
                    final eligible = elig.isEmpty || elig['eligible'] != false;
                    final reason = elig['reason']?.toString();
                    final name = (p['display_name'] ?? '${p['first_name'] ?? ''} ${p['last_name'] ?? ''}').toString().trim();
                    final jersey = p['jersey_number'];
                    final photo = p['photo_url']?.toString();
                    final details = [
                      if (jersey != null) '#$jersey',
                      '${p['document_type'] ?? 'Doc'} ${p['document_number'] ?? ''}'.trim(),
                      if (p['phone'] != null && p['phone'].toString().isNotEmpty) p['phone'].toString(),
                      if (p['position'] != null && p['position'].toString().isNotEmpty) p['position'].toString(),
                      if (p['age'] != null) '${p['age']} años',
                      if (!eligible && reason != null && reason.isNotEmpty) reason,
                    ].where((item) => item.toString().trim().isNotEmpty).join(' · ');

                    return Card(
                      child: ListTile(
                        leading: CircleAvatar(
                          backgroundColor: const Color(0xFF0B1F3A),
                          backgroundImage: photo != null && photo.isNotEmpty ? NetworkImage(photo) : null,
                          child: photo == null || photo.isEmpty
                              ? Text(
                                  name.isNotEmpty ? name[0].toUpperCase() : 'J',
                                  style: const TextStyle(color: Colors.white),
                                )
                              : null,
                        ),
                        title: Text(name.isEmpty ? 'Jugador' : name),
                        subtitle: Text(details),
                        isThreeLine: details.length > 42 || !eligible,
                        onTap: open ? () => _openForm(p) : null,
                        trailing: PopupMenuButton<String>(
                          onSelected: (value) {
                            if (value == 'edit') _openForm(p);
                            if (value == 'photos') _uploadPhotos(id);
                            if (value == 'exception') _requestException(id);
                          },
                          itemBuilder: (_) => [
                            if (open) const PopupMenuItem(value: 'edit', child: Text('Editar ficha')),
                            const PopupMenuItem(value: 'photos', child: Text('Tomar / subir fotos')),
                            if (!eligible)
                              const PopupMenuItem(value: 'exception', child: Text('Pedir excepción de edad')),
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
