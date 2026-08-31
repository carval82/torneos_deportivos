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
  Map<String, dynamic>? _rules;
  List<dynamic> _games = [];
  List<dynamic> _standings = [];
  List<dynamic> _scorers = [];
  List<dynamic> _upcoming = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 5, vsync: this);
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
      Map<String, dynamic>? rules;
      try {
        rules = await api.get('/t/${widget.slug}/rules') as Map<String, dynamic>;
      } on ApiException {
        rules = null;
      }
      if (!mounted) return;
      setState(() {
        _summary = summary;
        _rules = rules;
        _games = (fixture['games'] as List?) ?? [];
        _standings = (standings['standings'] as List?) ?? (summary['standings'] as List?) ?? [];
        _scorers = (scorers['scorers'] as List?) ?? (summary['scorers'] as List?) ?? [];
        _upcoming = (summary['upcoming'] as List?) ?? [];
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
    final tournament = _summary?['tournament'] is Map
        ? Map<String, dynamic>.from(_summary!['tournament'] as Map)
        : <String, dynamic>{};
    final title = widget.title ?? tournament['name']?.toString() ?? 'Torneo';

    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        bottom: TabBar(
          controller: _tabs,
          isScrollable: true,
          tabs: const [
            Tab(text: 'Inicio'),
            Tab(text: 'Fixture'),
            Tab(text: 'Tabla'),
            Tab(text: 'Goles'),
            Tab(text: 'Reglamento'),
          ],
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_error!, style: const TextStyle(color: Colors.red), textAlign: TextAlign.center),
                        const SizedBox(height: 12),
                        FilledButton(onPressed: _load, child: const Text('Reintentar')),
                      ],
                    ),
                  ),
                )
              : TabBarView(
                  controller: _tabs,
                  children: [
                    _HomeTab(
                      tournament: tournament,
                      upcoming: _upcoming,
                      onOpenFixture: () => _tabs.animateTo(1),
                      onOpenStandings: () => _tabs.animateTo(2),
                    ),
                    _FixtureTab(games: _games),
                    _StandingsTab(rows: _standings),
                    _ScorersTab(rows: _scorers),
                    _RulesTab(rules: _rules, tournament: tournament),
                  ],
                ),
    );
  }
}

class _HomeTab extends StatelessWidget {
  const _HomeTab({
    required this.tournament,
    required this.upcoming,
    required this.onOpenFixture,
    required this.onOpenStandings,
  });

  final Map<String, dynamic> tournament;
  final List<dynamic> upcoming;
  final VoidCallback onOpenFixture;
  final VoidCallback onOpenStandings;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          color: const Color(0xFFF4F7F2),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(tournament['name']?.toString() ?? 'Torneo',
                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700)),
                const SizedBox(height: 6),
                Text([
                  tournament['status']?.toString() ?? '',
                  if (tournament['complex_name'] != null) tournament['complex_name'].toString(),
                  if (tournament['sport'] is Map) (tournament['sport'] as Map)['name']?.toString() ?? '',
                ].where((e) => e.trim().isNotEmpty).join(' · ')),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: FilledButton.icon(
                onPressed: onOpenFixture,
                icon: const Icon(Icons.calendar_month),
                label: const Text('Fixture'),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: OutlinedButton.icon(
                onPressed: onOpenStandings,
                icon: const Icon(Icons.table_chart),
                label: const Text('Tabla'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 20),
        const Text('Próximos partidos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        if (upcoming.isEmpty)
          const Text('Sin partidos pendientes.')
        else
          ...upcoming.take(8).map((item) {
            final g = Map<String, dynamic>.from(item as Map);
            final home = g['home_team'] ?? g['homeTeam'];
            final away = g['away_team'] ?? g['awayTeam'];
            final homeName = home is Map ? home['name'] : 'Local';
            final awayName = away is Map ? away['name'] : 'Visita';
            final when = g['scheduled_at']?.toString() ?? '';
            final time = when.length >= 16 ? when.replaceFirst('T', ' ').substring(11, 16) : when;
            final field = g['field_name']?.toString() ?? g['venue']?.toString() ?? 'Sin cancha';
            return Card(
              child: ListTile(
                title: Text('$homeName vs $awayName'),
                subtitle: Text('Fecha ${g['matchday'] ?? '—'} · $time · $field'),
              ),
            );
          }),
      ],
    );
  }
}

class _RulesTab extends StatelessWidget {
  const _RulesTab({required this.rules, required this.tournament});
  final Map<String, dynamic>? rules;
  final Map<String, dynamic> tournament;

  @override
  Widget build(BuildContext context) {
    final summary = rules?['rules_summary']?.toString() ?? tournament['rules_summary']?.toString();
    final body = rules?['rules']?.toString() ?? tournament['rules']?.toString();
    final narrative = rules?['narrative']?.toString();

    if ((summary == null || summary.isEmpty) && (body == null || body.isEmpty)) {
      return const Center(child: Text('El reglamento todavía no está publicado.'));
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (summary != null && summary.isNotEmpty)
          Card(
            color: const Color(0xFFF4F7F2),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Text(summary, style: const TextStyle(fontWeight: FontWeight.w600)),
            ),
          ),
        if (narrative != null && narrative.isNotEmpty) ...[
          const SizedBox(height: 12),
          Text(narrative),
        ],
        if (body != null && body.isNotEmpty) ...[
          const SizedBox(height: 16),
          Text(body),
        ],
      ],
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

  String _timeLabel(Map<String, dynamic> g) {
    final raw = g['scheduled_at']?.toString() ?? '';
    if (raw.length >= 16) {
      final cleaned = raw.replaceFirst('T', ' ');
      return cleaned.substring(11, 16);
    }
    return 'Sin hora';
  }

  String _dateLabel(Map<String, dynamic> g) {
    final raw = g['scheduled_at']?.toString() ?? '';
    if (raw.length >= 10) return raw.substring(0, 10);
    return '';
  }

  String _fieldLabel(Map<String, dynamic> g) {
    final field = g['field_name']?.toString();
    if (field != null && field.isNotEmpty) return field;
    final venue = g['venue']?.toString();
    if (venue != null && venue.isNotEmpty) return venue;
    return 'Sin cancha';
  }

  @override
  Widget build(BuildContext context) {
    if (games.isEmpty) return const Center(child: Text('Sin fixture.'));

    final sorted = [...games.cast<Map<String, dynamic>>()]
      ..sort((a, b) {
        final md = (a['matchday'] as num? ?? 0).compareTo(b['matchday'] as num? ?? 0);
        if (md != 0) return md;
        return (a['scheduled_at']?.toString() ?? '').compareTo(b['scheduled_at']?.toString() ?? '');
      });

    final byMatchday = <int, List<Map<String, dynamic>>>{};
    for (final g in sorted) {
      final md = (g['matchday'] as num?)?.toInt() ?? 0;
      byMatchday.putIfAbsent(md, () => []).add(g);
    }

    return ListView(
      padding: const EdgeInsets.all(12),
      children: byMatchday.entries.map((entry) {
        final matchdayGames = entry.value;
        final slots = <String, List<Map<String, dynamic>>>{};
        for (final g in matchdayGames) {
          slots.putIfAbsent(_timeLabel(g), () => []).add(g);
        }
        final times = slots.keys.where((t) => t != 'Sin hora').toList();

        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Fecha ${entry.key}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Text(
                  [
                    if (_dateLabel(matchdayGames.first).isNotEmpty) _dateLabel(matchdayGames.first),
                    '${matchdayGames.length} partidos',
                    if (times.length > 1) '${times.length} turnos: ${times.join(' · ')}',
                    if (times.length == 1) 'turno ${times.first}',
                  ].join(' · '),
                  style: TextStyle(color: Colors.black.withOpacity(0.55), fontSize: 13),
                ),
                const SizedBox(height: 10),
                ...slots.entries.map((slot) {
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: double.infinity,
                        margin: const EdgeInsets.only(top: 6, bottom: 6),
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF4F7F2),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          slot.key == 'Sin hora' ? 'Sin horario' : 'Turno ${slot.key}',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                      ...slot.value.map((g) {
                        final home = g['home_team'] ?? g['homeTeam'];
                        final away = g['away_team'] ?? g['awayTeam'];
                        final homeName = home is Map ? home['name'] : 'Local';
                        final awayName = away is Map ? away['name'] : 'Visita';
                        final score = g['status'] == 'finished'
                            ? '${g['home_score'] ?? 0} – ${g['away_score'] ?? 0}'
                            : 'Pendiente';
                        return ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text('$homeName vs $awayName'),
                          subtitle: Text('${_timeLabel(g)} · ${_fieldLabel(g)}'),
                          trailing: Text(score, style: const TextStyle(fontWeight: FontWeight.w700)),
                        );
                      }),
                    ],
                  );
                }),
              ],
            ),
          ),
        );
      }).toList(),
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
