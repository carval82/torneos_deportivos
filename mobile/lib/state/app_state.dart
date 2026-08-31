import 'package:flutter/foundation.dart';

import '../services/api_client.dart';
import '../services/session_store.dart';

class AppState extends ChangeNotifier {
  AppState({ApiClient? api, SessionStore? store})
      : api = api ?? ApiClient(),
        store = store ?? SessionStore();

  final ApiClient api;
  final SessionStore store;

  bool loading = true;
  String? token;
  String? role;
  String? name;

  bool get isLoggedIn => token != null && token!.isNotEmpty;

  Future<void> bootstrap() async {
    loading = true;
    notifyListeners();
    token = await store.token();
    role = await store.role();
    name = await store.name();
    api.token = token;
    loading = false;
    notifyListeners();
  }

  Future<void> setSession({
    required String token,
    required String role,
    required String name,
  }) async {
    this.token = token;
    this.role = role;
    this.name = name;
    api.token = token;
    await store.save(token: token, role: role, name: name);
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      if (token != null) {
        await api.post('/logout', {});
      }
    } catch (_) {}
    token = null;
    role = null;
    name = null;
    api.token = null;
    await store.clear();
    notifyListeners();
  }

  Future<Map<String, dynamic>> loginPlayer(String documentNumber) async {
    final data = await api.post(
      '/player/login',
      {
        'document_number': documentNumber.trim(),
        'device_name': 'flutter',
      },
      auth: false,
    ) as Map<String, dynamic>;

    final user = data['user'] as Map<String, dynamic>? ?? {};
    await setSession(
      token: data['token'] as String,
      role: (user['role'] as String?) ?? 'player',
      name: (user['name'] as String?) ?? 'Jugador',
    );
    return data;
  }

  Future<Map<String, dynamic>> loginEmail(String email, String password) async {
    final data = await api.post(
      '/login',
      {
        'email': email.trim(),
        'password': password,
        'device_name': 'flutter',
      },
      auth: false,
    ) as Map<String, dynamic>;

    final user = data['user'] as Map<String, dynamic>? ?? {};
    await setSession(
      token: data['token'] as String,
      role: (user['role'] as String?) ?? 'organizer',
      name: (user['name'] as String?) ?? 'Usuario',
    );
    return data;
  }
}
