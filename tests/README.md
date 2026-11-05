# Regression checks

The PHP checks use **real WordPress 6.9** with an isolated SQLite database. Every Mailchimp HTTP request is mocked. Do not point the installer or tests at a production or shared development site: the tests change API settings, users, and caches.

Set `AN_MC_WP_ROOT` to a fresh disposable WordPress 6.9 download. Extract SQLite Database Integration 3.1.3 into its `wp-content/plugins` directory, then run:

```sh
php tests/install-wordpress.php
php tests/run.php
```

The installer writes a test configuration, creates a database, links this checkout as a plugin, and activates it. PHP requires PDO SQLite, SQLite3, mysqli, and mbstring. These commands run locally using the PHP executable on your PATH. To verify multiple PHP versions, run the installer and tests with each version in a separate disposable installation. Runtime warnings during test operations fail the suite. The initial bootstrap also checks WordPress incorrect-usage notices.

Frontend checks exercise the source and shipped minified JavaScript in a simulated browser DOM:

```sh
npm ci --ignore-scripts
npm test
npm run build
```

Node.js 22 or later is recommended. The tests cover submission without a mouse click, duplicate suppression, network/JSON failures, retries, custom messages, and editor attribute encoding. They do not replace a real browser or live Mailchimp end-to-end check.
