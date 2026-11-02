# STKGears: Vercel + Firebase

Production runs PHP 8.4 with Vercel's Container preset, Dockerfile.vercel and
FrankenPHP. Only /app/public is an HTTP document root. Firebase Authentication
handles email/password accounts; Realtime Database stores products, roles,
orders and shared PHP sessions. MySQL remains available for local XAMPP.

## Deployment process

1. Create a Firebase project and web app. Enable Authentication's Email/Password
   provider. Create Realtime Database in locked mode near the application.
   Firebase Hosting is not required.
2. Run php tools/configure-firebase-rules.php to deploy the database rules.
   Public reads/writes remain denied;
   only the PHP server's service account accesses the database. The expiry
   index supports session cleanup. Do not enable test-mode rules.
3. Create a service-account key in Project settings > Service accounts.
   Keep the JSON confidential; never commit it. Set Production variables in
   Vercel: DATA_BACKEND=firebase, FIREBASE_DATABASE_URL, FIREBASE_WEB_API_KEY,
   FIREBASE_DATA_NAMESPACE=stkgears, SESSION_LIFETIME=7200 and
   FIREBASE_SERVICE_ACCOUNT_JSON. Store the JSON variable as Sensitive.
   An existing FIREBASE_DATABASE_SECRET is also supported as a server-only
   Sensitive variable instead of the JSON. Firebase deprecates this legacy
   credential; prefer service-account OAuth when rotating credentials later.
   Do not reuse production credentials/data namespaces for previews.
4. Load these variables into a trusted terminal process and run
   php tools/seed-firebase.php. It imports 13 products from firebase/catalog.json
   and refuses to overwrite an existing catalog. No legacy accounts, passwords
   or orders are imported. Regenerate the reviewed catalog from SQL with
   php tools/build-firebase-catalog.php. PHP does not auto-load .env files.
5. Connect GitHub Pbkh042/STKGears, branch main, root . to Vercel. Select the
   Container preset and Dockerfile.vercel; remove static output overrides.
   Push changes after the production variables are ready.
6. Verify home, products, email registration/login, session persistence and orders.
   /health reports configuration presence, not Firebase connectivity. Private
   source, SQL and tool URLs must return 404.

## Accounts and administration

Register a fresh account on the website using email and an 8+ character password.
Firebase stores passwords; the application never stores password hashes in RTDB.
Existing MySQL usernames are not Firebase accounts. New accounts get user role.

To assign the owner as administrator, first register/login on the website.
In the trusted Firebase console, find the matching email under stkgears/users/u_ID
and change only role to admin, then log out/in. Never allow clients to write
roles and never grant administrator access to every new account.

Shared sessions use hashed IDs, expiring leases and Secure/HttpOnly/SameSite=Lax
cookies. Firebase tokens are refreshed; password changes invalidate other app
sessions. Stock changes and order creation/cancellation use ETag transactions.
Cancellation restores stock once. Cart prices always come from the server.

## Verification and maintenance

php tools/test-firebase.php runs real Firebase tests in a random test namespace
with a temporary Auth account, then removes those fixtures. Run only with trusted
server credentials; it does not modify the production namespace. Keep key and
local environment files out of Git. Rotate disclosed keys, updating Vercel before
removing the old key. Monitor Spark plan usage in the Firebase console.

For local MySQL, set DATA_BACKEND=mysql (the default), use the existing database
settings and run php -S 127.0.0.1:8080 in this folder. The older
 tools/import-cloud.php workflow applies only to the MySQL provider.
