# CodeForge 2.0 — Optimized Plain PHP Build

A modernized competitive-programming / DBMS software-lab project built with **plain PHP 8.2+ + MariaDB/MySQL + HTML/CSS/JavaScript**. No frontend or backend framework is used in this phase.

## Major modules

- Secure login + role-based admin access
- Dashboard with live metrics
- Problems + safe prototype submission workflow
- Contests + contest problem sets + leaderboard
- Rivalry comparison
- University analytics
- Global search and profiles
- **Code DNA** — data-driven performance fingerprint and archetype
- **Ghost Race** — race against historical solver timelines
- **SQL Battle Arena** — read-only SQL challenges with correctness/speed/efficiency scoring
- Secure admin data console

## Important safety decision

The current problem/ghost judge does **not execute arbitrary source code**. It uses a simple prototype judge to generate realistic AC/WA/TLE/CE-style flows safely. A containerized isolated judge should replace this service when the project is migrated to frameworks.

The SQL Battle validator accepts only SELECT/CTE queries and restricts access to four `arena_*` sandbox tables.

## XAMPP setup

1. Put this folder at `D:\xampp\htdocs\codeforge`.
2. Start Apache + MySQL in XAMPP.
3. Open phpMyAdmin and create database `project`.
4. Select `project` → **Import** → import `database/schema_and_seed.sql`.
   - The script rebuilds the CodeForge schema and demo dataset.
5. Copy `config/.env.example` to `config/.env` if you need non-default DB settings.
6. Default XAMPP settings are supported; CodeForge probes `3307` then `3306` unless `DB_PORT` is set explicitly. Standard XAMPP is usually `127.0.0.1:3306`, database `project`, user `root`, blank password.
7. Visit `http://localhost/codeforge/` for the dedicated public landing page.
8. Create an account or log in; authenticated users enter `dashboard.php`.

### Demo accounts

- User: `Ismail` / `123456`
- Admin: `Admin` / `admin123`

## If your XAMPP MySQL uses port 3307

Create `config/.env` with:

```ini
DB_HOST=127.0.0.1
DB_PORT=3307
DB_NAME=project
DB_USER=root
DB_PASS=
```

## Tests

From Command Prompt:

```bat
cd D:\xampp\htdocs\codeforge
D:\xampp\php\php.exe tests\run.php
```

Then lint all PHP files in PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & D:\xampp\php\php.exe -l $_.FullName }
```

## Architecture

```text
Pages / reusable includes
          ↓
Services + repositories
          ↓
PDO prepared statements
          ↓
MariaDB / MySQL
```

Feature services are intentionally separated so that later migration to React + Laravel (or another required framework pair) can preserve the feature logic.

## Optimization pass

The current build includes aggregate Code DNA queries, query-specific indexes, bounded SQL result sets, independent sidebar scrolling, cache-busted local assets, proper JSON API authentication, stronger session headers, responsive radar rendering, reduced-motion support, and visibility-aware animation loops. The UI uses **Space Grotesk** for readable futuristic typography and **JetBrains Mono** for code/data surfaces.
