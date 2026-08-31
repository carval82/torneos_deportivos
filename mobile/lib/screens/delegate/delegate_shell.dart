import 'package:flutter/material.dart';

import 'teams_screen.dart';
import '../organizer/tournaments_screen.dart';

/// Home del delegado: plantillas de equipo + entrada a sus torneos.
class DelegateShell extends StatefulWidget {
  const DelegateShell({super.key});

  @override
  State<DelegateShell> createState() => _DelegateShellState();
}

class _DelegateShellState extends State<DelegateShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _index,
        children: const [
          TeamsScreen(),
          TournamentsScreen(mode: TournamentListMode.delegate),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (value) => setState(() => _index = value),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.groups_outlined),
            selectedIcon: Icon(Icons.groups),
            label: 'Equipos',
          ),
          NavigationDestination(
            icon: Icon(Icons.emoji_events_outlined),
            selectedIcon: Icon(Icons.emoji_events),
            label: 'Torneos',
          ),
        ],
      ),
    );
  }
}
