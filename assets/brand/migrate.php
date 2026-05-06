<?php
/**
 * One-time data migration script.
 *
 * HOW TO USE:
 * 1. Upload your JSON file to the same directory as this script and name it
 *    "migration-data.json"  (or update the path below).
 * 2. Place this file in:  /wp-content/plugins/studio-scheduler/migrate.php
 * 3. Open a browser and visit:
 *    https://yoursite.com/wp-content/plugins/studio-scheduler/migrate.php?key=CHANGE_ME
 * 4. After a successful migration, DELETE this file immediately.
 *
 * Security: The ?key= query-string guard prevents accidental public access.
 * Change MIGRATION_SECRET to something random before uploading.
 */

define( 'MIGRATION_SECRET', 'xK9mP2qR7vLds' );

// ── Guard ─────────────────────────────────────────────────────────────────────
if ( ( $_GET['key'] ?? '' ) !== MIGRATION_SECRET ) {
    http_response_code( 403 );
    die( 'Forbidden. Add ?key=YOUR_SECRET to the URL.' );
}

// ── Bootstrap WordPress ───────────────────────────────────────────────────────
$wp_load = __DIR__ . '/../../../wp-load.php'; // adjust if plugin is in a sub-folder
if ( ! file_exists( $wp_load ) ) {
    die( 'Could not find wp-load.php. Check the path at line ' . __LINE__ . '.' );
}
require_once $wp_load;

// ── Make sure the plugin tables exist ────────────────────────────────────────
require_once __DIR__ . '/includes/db.php';
ss_create_tables();
require_once __DIR__ . '/includes/api.php'; // for ss_db_upsert_job etc.

// ── Load JSON ─────────────────────────────────────────────────────────────────
$json_path = __DIR__ . '/migration-data.json';
if ( ! file_exists( $json_path ) ) {
    die( 'migration-data.json not found in ' . __DIR__ );
}

$raw     = file_get_contents( $json_path );
$payload = json_decode( $raw, true );

if ( ! $payload || ! is_array( $payload ) ) {
    die( 'Failed to parse JSON. Check the file for syntax errors.' );
}

// ── Migrate jobs ──────────────────────────────────────────────────────────────
$jobs = $payload['jobs'] ?? [];
echo '<h2>Migrating ' . count( $jobs ) . ' jobs…</h2><ul>';

$ok = 0; $fail = 0;
foreach ( $jobs as $job ) {
    $result = ss_db_upsert_job( (array) $job );
    if ( is_wp_error( $result ) ) {
        echo '<li style="color:red;">FAIL: ' . esc_html( $job['name'] ?? $job['id'] ) . ' — ' . esc_html( $result->get_error_message() ) . '</li>';
        $fail++;
    } else {
        $ok++;
    }
}
echo '</ul><p>' . $ok . ' jobs inserted/updated, ' . $fail . ' failed.</p>';

// ── Migrate users ─────────────────────────────────────────────────────────────
$users = $payload['users'] ?? [];
echo '<h2>Migrating ' . count( $users ) . ' users…</h2>';
ss_db_bulk_replace_users( $users );
echo '<p>Done.</p>';

// ── Migrate settings ──────────────────────────────────────────────────────────
$settings = [];
foreach ( [ 'anchorDate', 'currentHorizonDays', 'lastEditedBy', 'lastEditedAt' ] as $key ) {
    if ( array_key_exists( $key, $payload ) ) {
        $settings[ $key ] = (string) $payload[ $key ];
    }
}
if ( $settings ) {
    ss_db_update_settings( $settings );
    echo '<h2>Settings saved:</h2><pre>' . esc_html( print_r( $settings, true ) ) . '</pre>';
}

echo '<hr><p style="color:green; font-weight:bold;">✓ Migration complete. DELETE this file now!</p>';
