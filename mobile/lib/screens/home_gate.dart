import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../state/app_state.dart';
import 'delegate/delegate_shell.dart';
import 'organizer/tournaments_screen.dart';
import 'player/player_home_screen.dart';
import 'welcome_screen.dart';

class HomeGate extends StatelessWidget {
  const HomeGate({super.key});

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();

    if (state.loading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (!state.isLoggedIn) {
      return const WelcomeScreen();
    }

    switch (state.role) {
      case 'player':
        return const PlayerHomeScreen();
      case 'delegate':
        return const DelegateShell();
      case 'admin':
      case 'organizer':
        return const TournamentsScreen(mode: TournamentListMode.organizer);
      default:
        return const WelcomeScreen();
    }
  }
}
