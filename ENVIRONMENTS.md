# Multi-Environment Setup

This project supports three environments: **Local**, **Dev**, and **Prod**. All use a single base `docker-compose.yml` with environment-specific overrides.

## Architecture

```
docker-compose.yml (base configuration)
├── docker-compose.local.yml (local development override)
├── docker-compose.dev.yml (development server override)
└── docker-compose.prod.yml (production server override)

docker/
├── nginx.local.conf (local, no HTTPS redirect, hot reload)
├── nginx.dev.conf (dev, HTTPS redirect, relaxed CSP)
└── nginx.prod.conf (prod, strict HTTPS/security headers)
```

## Local Development

Run locally with hot reload and live file watching.

```bash
# Start local environment (uses docker-compose.local.yml)
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d

# Or use the override convention (same as above)
docker compose up -d

# Stop
docker compose down
```

**Features:**
- Port: `50025`
- Nginx config: `nginx.local.conf`
- Hot reload: PHP and static assets sync in real-time
- Network: Isolated local bridge network (not external)

## Development Server (dev.teslapp.feyli.dev)

Deploy to Kárkharos dev server on pushes to `development` branch.

```bash
# Manual deploy to dev (on Kárkharos)
export ENVIRONMENT=dev
export EXTERNAL_NETWORK=true
export PORT=50026
export WEBAPP_IP=172.22.0.7

docker compose -f docker-compose.yml -f docker-compose.dev.yml build --no-cache
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --force-recreate webapp
```

**Configuration:**
- Port: `50026`
- IP: `172.22.0.7` (on `teslapp_shared` network)
- Nginx config: `nginx.dev.conf`
- HTTPS redirect: ✓ Enabled
- CSP: Relaxed (`'unsafe-inline'` for dev tools)
- Automatic deploys: GitHub workflow on `development` branch push
- Secrets: `WEBAPP_ENV_DEV`

**GitHub Workflow:**
- File: `.github/workflows/deploy-dev.yml`
- Trigger: Pushes to `development` branch
- Required secret: `WEBAPP_ENV_DEV` (dev environment variables)

## Production Server (teslapp.feyli.dev)

Deploy to Kárkharos prod server on pushes to `main` branch.

```bash
# Manual deploy to prod (on Kárkharos)
export ENVIRONMENT=prod
export EXTERNAL_NETWORK=true
export PORT=50025
export WEBAPP_IP=172.22.0.6

docker compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --force-recreate webapp
```

**Configuration:**
- Port: `50025`
- IP: `172.22.0.6` (on `teslapp_shared` network)
- Nginx config: `nginx.prod.conf`
- HTTPS redirect: ✓ Enabled (with preload)
- CSP: Strict (no `'unsafe-inline'`)
- Hot reload: Disabled
- Restart policy: `always`
- Automatic deploys: GitHub workflow on `main` branch push
- Secrets: `WEBAPP_ENV`

**GitHub Workflow:**
- File: `.github/workflows/deploy.yml`
- Trigger: Pushes to `main` branch
- Required secret: `WEBAPP_ENV` (prod environment variables)

## Environment Variables

Each environment uses a separate `.env` file injected at deployment:

### Local Development
Create a `.env` file in the project root (gitignored):
```bash
cp .env.example .env
# Edit .env with local settings
```

### Server Deployments
Set GitHub secrets in your repository:
- `WEBAPP_ENV` — Production environment variables
- `WEBAPP_ENV_DEV` — Development environment variables

These are injected into `.env` at deploy time by the GitHub workflows.

## Nginx Configuration

Each environment has a dedicated config with security headers tailored to its needs:

### `nginx.local.conf`
- No HTTPS redirect (local development)
- Relaxed CSP: `'unsafe-inline'` for Browsersync, `ws:/wss:` for sockets
- Gzip: Level 5 (balanced)

### `nginx.dev.conf`
- HTTPS redirect enabled
- Relaxed CSP: `'unsafe-inline'` for dev tools
- Gzip: Level 5

### `nginx.prod.conf`
- HTTPS redirect with preload
- Strict CSP: No `'unsafe-inline'`
- Higher gzip compression: Level 6
- Stricter HSTS header

## Deploying Changes

### Local Development
1. Make code changes
2. Files auto-sync via Docker watch
3. Opcache flushes on PHP changes
4. No explicit deploy needed

### Development (dev.teslapp.feyli.dev)
1. Push to `development` branch
2. GitHub workflow triggers automatically
3. New Docker image built and deployed
4. Old container replaced with minimal downtime

### Production (teslapp.feyli.dev)
1. Merge to `main` branch (via pull request)
2. GitHub workflow triggers automatically
3. New Docker image built and deployed
4. Old container replaced with minimal downtime

## Verification

After deployment, the GitHub workflows run a health check:
```bash
curl -f http://localhost:PORT/
```

If this fails, the workflow exits with code 1 and alerts you.

## Troubleshooting

### Check running containers
```bash
docker compose ps
```

### View logs
```bash
# All services
docker compose logs -f

# Just the webapp
docker compose logs -f webapp
```

### Access shell in running container
```bash
docker compose exec webapp sh
```

### Rebuild without cache
```bash
# Local
docker compose build --no-cache

# Dev
export ENVIRONMENT=dev
docker compose -f docker-compose.yml -f docker-compose.dev.yml build --no-cache
```

### Network issues on Kárkharos

If containers can't communicate, verify the shared network exists:
```bash
docker network inspect teslapp_shared
```

If missing, create it:
```bash
docker network create --subnet 172.22.0.0/16 teslapp_shared
```
