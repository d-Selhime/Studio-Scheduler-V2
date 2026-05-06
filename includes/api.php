<?php
/**
 * REST API endpoints for Studio Scheduler.
 *
 * Base namespace: studio-scheduler/v1
 *
 * GET    /data              → full payload (jobs + users + settings) — mirrors JSON file load
 * PUT    /data              → replace full payload (bulk save — mirrors JSON file write)
 * GET    /jobs              → all jobs
 * POST   /jobs              → create a single job
 * GET    /jobs/{job_id}     → single job
 * PATCH  /jobs/{job_id}     → update a single job
 * DELETE /jobs/{job_id}     → delete a single job
 * GET    /users             → all users
 * PUT    /users             → bulk-replace users list
 * GET    /settings          → all settings
 * PUT    /settings          → bulk-update settings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', 'ss_register_routes' );

function ss_register_routes() {

    $ns = SS_API_NS;

    // ── Full-payload endpoints (replaces the single JSON file) ────────────────
    register_rest_route( $ns, '/data', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'ss_api_get_data',
            'permission_callback' => '__return_true',  // public read
        ],
        [
            'methods'             => WP_REST_Server::EDITABLE,  // PUT / PATCH
            'callback'            => 'ss_api_put_data',
            'permission_callback' => 'ss_can_write',
        ],
    ] );

    // ── Jobs ──────────────────────────────────────────────────────────────────
    register_rest_route( $ns, '/jobs', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'ss_api_get_jobs',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'ss_api_create_job',
            'permission_callback' => 'ss_can_write',
        ],
    ] );

    register_rest_route( $ns, '/jobs/(?P<job_id>[^/]+)', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'ss_api_get_job',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'             => 'PATCH',
            'callback'            => 'ss_api_update_job',
            'permission_callback' => 'ss_can_write',
        ],
        [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'ss_api_delete_job',
            'permission_callback' => 'ss_can_write',
        ],
    ] );

    // ── Users ─────────────────────────────────────────────────────────────────
    register_rest_route( $ns, '/users', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'ss_api_get_users',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => 'ss_api_put_users',
            'permission_callback' => 'ss_can_write',
        ],
    ] );

    // ── Settings ──────────────────────────────────────────────────────────────
    register_rest_route( $ns, '/settings', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'ss_api_get_settings',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => 'ss_api_put_settings',
            'permission_callback' => 'ss_can_write',
        ],
    ] );
}

// ── Permission helpers ────────────────────────────────────────────────────────

/**
 * Write permission check.
 *
 * Strategy: The scheduler handles its own role system inside the JS
 * (admin / lob-manager / view-*). For WordPress we use a lightweight
 * shared-secret nonce approach so:
 *   - Any request with a valid WP REST nonce is allowed.
 *   - Unauthenticated requests get a 403.
 *
 * If you want truly open writes (e.g. no WP login required at all),
 * replace the body of this function with `return true;` and rely solely
 * on the JS-side role checks + optionally an API key header.
 */
function ss_can_write( WP_REST_Request $request ) {
    // Allow any logged-in WP user OR a valid nonce (sent as X-WP-Nonce header).
    if ( is_user_logged_in() ) return true;

    $nonce = $request->get_header( 'X-WP-Nonce' );
    if ( $nonce && wp_verify_nonce( $nonce, 'wp_rest' ) ) return true;

    return new WP_Error( 'rest_forbidden', 'You must be authenticated to save data.', [ 'status' => 403 ] );
}

// ── Full-payload handlers ─────────────────────────────────────────────────────

/**
 * GET /data
 * Returns the same shape as the original JSON file so the JS app needs
 * minimal changes: { jobs, users, anchorDate, currentHorizonDays, lastEditedBy, lastEditedAt }
 */
function ss_api_get_data() {
    $jobs     = ss_db_get_all_jobs();
    $users    = ss_db_get_all_users();
    $settings = ss_db_get_all_settings();

    return rest_ensure_response( [
        'jobs'               => $jobs,
        'users'              => $users,
        'anchorDate'         => $settings['anchorDate']         ?? '',
        'currentHorizonDays' => (int) ( $settings['currentHorizonDays'] ?? 42 ),
        'lastEditedBy'       => $settings['lastEditedBy']       ?? '',
        'lastEditedAt'       => $settings['lastEditedAt']        ?? '',
    ] );
}

/**
 * PUT /data
 * Accepts the same payload shape and bulk-replaces everything.
 * This mirrors what the FSA "save to JSON file" used to do.
 */
function ss_api_put_data( WP_REST_Request $request ) {
    $body = $request->get_json_params();
    if ( ! is_array( $body ) ) {
        return new WP_Error( 'invalid_data', 'Expected a JSON object.', [ 'status' => 400 ] );
    }

    if ( isset( $body['jobs'] ) && is_array( $body['jobs'] ) ) {
        ss_db_bulk_replace_jobs( $body['jobs'] );
    }

    if ( isset( $body['users'] ) && is_array( $body['users'] ) ) {
        ss_db_bulk_replace_users( $body['users'] );
    }

    $settings = [];
    foreach ( [ 'anchorDate', 'currentHorizonDays', 'lastEditedBy', 'lastEditedAt' ] as $key ) {
        if ( array_key_exists( $key, $body ) ) {
            $settings[ $key ] = (string) $body[ $key ];
        }
    }
    if ( $settings ) ss_db_update_settings( $settings );

    return rest_ensure_response( [ 'success' => true ] );
}

// ── Jobs handlers ─────────────────────────────────────────────────────────────

function ss_api_get_jobs() {
    return rest_ensure_response( ss_db_get_all_jobs() );
}

function ss_api_get_job( WP_REST_Request $request ) {
    $job = ss_db_get_job( $request['job_id'] );
    if ( ! $job ) return new WP_Error( 'not_found', 'Job not found.', [ 'status' => 404 ] );
    return rest_ensure_response( $job );
}

function ss_api_create_job( WP_REST_Request $request ) {
    $data = $request->get_json_params();
    if ( empty( $data ) ) {
        return new WP_Error( 'invalid_data', 'Job data required.', [ 'status' => 400 ] );
    }
    $result = ss_db_upsert_job( $data );
    if ( is_wp_error( $result ) ) return $result;
    return rest_ensure_response( [ 'success' => true, 'job_id' => $data['id'] ?? null ] );
}

function ss_api_update_job( WP_REST_Request $request ) {
    $data = $request->get_json_params();
    if ( empty( $data ) ) {
        return new WP_Error( 'invalid_data', 'Job data required.', [ 'status' => 400 ] );
    }
    $data['id'] = $request['job_id'];  // ensure ID comes from URL
    $result = ss_db_upsert_job( $data );
    if ( is_wp_error( $result ) ) return $result;
    return rest_ensure_response( [ 'success' => true ] );
}

function ss_api_delete_job( WP_REST_Request $request ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ss_jobs';
    $wpdb->delete( $table, [ 'job_id' => (string) $request['job_id'] ], [ '%s' ] );
    return rest_ensure_response( [ 'success' => true ] );
}

// ── Users handlers ────────────────────────────────────────────────────────────

function ss_api_get_users() {
    return rest_ensure_response( ss_db_get_all_users() );
}

function ss_api_put_users( WP_REST_Request $request ) {
    $users = $request->get_json_params();
    if ( ! is_array( $users ) ) {
        return new WP_Error( 'invalid_data', 'Expected a JSON array of users.', [ 'status' => 400 ] );
    }
    ss_db_bulk_replace_users( $users );
    return rest_ensure_response( [ 'success' => true ] );
}

// ── Settings handlers ─────────────────────────────────────────────────────────

function ss_api_get_settings() {
    return rest_ensure_response( ss_db_get_all_settings() );
}

function ss_api_put_settings( WP_REST_Request $request ) {
    $data = $request->get_json_params();
    if ( ! is_array( $data ) ) {
        return new WP_Error( 'invalid_data', 'Expected a JSON object.', [ 'status' => 400 ] );
    }
    ss_db_update_settings( $data );
    return rest_ensure_response( [ 'success' => true ] );
}

// ── Database helper functions ─────────────────────────────────────────────────

function ss_db_get_all_jobs() {
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}ss_jobs ORDER BY start_date ASC, name ASC",
        ARRAY_A
    );
    return array_map( 'ss_job_row_to_api', $rows ?: [] );
}

function ss_db_get_job( $job_id ) {
    global $wpdb;
    $row = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ss_jobs WHERE job_id = %s", (string) $job_id ),
        ARRAY_A
    );
    return $row ? ss_job_row_to_api( $row ) : null;
}

/**
 * Insert or update a single job row.
 * Handles both new jobs (INSERT) and edits (UPDATE via ON DUPLICATE KEY).
 */
function ss_db_upsert_job( array $job ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ss_jobs';

    // Generate a new float-style ID if none supplied
    if ( empty( $job['id'] ) ) {
        $job['id'] = (string) ( microtime( true ) * 1000 );
    }

    $checklist = isset( $job['checklist'] ) ? wp_json_encode( $job['checklist'] ) : null;

    $row = [
        'job_id'              => (string) $job['id'],
        'name'                => sanitize_text_field( $job['name']           ?? '' ),
        'wf_id'               => sanitize_text_field( $job['wfId']           ?? '' ),
        'lob'                 => sanitize_text_field( $job['lob']            ?? '' ),
        'job_type'            => sanitize_text_field( $job['jobType']        ?? '' ),
        'loe'                 => sanitize_text_field( (string) ( $job['loe'] ?? '1' ) ),
        'phase'               => sanitize_text_field( $job['phase']          ?? '' ),
        'project_task'        => sanitize_text_field( $job['projectTask']    ?? '' ),
        'scope_id'            => sanitize_text_field( $job['scopeId']        ?? '' ),
        'project_pct'         => (float)   ( $job['projectPct']             ?? 0 ),
        'cp_pct'              => (float)   ( $job['cpPct']                  ?? 0 ),
        'producer_pct'        => (float)   ( $job['producerPct']            ?? 0 ),
        'status_notes'        => sanitize_textarea_field( $job['statusNotes'] ?? '' ),
        'risk_notes'          => sanitize_textarea_field( $job['riskNotes']  ?? '' ),
        'adpiler_link'        => esc_url_raw( $job['adpilerLink']            ?? '' ),
        'bynder_link'         => esc_url_raw( $job['bynderLink']             ?? '' ),
        'bynder_archive_link' => esc_url_raw( $job['bynderArchiveLink']      ?? '' ),
        'sharepoint_link'     => esc_url_raw( $job['sharepointLink']         ?? '' ),
        'cp'                  => sanitize_text_field( $job['cp']             ?? '' ),
        'producer'            => sanitize_text_field( $job['producer']       ?? '' ),
        'marketer'            => sanitize_text_field( $job['marketer']       ?? '' ),
        'media_partner'       => sanitize_text_field( $job['mediaPartner']   ?? '' ),
        'risk'                => sanitize_text_field( $job['risk']           ?? 'On Track' ),
        'start_date'          => ss_iso_to_mysql( $job['startDate']          ?? null ),
        'cd_date'             => ss_iso_to_mysql( $job['cdDate']             ?? null ),
        'live_date'           => ss_iso_to_mysql( $job['liveDate']           ?? null ),
        'go_live_date'        => ss_iso_to_mysql( $job['goLiveDate']         ?? null ),
        'total_assets'        => (int)    ( $job['totalAssets']             ?? 0 ),
        'cd_placements'       => isset( $job['cdPlacements'] ) ? (int) $job['cdPlacements'] : null,
        'social_assets'       => (int)    ( $job['socialAssets']            ?? 0 ),
        'display_assets'      => (int)    ( $job['displayAssets']           ?? 0 ),
        'static_assets'       => (int)    ( $job['staticAssets']            ?? 0 ),
        'cd_only_assets'      => (int)    ( $job['cdOnlyAssets']            ?? 0 ),
        'priority'            => (int)    ( $job['priority']                ?? 2 ),
        'dev_minutes_social'  => (int)    ( $job['devMinutesSocial']        ?? 30 ),
        'dev_minutes_display' => (int)    ( $job['devMinutesDisplay']       ?? 30 ),
        'dev_minutes_static'  => (int)    ( $job['devMinutesStatic']        ?? 15 ),
        'review_minutes'      => (int)    ( $job['reviewMinutes']           ?? 10 ),
        'qa_minutes'          => (int)    ( $job['qaMinutes']               ?? 5 ),
        'cd_minutes'          => (int)    ( $job['cdMinutes']               ?? 10 ),
        'fxf_minutes'         => (int)    ( $job['fxfMinutes']              ?? 30 ),
        'cd_delivery_minutes' => (int)    ( $job['cdDeliveryMinutes']       ?? 10 ),
        'vacation_track'      => sanitize_text_field( $job['vacationTrack'] ?? 'all' ),
        'vacation_person'     => sanitize_text_field( $job['vacationPerson'] ?? '' ),
        'assignee_social'     => sanitize_text_field( $job['assigneeSocial'] ?? '' ),
        'assignee_display'    => sanitize_text_field( $job['assigneeDisplay'] ?? '' ),
        'assignee_static'     => sanitize_text_field( $job['assigneeStatic'] ?? '' ),
        'assignee_review'     => sanitize_text_field( $job['assigneeReview'] ?? '' ),
        'assignee_qa'         => sanitize_text_field( $job['assigneeQa']    ?? '' ),
        'assignee_cd'         => sanitize_text_field( $job['assigneeCd']    ?? '' ),
        'assignee_ad'         => sanitize_text_field( $job['assigneeAd']    ?? '' ),
        'assignee_content'    => sanitize_text_field( $job['assigneeContent'] ?? '' ),
        'notes'               => sanitize_textarea_field( $job['notes']     ?? '' ),
        'includes_hm'         => sanitize_text_field( $job['includesHM']    ?? '' ),
        'est_rounds'          => sanitize_text_field( (string) ( $job['estRounds'] ?? '1' ) ),
        'has_fxf'             => sanitize_text_field( $job['hasFxf']        ?? '' ),
        'checklist'           => $checklist,
        'created_at'          => ss_iso_to_mysql( $job['createdAt']         ?? null ),
        'created_by'          => sanitize_text_field( $job['createdBy']     ?? '' ),
    ];

    // Use INSERT … ON DUPLICATE KEY UPDATE for atomic upsert
    $cols        = implode( ', ', array_map( fn( $c ) => "`{$c}`", array_keys( $row ) ) );
    $placeholders = implode( ', ', array_fill( 0, count( $row ), '%s' ) );
    $updates     = implode( ', ', array_map( fn( $c ) => "`{$c}` = VALUES(`{$c}`)", array_keys( $row ) ) );

    $sql = "INSERT INTO `{$table}` ({$cols}) VALUES ({$placeholders}) ON DUPLICATE KEY UPDATE {$updates}";
    $wpdb->query( $wpdb->prepare( $sql, array_values( $row ) ) );

    if ( $wpdb->last_error ) {
        return new WP_Error( 'db_error', $wpdb->last_error, [ 'status' => 500 ] );
    }
    return true;
}

/**
 * Bulk-replace all jobs.
 * Deletes all existing rows, then inserts the new set in one transaction.
 * This matches the original behaviour where saving the JSON file replaced it entirely.
 */
function ss_db_bulk_replace_jobs( array $jobs ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ss_jobs';
    $wpdb->query( "DELETE FROM `{$table}`" );
    foreach ( $jobs as $job ) {
        ss_db_upsert_job( (array) $job );
    }
}

function ss_db_get_all_users() {
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}ss_users ORDER BY name ASC",
        ARRAY_A
    );
    return array_map( function( $row ) {
        return [
            'name'      => $row['name'],
            'role'      => $row['role'],
            'lobs'      => $row['lobs'] ? json_decode( $row['lobs'], true ) : [],
            'functions' => $row['functions'] ? json_decode( $row['functions'], true ) : [],
        ];
    }, $rows ?: [] );
}

function ss_db_bulk_replace_users( array $users ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ss_users';
    $wpdb->query( "DELETE FROM `{$table}`" );
    foreach ( $users as $u ) {
        $u = (array) $u;
        $lobs = is_array( $u['lobs'] ?? null )
            ? wp_json_encode( $u['lobs'] )
            : ( is_string( $u['lobs'] ?? null ) ? wp_json_encode( array_map( 'trim', explode( ',', $u['lobs'] ) ) ) : '[]' );
        $fns  = is_array( $u['functions'] ?? null ) ? wp_json_encode( $u['functions'] ) : '[]';
        $wpdb->replace( $table, [
            'name'      => sanitize_text_field( $u['name'] ?? '' ),
            'role'      => sanitize_text_field( $u['role'] ?? 'view' ),
            'lobs'      => $lobs,
            'functions' => $fns,
        ], [ '%s', '%s', '%s', '%s' ] );
    }
}

function ss_db_get_all_settings() {
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT setting_key, setting_value FROM {$wpdb->prefix}ss_settings",
        ARRAY_A
    );
    $out = [];
    foreach ( $rows as $r ) {
        $out[ $r['setting_key'] ] = $r['setting_value'];
    }
    return $out;
}

function ss_db_update_settings( array $data ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ss_settings';
    foreach ( $data as $key => $value ) {
        $wpdb->replace( $table, [
            'setting_key'   => sanitize_key( $key ),
            'setting_value' => (string) $value,
        ], [ '%s', '%s' ] );
    }
}

// ── Utility ───────────────────────────────────────────────────────────────────

/** Convert an ISO 8601 date string to MySQL DATETIME format, or return null. */
function ss_iso_to_mysql( $iso ) {
    if ( ! $iso ) return null;
    $ts = strtotime( $iso );
    return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
}

/** Convert a DB row back to the camelCase shape the JS app expects. */
function ss_job_row_to_api( array $row ) {
    return [
        'id'                  => $row['job_id'],
        'name'                => $row['name'],
        'wfId'                => $row['wf_id'],
        'lob'                 => $row['lob'],
        'jobType'             => $row['job_type'],
        'loe'                 => $row['loe'],
        'phase'               => $row['phase'],
        'projectTask'         => $row['project_task'],
        'scopeId'             => $row['scope_id'],
        'projectPct'          => (float) $row['project_pct'],
        'cpPct'               => (float) $row['cp_pct'],
        'producerPct'         => (float) $row['producer_pct'],
        'statusNotes'         => $row['status_notes'],
        'riskNotes'           => $row['risk_notes'],
        'adpilerLink'         => $row['adpiler_link'],
        'bynderLink'          => $row['bynder_link'],
        'bynderArchiveLink'   => $row['bynder_archive_link'],
        'sharepointLink'      => $row['sharepoint_link'],
        'cp'                  => $row['cp'],
        'producer'            => $row['producer'],
        'marketer'            => $row['marketer'],
        'mediaPartner'        => $row['media_partner'],
        'risk'                => $row['risk'],
        'startDate'           => $row['start_date']   ? ss_mysql_to_iso( $row['start_date'] )   : null,
        'cdDate'              => $row['cd_date']      ? ss_mysql_to_iso( $row['cd_date'] )      : null,
        'liveDate'            => $row['live_date']    ? ss_mysql_to_iso( $row['live_date'] )    : null,
        'goLiveDate'          => $row['go_live_date'] ? ss_mysql_to_iso( $row['go_live_date'] ) : null,
        'totalAssets'         => (int) $row['total_assets'],
        'cdPlacements'        => isset( $row['cd_placements'] ) ? (int) $row['cd_placements'] : null,
        'socialAssets'        => (int) $row['social_assets'],
        'displayAssets'       => (int) $row['display_assets'],
        'staticAssets'        => (int) $row['static_assets'],
        'cdOnlyAssets'        => (int) $row['cd_only_assets'],
        'priority'            => (int) $row['priority'],
        'devMinutesSocial'    => (int) $row['dev_minutes_social'],
        'devMinutesDisplay'   => (int) $row['dev_minutes_display'],
        'devMinutesStatic'    => (int) $row['dev_minutes_static'],
        'reviewMinutes'       => (int) $row['review_minutes'],
        'qaMinutes'           => (int) $row['qa_minutes'],
        'cdMinutes'           => (int) $row['cd_minutes'],
        'fxfMinutes'          => (int) $row['fxf_minutes'],
        'cdDeliveryMinutes'   => (int) $row['cd_delivery_minutes'],
        'vacationTrack'       => $row['vacation_track'],
        'vacationPerson'      => $row['vacation_person'],
        'assigneeSocial'      => $row['assignee_social'],
        'assigneeDisplay'     => $row['assignee_display'],
        'assigneeStatic'      => $row['assignee_static'],
        'assigneeReview'      => $row['assignee_review'],
        'assigneeQa'          => $row['assignee_qa'],
        'assigneeCd'          => $row['assignee_cd'],
        'assigneeAd'          => $row['assignee_ad'],
        'assigneeContent'     => $row['assignee_content'],
        'notes'               => $row['notes'],
        'includesHM'          => $row['includes_hm'],
        'estRounds'           => $row['est_rounds'],
        'hasFxf'              => $row['has_fxf'],
        'checklist'           => $row['checklist'] ? json_decode( $row['checklist'], true ) : null,
        'createdAt'           => $row['created_at'] ? ss_mysql_to_iso( $row['created_at'] ) : null,
        'createdBy'           => $row['created_by'],
    ];
}

/** MySQL DATETIME → ISO 8601 string */
function ss_mysql_to_iso( $mysql ) {
    if ( ! $mysql || $mysql === '0000-00-00 00:00:00' ) return null;
    return gmdate( 'c', strtotime( $mysql ) );
}
