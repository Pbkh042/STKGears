# Deploy STKGears to Vercel

The project uses the Vercel **Container** preset. `Dockerfile.vercel` packages
PHP 8.4, FrankenPHP, PDO MySQL and mbstring. The HTTP document root is
`/app/public`; configuration, application source and SQL dumps are not served.

1. Connect `Pbkh042/STKGears`, production branch `main`, root directory `.`.
2. Use the **Container** framework preset. Clear static output/build overrides.
3. Deploy. `/health` checks PHP independently of the database.
4. Provision an empty reachable MySQL database. The Aiven MySQL Free plan is
   compatible. Download its CA certificate. Set local process environment
   variables for the target database, including `DB_SSL_CA` pointing to that
   certificate, then run `php tools/import-cloud.php` from a trusted terminal.
   The importer refuses nonempty databases. It imports the schema, catalog and
   session table while excluding legacy accounts, password hashes and orders.
   `php tools/import-cloud.php --check` validates the import plan offline.
   Never expose the importer through HTTP.
5. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, and the certificate
   contents as a multiline `DB_SSL_CA_PEM` value in Vercel's project
   environment settings and redeploy. PHP reads these with `getenv()`;
   `.env.example` is documentation and is not loaded automatically.
6. Production automatically uses the MySQL `app_sessions` table. Session IDs
   are stored as hashes; a session-specific MySQL lock prevents simultaneous
   requests from overwriting each other. Cookie flags include Secure,
   HttpOnly and SameSite=Lax. `SESSION_LIFETIME` defaults to 7200 seconds.
   Users can register new accounts after the database is connected. Legacy
   demo passwords are not installed by the cloud importer.
7. Verify home, CSS/images, product listing, authentication and order creation.
   Requests to `/config/app.php`, `/sql/stkgear.sql`, `/api/common.php`,
   `/pages/...`, and unknown routes must return 404.

Without database environment variables the home, policy, catalog shell and
login page render, while database-dependent APIs return JSON with HTTP 503.
This is not a fully working shop until the MySQL and shared session setup is
complete. `/health` reports `databaseConfigured`, not database connectivity.

Local XAMPP setup still uses `php -S 127.0.0.1:8080` from the project root
with the existing local database defaults.
