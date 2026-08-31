import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
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
  Map<String, dynamic>? _rosterStatus;
  Map<String, dynamic> _eligibility = {};
  int? _tournamentId;
  bool _loading = true;
  String? _error;
  final _picker = ImagePicker();

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
      final path = _tournamentId == null
          ? '/delegate/teams/${widget.teamId}/roster'
          : '/delegate/teams/${widget.teamId}/roster?tournament_id=$_tournamentId';
      final data = await context.read<AppState>().api.get(path) as Map<String, dynamic>;
      final team = data['team'] as Map<String, dynamic>?;
      final tournament = data['tournament'] as Map<String, dynamic>?;
      setState(() {
        _players = (team?['players'] as List?) ?? [];
        _rosterStatus = data['roster_status'] as Map<String, dynamic>?;
        _eligibility = Map<String, dynamic>.from(data['eligibility'] as Map? ?? {});
        _tournamentId = (tournament?['id'] as num?)?.toInt() ?? _tournamentId;
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudo cargar la plantilla.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<String?> _pick(ImageSource source) async {
    final file = await _picker.pickImage(source: source, imageQuality: 85, maxWidth: 1600);
    return file?.path;
  }

  Future<void> _addPlayer() async {
    final first = TextEditingController();
    final last = TextEditingController();
    final doc = TextEditingController();
    final birth = TextEditingController(text: '2005-01-01');
    String gender = 'masculino';
    String? photoPath;
    String? docPhotoPath;

    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setLocal) => AlertDialog(
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
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () async {
                          final path = await _pick(ImageSource.camera);
                          if (path != null) setLocal(() => photoPath = path);
                        },
                        icon: const Icon(Icons.photo_camera),
                        label: Text(photoPath == null ? 'Foto jugador' : 'Foto OK'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () async {
                          final path = await _pick(ImageSource.camera);
                          if (path != null) setLocal(() => docPhotoPath = path);
                        },
                        icon: const Icon(Icons.badge),
                        label: Text(docPhotoPath == null ? 'Foto doc' : 'Doc OK'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancelar')),
            FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Guardar')),
          ],
        ),
      ),
    );

    if (ok != true || !mounted) return;

    try {
      final fields = <String, String>{
        'first_name': first.text.trim(),
        'last_name': last.text.trim(),
        'document_type': 'Cédula',
        'document_number': doc.text.trim(),
        'birthdate': birth.text.trim(),
        'gender': gender,
      };
      if (_tournamentId != null) {
        fields['tournament_id'] = '$_tournamentId';
      }
      final files = <String, String>{};
      if (photoPath != null) files['photo'] = photoPath!;
      if (docPhotoPath != null) files['document_photo'] = docPhotoPath!;

      if (files.isEmpty) {
        await context.read<AppState>().api.post('/delegate/teams/${widget.teamId}/players', {
          ...fields,
          if (_tournamentId != null) 'tournament_id': _tournamentId,
        });
      } else {
        await context.read<AppState>().api.postMultipart(
              '/delegate/teams/${widget.teamId}/players',
              fields: fields,
              files: files,
            );
      }
      await _load();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Jugador agregado')));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _uploadPhotos(int playerId) async {
    final photo = await _pick(ImageSource.camera);
    final doc = await _pick(ImageSource.camera);
    if (photo == null && doc == null) return;
    try {
      final files = <String, String>{};
      if (photo != null) files['photo'] = photo;
      if (doc != null) files['document_photo'] = doc;
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
    final api = context.read<AppState>().api;
    try {
      await api.post('/delegate/tournaments/$_tournamentId/exceptions', {
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
              onPressed: _addPlayer,
              icon: const Icon(Icons.person_add),
              label: const Text('Jugador'),
            )
          : null,
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (_rosterStatus != null)
                    Card(
                      color: open ? const Color(0xFFF4F7F2) : const Color(0xFFFFF1F2),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Text(_rosterStatus!['message']?.toString() ?? ''),
                      ),
                    ),
                  if (_error != null) Text(_error!, style: const TextStyle(color: Colors.red)),
                  Text('${_players.length} jugadores', style: const TextStyle(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 12),
                  ..._players.map((item) {
                    final p = item as Map<String, dynamic>;
                    final id = (p['id'] as num).toInt();
                    final elig = _eligibility['$id'] as Map?;
                    final eligible = elig?['eligible'] != false;
                    final reason = elig?['reason']?.toString();
                    return Card(
                      child: ListTile(
                        title: Text('${p['first_name'] ?? ''} ${p['last_name'] ?? ''}'),
                        subtitle: Text(
                          [
                            'Cédula ${p['document_number'] ?? ''}',
                            if (!eligible && reason != null) reason,
                            if (elig?['warnings'] is List && (elig!['warnings'] as List).isNotEmpty)
                              (elig['warnings'] as List).first.toString(),
                          ].join('\n'),
                        ),
                        isThreeLine: !eligible,
                        trailing: PopupMenuButton<String>(
                          onSelected: (value) {
                            if (value == 'photos') _uploadPhotos(id);
                            if (value == 'exception') _requestException(id);
                          },
                          itemBuilder: (_) => [
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
