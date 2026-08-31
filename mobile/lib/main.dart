import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'screens/home_gate.dart';
import 'state/app_state.dart';
import 'theme/arena_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final appState = AppState();
  await appState.bootstrap();
  runApp(ArenaPlayersApp(appState: appState));
}

class ArenaPlayersApp extends StatelessWidget {
  const ArenaPlayersApp({super.key, required this.appState});

  final AppState appState;

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider.value(
      value: appState,
      child: MaterialApp(
        title: 'Arena Players',
        debugShowCheckedModeBanner: false,
        theme: buildArenaTheme(),
        home: const HomeGate(),
      ),
    );
  }
}
