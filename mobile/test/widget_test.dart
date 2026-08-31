import 'package:flutter_test/flutter_test.dart';

import 'package:arena_players/main.dart';
import 'package:arena_players/state/app_state.dart';

void main() {
  testWidgets('welcome muestra los tres perfiles', (WidgetTester tester) async {
    final state = AppState();
    state.loading = false;

    await tester.pumpWidget(ArenaPlayersApp(appState: state));

    expect(find.text('Soy jugador'), findsOneWidget);
    expect(find.text('Soy delegado'), findsOneWidget);
    expect(find.text('Soy organizador / master'), findsOneWidget);
  });
}
