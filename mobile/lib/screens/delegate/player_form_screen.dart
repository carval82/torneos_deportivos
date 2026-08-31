import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';

class PlayerFormScreen extends StatefulWidget {
  const PlayerFormScreen({
    super.key,
    required this.teamId,
    this.tournamentId,
    this.player,
  });

  final int teamId;
  final int? tournamentId;
  final Map<String, dynamic>? player;

  @override
  State<PlayerFormScreen> createState() => _PlayerFormScreenState();
}

class _PlayerFormScreenState extends State<PlayerFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _picker = ImagePicker();

  late final TextEditingController _first;
  late final TextEditingController _last;
  late final TextEditingController _doc;
  late final TextEditingController _phone;
  late final TextEditingController _jersey;
  late final TextEditingController _position;
  late final TextEditingController _nationality;
  late final TextEditingController _email;

  String _documentType = 'Cédula';
  String _gender = 'masculino';
  DateTime? _birthdate;
  String? _photoPath;
  String? _docPhotoPath;
  bool _saving = false;

  bool get _editing => widget.player != null;

  @override
  void initState() {
    super.initState();
    final p = widget.player ?? {};
    _first = TextEditingController(text: p['first_name']?.toString() ?? '');
    _last = TextEditingController(text: p['last_name']?.toString() ?? '');
    _doc = TextEditingController(text: p['document_number']?.toString() ?? '');
    _phone = TextEditingController(text: p['phone']?.toString() ?? '');
    _jersey = TextEditingController(text: p['jersey_number']?.toString() ?? '');
    _position = TextEditingController(text: p['position']?.toString() ?? '');
    _nationality = TextEditingController(text: p['nationality']?.toString() ?? 'Colombia');
    _email = TextEditingController(text: p['email']?.toString() ?? '');
    _documentType = p['document_type']?.toString() ?? 'Cédula';
    _gender = p['gender']?.toString() ?? 'masculino';
    final birth = p['birthdate']?.toString();
    if (birth != null && birth.length >= 10) {
      _birthdate = DateTime.tryParse(birth.substring(0, 10));
    }
  }

  @override
  void dispose() {
    _first.dispose();
    _last.dispose();
    _doc.dispose();
    _phone.dispose();
    _jersey.dispose();
    _position.dispose();
    _nationality.dispose();
    _email.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final initial = _birthdate ?? DateTime(now.year - 18, now.month, now.day);
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(1950),
      lastDate: now,
      helpText: 'Fecha de nacimiento',
    );
    if (picked != null) setState(() => _birthdate = picked);
  }

  Future<void> _pick(ImageSource source, {required bool document}) async {
    final file = await _picker.pickImage(source: source, imageQuality: 85, maxWidth: 1600);
    if (file == null) return;
    setState(() {
      if (document) {
        _docPhotoPath = file.path;
      } else {
        _photoPath = file.path;
      }
    });
  }

  Future<void> _chooseSource({required bool document}) async {
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
    if (source != null) await _pick(source, document: document);
  }

  String get _birthLabel {
    if (_birthdate == null) return 'Elegí la fecha';
    final d = _birthdate!;
    return '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    if (_birthdate == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('La fecha de nacimiento es obligatoria.')),
      );
      return;
    }

    setState(() => _saving = true);
    try {
      final fields = <String, String>{
        'first_name': _first.text.trim(),
        'last_name': _last.text.trim(),
        'document_type': _documentType,
        'document_number': _doc.text.trim(),
        'birthdate': _birthLabel,
        'gender': _gender,
        'nationality': _nationality.text.trim(),
        if (_phone.text.trim().isNotEmpty) 'phone': _phone.text.trim(),
        if (_jersey.text.trim().isNotEmpty) 'jersey_number': _jersey.text.trim(),
        if (_position.text.trim().isNotEmpty) 'position': _position.text.trim(),
        if (_email.text.trim().isNotEmpty) 'email': _email.text.trim(),
        if (widget.tournamentId != null) 'tournament_id': '${widget.tournamentId}',
      };
      final jsonBody = Map<String, dynamic>.from(fields);

      final files = <String, String>{};
      if (_photoPath != null) files['photo'] = _photoPath!;
      if (_docPhotoPath != null) files['document_photo'] = _docPhotoPath!;

      final api = context.read<AppState>().api;
      final path = _editing
          ? '/delegate/teams/${widget.teamId}/players/${widget.player!['id']}'
          : '/delegate/teams/${widget.teamId}/players';

      if (_editing && files.isEmpty) {
        await api.put(path, jsonBody);
      } else if (_editing) {
        await api.postMultipart('$path/photos', fields: fields, files: files);
        await api.put(path, jsonBody);
      } else if (files.isEmpty) {
        await api.post(path, jsonBody);
      } else {
        await api.postMultipart(path, fields: fields, files: files);
      }

      if (!mounted) return;
      Navigator.pop(context, true);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No se pudo guardar el jugador.')),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_editing ? 'Editar jugador' : 'Nuevo jugador')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(
              controller: _first,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(labelText: 'Nombre *'),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Obligatorio' : null,
            ),
            TextFormField(
              controller: _last,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(labelText: 'Apellido *'),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Obligatorio' : null,
            ),
            DropdownButtonFormField<String>(
              value: _documentType,
              decoration: const InputDecoration(labelText: 'Tipo de documento *'),
              items: const [
                DropdownMenuItem(value: 'Cédula', child: Text('Cédula')),
                DropdownMenuItem(value: 'DNI', child: Text('DNI')),
                DropdownMenuItem(value: 'Pasaporte', child: Text('Pasaporte')),
              ],
              onChanged: (v) => setState(() => _documentType = v ?? 'Cédula'),
            ),
            TextFormField(
              controller: _doc,
              decoration: const InputDecoration(labelText: 'Número de documento *'),
              keyboardType: TextInputType.number,
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Obligatorio' : null,
            ),
            const SizedBox(height: 8),
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Fecha de nacimiento *'),
              subtitle: Text(_birthLabel),
              trailing: const Icon(Icons.calendar_month),
              onTap: _pickDate,
            ),
            DropdownButtonFormField<String>(
              value: _gender,
              decoration: const InputDecoration(labelText: 'Género *'),
              items: const [
                DropdownMenuItem(value: 'masculino', child: Text('Masculino')),
                DropdownMenuItem(value: 'femenino', child: Text('Femenino')),
                DropdownMenuItem(value: 'mixto', child: Text('Mixto')),
              ],
              onChanged: (v) => setState(() => _gender = v ?? 'masculino'),
            ),
            TextFormField(
              controller: _phone,
              decoration: const InputDecoration(labelText: 'Celular'),
              keyboardType: TextInputType.phone,
            ),
            TextFormField(
              controller: _jersey,
              decoration: const InputDecoration(labelText: 'N° de camiseta'),
              keyboardType: TextInputType.number,
            ),
            TextFormField(
              controller: _position,
              decoration: const InputDecoration(labelText: 'Posición'),
            ),
            TextFormField(
              controller: _nationality,
              decoration: const InputDecoration(labelText: 'Nacionalidad'),
            ),
            TextFormField(
              controller: _email,
              decoration: const InputDecoration(labelText: 'Correo (opcional)'),
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _chooseSource(document: false),
                    icon: const Icon(Icons.photo_camera),
                    label: Text(_photoPath == null ? 'Foto jugador' : 'Foto OK'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _chooseSource(document: true),
                    icon: const Icon(Icons.badge),
                    label: Text(_docPhotoPath == null ? 'Foto documento' : 'Doc OK'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _saving ? null : _save,
              child: Text(_saving ? 'Guardando…' : 'Guardar jugador'),
            ),
          ],
        ),
      ),
    );
  }
}
