<?php
/**
 * Database table creation for Studio Scheduler.
 *
 * Called on plugin activation via ss_activate().
 * Uses dbDelta() so it is safe to run on updates too — it only adds
 * missing columns / tables; it never drops existing data.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function ss_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    // ── 1. Jobs ───────────────────────────────────────────────────────────────
    // The original JSON uses floating-point IDs (e.g. 1776698203408.2024).
    // We store them as VARCHAR so nothing is lost in translation, and use
    // an auto-increment surrogate PK for internal operations.
    $jobs_table = $wpdb->prefix . 'ss_jobs';
    $sql_jobs   = "CREATE TABLE {$jobs_table} (
        id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        job_id               VARCHAR(64)     NOT NULL,
        name                 VARCHAR(255)    NOT NULL DEFAULT '',
        wf_id                VARCHAR(100)             DEFAULT '',
        lob                  VARCHAR(20)              DEFAULT '',
        job_type             VARCHAR(100)             DEFAULT '',
        loe                  VARCHAR(10)              DEFAULT '1',
        phase                VARCHAR(100)             DEFAULT '',
        project_task         VARCHAR(255)             DEFAULT '',
        scope_id             VARCHAR(50)              DEFAULT '',
        project_pct          FLOAT                    DEFAULT 0,
        cp_pct               FLOAT                    DEFAULT 0,
        producer_pct         FLOAT                    DEFAULT 0,
        status_notes         TEXT,
        risk_notes           TEXT,
        adpiler_link         VARCHAR(512)             DEFAULT '',
        bynder_link          VARCHAR(512)             DEFAULT '',
        bynder_archive_link  VARCHAR(512)             DEFAULT '',
        sharepoint_link      VARCHAR(512)             DEFAULT '',
        cp                   VARCHAR(100)             DEFAULT '',
        producer             VARCHAR(100)             DEFAULT '',
        marketer             VARCHAR(100)             DEFAULT '',
        media_partner        VARCHAR(100)             DEFAULT '',
        risk                 VARCHAR(50)              DEFAULT 'On Track',
        start_date           DATETIME,
        cd_date              DATETIME,
        live_date            DATETIME,
        go_live_date         DATETIME,
        total_assets         INT                      DEFAULT 0,
        cd_placements        INT,
        social_assets        INT                      DEFAULT 0,
        display_assets       INT                      DEFAULT 0,
        static_assets        INT                      DEFAULT 0,
        cd_only_assets       INT                      DEFAULT 0,
        priority             INT                      DEFAULT 2,
        dev_minutes_social   INT                      DEFAULT 30,
        dev_minutes_display  INT                      DEFAULT 30,
        dev_minutes_static   INT                      DEFAULT 15,
        review_minutes       INT                      DEFAULT 10,
        qa_minutes           INT                      DEFAULT 5,
        cd_minutes           INT                      DEFAULT 10,
        fxf_minutes          INT                      DEFAULT 30,
        cd_delivery_minutes  INT                      DEFAULT 10,
        vacation_track       VARCHAR(50)              DEFAULT 'all',
        vacation_person      VARCHAR(100)             DEFAULT '',
        assignee_social      VARCHAR(100)             DEFAULT '',
        assignee_display     VARCHAR(100)             DEFAULT '',
        assignee_static      VARCHAR(100)             DEFAULT '',
        assignee_review      VARCHAR(100)             DEFAULT '',
        assignee_qa          VARCHAR(100)             DEFAULT '',
        assignee_cd          VARCHAR(100)             DEFAULT '',
        assignee_ad          VARCHAR(100)             DEFAULT '',
        assignee_content     VARCHAR(100)             DEFAULT '',
        notes                TEXT,
        includes_hm          VARCHAR(10)              DEFAULT '',
        est_rounds           VARCHAR(10)              DEFAULT '1',
        has_fxf              VARCHAR(10)              DEFAULT '',
        checklist            LONGTEXT,
        created_at           DATETIME,
        created_by           VARCHAR(100)             DEFAULT '',
        PRIMARY KEY  (id),
        UNIQUE KEY   job_id (job_id)
    ) {$charset};";

    // ── 2. Users ──────────────────────────────────────────────────────────────
    $users_table = $wpdb->prefix . 'ss_users';
    $sql_users   = "CREATE TABLE {$users_table} (
        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name       VARCHAR(100)    NOT NULL DEFAULT '',
        role       VARCHAR(50)              DEFAULT 'view',
        lobs       TEXT,
        functions  TEXT,
        PRIMARY KEY (id),
        UNIQUE KEY  name (name)
    ) {$charset};";

    // ── 3. Settings ───────────────────────────────────────────────────────────
    // Key-value store for anchorDate, currentHorizonDays, lastEditedBy, lastEditedAt
    $settings_table = $wpdb->prefix . 'ss_settings';
    $sql_settings   = "CREATE TABLE {$settings_table} (
        setting_key   VARCHAR(100) NOT NULL,
        setting_value LONGTEXT,
        PRIMARY KEY (setting_key)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_jobs );
    dbDelta( $sql_users );
    dbDelta( $sql_settings );

    // Seed default settings if they don't exist yet
    $wpdb->query(
        "INSERT IGNORE INTO {$settings_table} (setting_key, setting_value)
         VALUES
           ('anchorDate',         ''),
           ('currentHorizonDays', '42'),
           ('lastEditedBy',       ''),
           ('lastEditedAt',       '')"
    );
}
