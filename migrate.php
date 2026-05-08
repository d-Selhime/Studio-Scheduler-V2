<?php
/**
 * Studio Scheduler — Data Migration Admin Page
 *
 * Registers a hidden WordPress admin page for one-time data import.
 * Access it at: WP Admin → Tools → Scheduler Migration
 * Or directly: https://yoursite.com/wp-admin/admin.php?page=ss-migrate
 *
 * HOW TO USE:
 * 1. Make sure this file is at:
 *    /wp-content/plugins/studio-scheduler/migrate.php
 * 2. Log into WordPress admin
 * 3. Visit: https://yoursite.com/wp-admin/tools.php?page=ss-migrate
 * 4. Paste your JSON data into the text area and click Import
 * 5. After successful migration, DELETE this file
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Register admin menu page ──────────────────────────────────────────────────
add_action( 'admin_menu', 'ss_migrate_menu' );
function ss_migrate_menu() {
    add_management_page(
        'Scheduler Migration',
        'Scheduler Migration',
        'manage_options',
        'ss-migrate',
        'ss_migrate_page'
    );
}

// ── Admin page ────────────────────────────────────────────────────────────────
function ss_migrate_page() {
    require_once SS_PLUGIN_DIR . 'includes/db.php';
    require_once SS_PLUGIN_DIR . 'includes/api.php';
    ss_create_tables();

    $message  = '';
    $error    = '';
    $stats    = [];

    if (
        isset( $_POST['ss_migrate_submit'] ) &&
        check_admin_referer( 'ss_migrate_action', 'ss_migrate_nonce' )
    ) {
        $json_raw = stripslashes( $_POST['ss_json_data'] ?? '' );

        if ( empty( $json_raw ) ) {
            $error = 'No JSON data provided.';
        } else {
            $payload = json_decode( $json_raw, true );

            if ( ! $payload || ! is_array( $payload ) ) {
                $error = 'Invalid JSON — check your data for syntax errors.';
            } else {
                $jobs        = $payload['jobs'] ?? [];
                $ok          = 0;
                $fail        = 0;
                $failures    = [];
                $import_mode = $_POST['import_mode'] ?? 'replace';

                if ( $import_mode === 'replace' ) {
                    global $wpdb;
                    $wpdb->query( "DELETE FROM {$wpdb->prefix}ss_jobs" );
                }

                foreach ( $jobs as $job ) {
                    $result = ss_db_upsert_job( (array) $job );
                    if ( is_wp_error( $result ) ) {
                        $fail++;
                        $failures[] = ( $job['name'] ?? $job['id'] ?? 'unknown' ) . ': ' . $result->get_error_message();
                    } else {
                        $ok++;
                    }
                }
                $stats['jobs_ok']       = $ok;
                $stats['jobs_fail']     = $fail;
                $stats['jobs_failures'] = $failures;

                $users = $payload['users'] ?? [];
                if ( ! empty( $users ) ) {
                    ss_db_bulk_replace_users( $users );
                    $stats['users'] = count( $users );
                }

                $settings = [];
                foreach ( [ 'anchorDate', 'currentHorizonDays', 'lastEditedBy', 'lastEditedAt' ] as $key ) {
                    if ( array_key_exists( $key, $payload ) ) {
                        $settings[ $key ] = (string) $payload[ $key ];
                    }
                }
                if ( $settings ) {
                    ss_db_update_settings( $settings );
                    $stats['settings'] = $settings;
                }

                $message = 'Migration complete!';
            }
        }
    }

    // Current DB counts
    global $wpdb;
    $job_count  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ss_jobs" );
    $user_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ss_users" );
    $settings   = ss_db_get_all_settings();
    ?>
    <div class="wrap">
        <h1>📅 Studio Scheduler — Data Migration</h1>
        <p style="color:#6b7280;">Import your existing JSON data into the WordPress database. <strong>Delete this file after a successful migration.</strong></p>

        <?php if ( $error ) : ?>
            <div class="notice notice-error"><p><strong>Error:</strong> <?php echo esc_html( $error ); ?></p></div>
        <?php endif; ?>

        <?php if ( $message ) : ?>
            <div class="notice notice-success">
                <p><strong>✓ <?php echo esc_html( $message ); ?></strong></p>
                <ul>
                    <li>✅ <strong><?php echo (int) $stats['jobs_ok']; ?></strong> jobs imported</li>
                    <?php if ( ! empty( $stats['jobs_fail'] ) ) : ?>
                        <li>❌ <strong><?php echo (int) $stats['jobs_fail']; ?></strong> jobs failed</li>
                    <?php endif; ?>
                    <?php if ( isset( $stats['users'] ) ) : ?>
                        <li>✅ <strong><?php echo (int) $stats['users']; ?></strong> users imported</li>
                    <?php endif; ?>
                    <?php if ( ! empty( $stats['settings'] ) ) : ?>
                        <li>✅ Settings saved</li>
                    <?php endif; ?>
                </ul>
                <p style="color:#dc2626; font-weight:600;">⚠️ Please delete migrate.php from your plugin folder now!</p>
            </div>
        <?php endif; ?>

        <!-- Current DB Status -->
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:16px; max-width:860px; margin-bottom:20px;">
            <h3 style="margin-top:0; color:#166534;">Current Database Status</h3>
            <ul style="margin:0;">
                <li>📋 <strong><?php echo $job_count; ?></strong> jobs in database</li>
                <li>👥 <strong><?php echo $user_count; ?></strong> users in database</li>
                <?php if ( ! empty( $settings['lastEditedBy'] ) ) : ?>
                    <li>✏️ Last edited by <strong><?php echo esc_html( $settings['lastEditedBy'] ); ?></strong> at <?php echo esc_html( $settings['lastEditedAt'] ?? '' ); ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Import Form -->
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:24px; max-width:860px;">
            <form method="post">
                <?php wp_nonce_field( 'ss_migrate_action', 'ss_migrate_nonce' ); ?>

                <div style="margin-bottom:16px;">
                    <label style="font-weight:600; display:block; margin-bottom:8px;">Import Mode:</label>
                    <label style="margin-right:20px;">
                        <input type="radio" name="import_mode" value="replace" checked>
                        <strong>Replace</strong> — clear existing jobs and import fresh
                    </label>
                    <label>
                        <input type="radio" name="import_mode" value="append">
                        <strong>Append</strong> — add to existing jobs
                    </label>
                </div>

                <div style="margin-bottom:16px;">
                    <label for="ss_json_data" style="font-weight:600; display:block; margin-bottom:8px;">
                        Paste your JSON data here:
                    </label>
                    <textarea
                        id="ss_json_data"
                        name="ss_json_data"
                        rows="20"
                        style="width:100%; font-family:monospace; font-size:0.82rem; border:1px solid #d1d5db; border-radius:6px; padding:10px; background:#f8fafc;"
                        placeholder='Paste the full contents of your JSON file here...'
                    ></textarea>
                </div>

                <div style="display:flex; gap:10px; align-items:center;">
                    <input type="submit" name="ss_migrate_submit" class="button button-primary button-large" value="Import Data" onclick="return confirmImport();">
                    <button type="button" class="button button-secondary" onclick="validateJson();">Validate JSON</button>
                    <span id="validateMsg" style="font-size:0.85rem;"></span>
                </div>
            </form>
        </div>
    </div>

    <script>
    function validateJson() {
        const raw = document.getElementById('ss_json_data').value.trim();
        const msg = document.getElementById('validateMsg');
        if (!raw) { msg.textContent = '⚠️ No data entered.'; msg.style.color = '#f97316'; return; }
        try {
            const d = JSON.parse(raw);
            const jobs  = Array.isArray(d.jobs)  ? d.jobs.length  : '—';
            const users = Array.isArray(d.users) ? d.users.length : '—';
            msg.textContent = `✅ Valid — ${jobs} jobs, ${users} users found.`;
            msg.style.color = '#16a34a';
        } catch(e) {
            msg.textContent = '❌ Invalid JSON: ' + e.message;
            msg.style.color = '#dc2626';
        }
    }
    function confirmImport() {
        const raw = document.getElementById('ss_json_data').value.trim();
        if (!raw) { alert('Please paste your JSON data first.'); return false; }
        try { JSON.parse(raw); } catch(e) { alert('Invalid JSON: ' + e.message); return false; }
        const mode = document.querySelector('input[name="import_mode"]:checked').value;
        const warn = mode === 'replace' ? '\n\n⚠️ This will DELETE all existing jobs first.' : '';
        return confirm('Import data into the database?' + warn);
    }
    </script>
    <?php
}
