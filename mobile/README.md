# Arena Players · App Flutter

App móvil para **jugador**, **delegado** y **organizador/master**.

## API

Por defecto apunta a producción:

`https://torneosdeportivos-production.up.railway.app/api`

Local (emulador Android):

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
```

## Roles

| Perfil | Entrada | Pantallas |
|--------|---------|-----------|
| Jugador | Cédula | Fechas, resultados, torneos |
| Delegado | Email + password | Equipos y plantilla |
| Organizador / Master | Email + password | Lista de torneos + tabla/fixture |

## Correr

```bash
cd mobile
flutter pub get
flutter run
```

## Build Android

```bash
flutter build apk --release
```
