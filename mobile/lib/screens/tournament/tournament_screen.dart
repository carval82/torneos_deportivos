import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../services/api_client.dart';
import '../../state/app_state.dart';

class TournamentScreen extends StatefulWidget {
  const TournamentScreen({super.key, required this.slug, this.title});

  final String slug;
  final String? title;

  @override
  State<TournamentScreen> createState() => _TournamentScreenState();
}

class _TournamentScreenState extends State<TournamentScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabs;
  Map<String, dynamic>? _summary;
  List<dynamic> _games = [];
  List<dynamic> _standings = [];
  List<dynamic> _scorers = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 3, vsync: this);
    _load();
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final api = context.read<AppState>().api;
    try {
      final summary = await api.get('/t/${widget.slug}') as Map<String, dynamic>;
      final fixture = await api.get('/t/${widget.slug}/fixture') as Map<String, dynamic>;
      final standings = await api.get('/t/${widget.slug}/standings') as Map<String, dynamic>;
      final scorers = await api.get('/t/${widget.slug}/scorers') as Map<String, dynamic>;
      setState(() {
        _summary = summary;
        _games = (fixture['games'] as List?) ?? [];
        _standings = (standings['standings'] as List?) ?? (summary['standings'] as List?) ?? [];
        _scorers = (scorers['scorers'] as List?) ?? (summary['scorers'] as List?) ?? [];
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } catch (_) {
      setState(() => _error = 'No se pudo cargar el torneo.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.title ??
        (_summary?['tournament'] is Map ? (_summary!['tournament'] as Map)['name']?.toString() : null) ??
        'Torneo';

    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        bottom: TabBar(
          controller: _tabs,
          tabs: const [
            Tab(text: 'Tabla'),
            Tab(text: 'Fixture'),
            Tab(text: 'Goles'),
          ],
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: Colors.red)))
              : TabBarView(
                  controller: _tabs,
                  children: [
                    _StandingsTab(rows: _standings),
                    _FixtureTab(games: _games),
                    _ScorersTab(rows: _scorers),
                  ],
                ),
    );
  }
}

class _StandingsTab extends StatelessWidget {
  const _StandingsTab({required this.rows});
  final List<dynamic> rows;

  @override
  Widget build(BuildContext context) {
    if (rows.isEmpty) return const Center(child: Text('Sin tabla todavía.'));
    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: rows.length,
      itemBuilder: (context, i) {
        final row = rows[i] as Map<String, dynamic>;
        final team = row['team'];
        final name = team is Map ? (team['name'] ?? team['short_name']) : row['team_name'];
        return Card(
          child: ListTile(
            leading: CircleAvatar(child: Text('${row['position'] ?? i + 1}')),
            title: Text(name?.toString() ?? 'Equipo'),
            trailing: Text('${row['points'] ?? 0} pts', style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle: Text('PJ ${row['played'] ?? 0} · DG ${row['goal_difference'] ?? 0}'),
          ),
        );
      },
    );
  }
}

class _FixtureTab extends StatelessWidget {
  const _FixtureTab({required this.games});
  final List<dynamic> games;

  @override
  Widget build(BuildContext context) {
    if (games.isEmpty) return const Center(child: Text('Sin fixture.'));
    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: games.length,
      itemBuilder: (context, i) {
        final g = games[i] as Map<String, dynamic>;
        final home = g['home_team'] ?? g['homeTeam'];
        final away = g['away_team'] ?? g['awayTeam'];
        final homeName = home is Map ? home['name'] : 'Local';
        final awayName = away is Map ? away['name'] : 'Visita';
        final score = g['status'] == 'finished'
            ? '${g['home_score'] ?? 0} – ${g['away_score'] ?? 0}'
            : 'Pendiente';
        return Card(
          child: ListTile(
            title: Text('$homeName vs $awayName'),
            subtitle: Text('Fecha ${g['matchday'] ?? '—'} · ${g['scheduled_at'] ?? ''}'),
            trailing: Text(score, style: const TextStyle(fontWeight: FontWeight.w700)),
          ),
        );
      },
    );
  }
}

class _ScorersTab extends StatelessWidget {
  const _ScorersTab({required this.rows});
  final List<dynamic> rows;

  @override
  Widget build(BuildContext context) {
    if (rows.isEmpty) return const Center(child: Text('Sin goleadores.'));
    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: rows.length,
      itemBuilder: (context, i) {
        final row = rows[i] as Map<String, dynamic>;
        final player = row['player'];
        final team = row['team'];
        final playerName = player is Map
            ? '${player['first_name'] ?? ''} ${player['last_name'] ?? ''}'.trim()
            : 'Jugador';
        final teamName = team is Map ? team['name'] : '';
        return Card(
          child: ListTile(
            leading: CircleAvatar(child: Text('${row['position'] ?? i + 1}')),
            title: Text(playerName),
            subtitle: Text(teamName?.toString() ?? ''),
            trailing: Text('${row['goals'] ?? 0}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
          ),
        );
      },
    );
  }
}
