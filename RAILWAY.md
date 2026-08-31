# Deploy Arena Players en Railway

Repo: https://github.com/carval82/torneos_deportivos.git

## 1. Crear proyecto en Railway

1. [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub** → `carval82/torneos_deportivos`
2. Agregar plugin **PostgreSQL** al mismo proyecto
3. En el servicio web, conectar variables del Postgres

## 2. Variables de entorno (servicio web)

| Variable | Valor |
|----------|--------|
| `APP_NAME` | `Arena Players` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | generar con `php artisan key:generate --show` (local) |
| `APP_URL` | URL pública de Railway (`https://....up.railway.app`) |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | `${{Postgres-XXXX.DATABASE_URL}}` (referencia Railway; Laravel lee `DB_URL`) |
| `DATABASE_URL` | misma referencia (opcional, backup) |

**Importante:** no dejes `DB_HOST=127.0.0.1` ni `DB_USERNAME=root`. Eso fuerza localhost y rompe el deploy.
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `error` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `public` |

## 3. Build / start

Ya configurados:

- `nixpacks.toml` — Composer + npm build
- `scripts/railway-start.sh` — migrate + `artisan serve` en `$PORT`
- `railway.toml` — start command

## 4. Después del primer deploy

En Railway → servicio → **Settings** → generar dominio público.

Opcional seed demo (una sola vez, desde shell Railway):

```bash
php artisan db:seed --force
```

Admin demo del seeder: `pcapacho24@gmail.com` / `anaval33` (cambialo en producción).

## 5. URLs útiles

- Guest torneo: `/t/{slug}`
- Jugador: `/jugador/entrar` (solo cédula)
- API pública: `/api/public/tournaments/{slug}`
- API jugador: `POST /api/player/login` `{ "document_number": "..." }`
