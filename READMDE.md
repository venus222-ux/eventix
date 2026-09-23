# Laravel React Boilerplate — Laravel + React (Docker)

Boilerplate full-stack, rulat integral în Docker, pentru pornirea rapidă a unui proiect Laravel + React.

## Stack tehnic

| Componentă | Tehnologie |
|---|---|
| Backend | Laravel 12 + FrankenPHP |
| Frontend | React + Vite |
| Autentificare | JWT + HttpOnly Cookie |
| Autorizare | Spatie Roles & Permissions |
| Bază de date relațională | MySQL 8 |
| Bază de date documente | MongoDB 7 |
| Cache & Queue | Redis 7 |
| Search | Elasticsearch |
| Monitorizare cozi | Laravel Horizon |
| Observabilitate | Prometheus + Grafana |
| Admin DB | phpMyAdmin |

## Arhitectură — servicii Docker

```
laravel_app        → FrankenPHP, servește API-ul HTTP (:8000)
laravel_worker     → Horizon, procesează cozile Redis
frontend           → Vite dev server (:5173)
mysql              → date relaționale (:3307 host)
mongodb            → colecții documente (:27018 host)
redis              → queue + cache (:6380 host)
elasticsearch      → indexare full-text (:9200)
prometheus         → colectare metrici (:9090)
grafana            → dashboard-uri (:3000)
phpmyadmin         → UI admin MySQL (:8081)
```

## Pornire rapidă

```bash
git clone <repo>
cd laravel-react-boilerplate

# 1. Pornește totul
docker compose up -d --build

# 2. Backend setup
cp backend/.env.example backend/.env
docker compose exec laravel_app composer install
docker compose exec laravel_app php artisan key:generate
docker compose exec laravel_app php artisan jwt:secret
docker compose exec laravel_app php artisan migrate --seed

# 3. Frontend setup
cp frontend/.env.example frontend/.env
docker compose exec frontend npm install
docker compose up -d --force-recreate frontend
```

Verifică starea tuturor serviciilor:
```bash
docker compose ps
```
`laravel_app`, `mysql` ar trebui marcate `(healthy)`. `laravel_worker` nu are healthcheck (nu servește HTTP) — starea lui se verifică prin loguri, nu prin coloana STATUS.

Apoi deschide:

| Serviciu | URL |
|---|---|
| React (Vite) | http://localhost:5173 |
| Laravel API | http://localhost:8000 |
| Horizon | http://localhost:8000/horizon |
| phpMyAdmin | http://localhost:8081 |
| Grafana | http://localhost:3000 (user: `admin`) |
| Prometheus | http://localhost:9090 |
| Elasticsearch | http://localhost:9200 |
| MySQL (host) | localhost:3307 |
| MongoDB (host) | localhost:27018 |
| Redis (host) | localhost:6380 |

## Comenzi curente de dezvoltare

**Loguri live per serviciu:**
```bash
docker compose logs -f laravel_worker
docker compose logs -f laravel_app
```

**Log Laravel (aplicație):**
```bash
docker compose exec laravel_app tail -f storage/logs/laravel.log
```

**Rulare artisan:**
```bash
docker compose exec laravel_app php artisan <comanda>
docker compose exec laravel_app php artisan migrate
docker compose exec laravel_app php artisan optimize:clear
docker compose exec laravel_app php artisan horizon:status
docker compose exec laravel_app php artisan schedule:work
```

**Acces Horizon (monitor cozi):**
```
http://127.0.0.1:8000/horizon
```

**Config cache — de rulat după orice schimbare de `.env` sau `config/*.php`:**
```bash
docker compose exec laravel_app php artisan config:clear
docker compose exec laravel_worker php artisan config:clear
docker compose restart laravel_worker laravel_app
```

**Restart curat complet (elimină procese Horizon stale și config cache stale din imagine):**
```bash
docker compose down
docker compose build laravel_app laravel_worker
docker compose up -d --force-recreate
```

**Repornire doar Horizon (fără a recrea containerul):**
```bash
docker compose exec laravel_worker php artisan horizon:terminate
```
Supervisorul Horizon repornește automat workerii cu config proaspăt.

**Frontend:**
```bash
docker compose exec frontend npm install
docker compose exec frontend npm run build
```

## Autentificare

- API: JWT, guard `api`
- Autorizare: roluri și permisiuni via Spatie

## Configurație .env (important!)

### Backend (`backend/.env`)

```dotenv
DB_HOST=mysql
DB_PORT=3306
REDIS_HOST=redis
DB_MONGO_HOST=mongodb
ELASTICSEARCH_HOST=elasticsearch
```

### Frontend (`frontend/.env`)

```dotenv
VITE_API_URL=http://localhost:8000
```

> **Regulă simplă:**
>
> - Din **containere** → folosești numele serviciului (`mysql`, `redis`, `elasticsearch`...)
> - Din **browser** → folosești `localhost` + portul publicat

## Servicii Docker

| Service | Port host | Rol |
|---|---|---|
| `laravel_app` | 8000 | API + FrankenPHP |
| `laravel_worker` | — | Horizon (queues) |
| `frontend` | 5173 | React + Vite |
| `mysql` | 3307 | MySQL |
| `mongodb` | 27018 | MongoDB |
| `redis` | 6380 | Cache + Queue |
| `elasticsearch` | 9200 | Search |
| `prometheus` | 9090 | Metrics |
| `grafana` | 3000 | Dashboards |
| `phpmyadmin` | 8081 | Admin MySQL |

## Credențiale

**MySQL / phpMyAdmin**

- Database: `laravel_react_boilerplate`
- User: `laravel_react_boilerplate`
- Password: `secret`

**Grafana**

- User: `admin`

## Depanare rapidă

| Simptom | Verifică |
|---|---|
| `laravel_worker` apare unhealthy | Normal — nu servește HTTP; healthcheck dezactivat |
| Config nu se aplică după schimbare `.env` | `php artisan config:clear` + restart `laravel_app` + `laravel_worker`; dacă persistă, verifică dacă `config:cache` rulează în `Dockerfile` la build |
| `laravel_app`/`mysql` nu ajung `(healthy)` | Verifică `docker compose logs -f <service>` pentru erori de conexiune la pornire |