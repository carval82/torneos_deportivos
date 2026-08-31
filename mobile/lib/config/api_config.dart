class ApiConfig {
  /// Producción Railway. Para local: http://10.0.2.2:8000/api (emulador Android)
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://torneosdeportivos-production.up.railway.app/api',
  );
}
