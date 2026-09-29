<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# Project notes

## Loading large product volumes (1M+ rows) for performance tests

The committed seeders (`CategorySeeder`, `ProductSeeder`) are factory-based and sized for normal
development (100 categories, 100,000 products). They are too slow for 1M+ rows. When the user asks
for a large data set, do NOT change the seeders permanently; load it with raw SQL as follows
(1M rows took ~36s to load + ~50s to build indexes, ~90s total, vs 5+ minutes with the factory):

1. Confirm the target is the local Sail database and that wiping it is fine (the steps below reset it).
2. `sail artisan migrate:fresh` to rebuild the schema, then seed categories: `sail artisan db:seed --class=CategorySeeder`.
3. **Drop the secondary indexes before loading.** Maintaining them per inserted row is what makes
   bulk loads slow. Roll back only the index migration:
   `sail artisan migrate:rollback --step=1 --path=database/migrations/<timestamp>_add_search_indexes_to_products_table.php`
   (`--path` needs the full relative path with `.php`; all migrations share batch 1 after `migrate:fresh`,
   so never run a plain `migrate:rollback`).
4. **Generate rows in SQL, not PHP.** Use `INSERT ... SELECT` from a number sequence, ~100k rows per
   statement (cross join of five `SELECT 0 ... SELECT 9` digit tables), executed with `DB::insert`
   (run it from a temporary seeder or `tinker`, and delete it afterwards). Per column:
   - `id`: time-ordered UUIDv7-style string so the primary key appends instead of splitting pages:
     `CONCAT(SUBSTR(LPAD(HEX(:baseMs + n),12,'0'),1,8),'-',SUBSTR(LPAD(HEX(:baseMs + n),12,'0'),9,4),'-7',LPAD(HEX(FLOOR(RAND()*4096)),3,'0'),'-',ELT(1+FLOOR(RAND()*4),'8','9','a','b'),LPAD(HEX(FLOOR(RAND()*4096)),3,'0'),'-',LPAD(HEX(FLOOR(RAND()*281474976710656)),12,'0'))`
     where `baseMs = now in ms + chunk offset`.
   - `name`: `CONCAT_WS(' ', w, w, w)` with `w = ELT(1+FLOOR(RAND()*200), <200 lorem words as bindings>)`
     (words from `Faker\Provider\Lorem::$wordList`, protected: read it with `ReflectionProperty`).
   - `category_id`: `ELT(1+FLOOR(RAND()*N), <category ids as bindings>)`.
   - `price`: `ROUND(1+RAND()*499,2)`, `stock`: `FLOOR(RAND()*101)`,
     `status`: `IF(RAND()<0.9,'active','inactive')`, timestamps `NOW()`.
5. **Rebuild the indexes after loading:** `sail artisan migrate` (re-applies the index migration).
6. Verify: row count, `count(distinct id)`, `SHOW INDEX FROM products`, and `EXPLAIN` on the queries under test.

Gotchas:
- MySQL names the foreign key's own index `products_category_id_foreign` (not Laravel's default
  `products_category_id_index`). It auto-drops that implicit index when another index starting with
  `category_id` is created, but not one created explicitly (the migration's `down()` recreates it
  explicitly, and its `up()` drops it if present).
- A composite `(category_id, status, name)` does NOT serve `category_id + name` without `status`
  (status blocks the name range), so `(category_id, name)` is kept as well.
- `LIKE '%term%'` cannot use a B-tree index; the product search uses a prefix match (`term%`).
- Stopping a background `sail artisan ...` from the host does not stop the process in the container;
  kill it with `docker exec <laravel.test container> pkill -f "artisan <command>"`.
- Do not commit unless the user asks.
