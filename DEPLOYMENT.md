# Deploy STKGears to Vercel

The project uses the Vercel **Container** preset. `Dockerfile.vercel` packages
PHP 8.4, FrankenPHP, PDO MySQL and mbstring. The HTTP document root is
`/app/public`; configuration, application source and SQL dumps are not served.

1. Connect `Pbkh042/STKGears`, production branch `main`, root directory `.`.
2. Use the **Container** framework preset. Clear static output/build overrides.
3. Deploy. `/health` checks PHP independently of the database.
4. Provision a reachable MySQL database and import `sql/stkgear.sql` with a
   database client. This dump contains demo accounts/data: replace them before
   accepting real customer traffic. Do not add a web-accessible import script.
5. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` in Vercel's project
   environment settings and redeploy. PHP reads these with `getenv()`;
   `.env.example` is documentation and is not loaded automatically.
6. Before enabling real accounts, configure a shared PHP session store
   (database or Redis). Container-local session files do not persist or share
   across Vercel instances.
7. Verify home, CSS/images, product listing, authentication and order creation.
   Requests to `/config/app.php`, `/sql/stkgear.sql`, `/api/common.php`,
   `/pages/...`, and unknown routes must return 404.

Without database environment variables the home, policy, catalog shell and
login page render, while database-dependent APIs return JSON with HTTP 503.
This is not a fully working shop until the MySQL and shared session setup is
complete. `/health` reports `databaseConfigured`, not database connectivity.

Local XAMPP setup still uses `php -S 127.0.0.1:8080` from the project root
with the existing local database defaults.
