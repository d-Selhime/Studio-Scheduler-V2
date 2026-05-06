# Studio Production Scheduler — WordPress Plugin

A WordPress plugin that converts the standalone `Sheduler_Status.html` app
into a fully hosted, database-backed application running on WP Engine.

---

## Plugin Structure

```
studio-scheduler/
├── studio-scheduler.php      ← Main plugin file (registers everything)
├── migrate.php               ← ONE-TIME data import script (delete after use)
├── includes/
│   ├── db.php                ← Database table creation
│   ├── api.php               ← REST API endpoints + DB helper functions
│   └── template.php          ← HTML shell rendered by [studio_scheduler] shortcode
└── assets/
    ├── app.css               ← All CSS from the original <style> block  ← YOU CREATE THIS
    ├── app.js                ← All JS from the original <script> blocks ← YOU CREATE THIS
    ├── api-adapter.js        ← WP REST API bridge (replaces JSON file I/O)
    └── brand/
        ├── 1876_Productions_WM_RGB_Pos.png   ← Copy from original /brand/ folder
        └── 1876-logo.svg                     ← Copy from original /brand/ folder
```

---

## Step-by-Step Deployment

### Step 1 — Prepare `assets/app.css`

1. Open `Sheduler_Status.html`
2. Copy **everything between** `<style>` and `</style>` (lines 9–1061)
3. Paste it into a new file: `assets/app.css`
4. Save.

### Step 2 — Prepare `assets/app.js`

1. In `Sheduler_Status.html`, locate all `<script>` blocks (lines 1824–6333)
2. Copy the **contents** of every script block (not the `<script>` tags themselves)
3. Paste them — in order — into a new file: `assets/app.js`
4. At the **very bottom** of `assets/app.js`, add this line:

```js
// Initialize WordPress API adapter (replaces JSON file I/O)
document.addEventListener('DOMContentLoaded', function() {
  SS_WP.init();
});
```

5. Find the line in app.js that reads:
```js
const EMBEDDED_STATE = {"jobs":[],"anchorDate":null,"currentHorizonDays":42};
```
   Change it to:
```js
const EMBEDDED_STATE = {"jobs":[],"anchorDate":null,"currentHorizonDays":42};
// NOTE: Real data is loaded from the WP REST API by SS_WP.init() below.
```

6. **Remove or comment out** `initFsaConnection()` if it is called at the bottom of
   the script — the adapter handles initial load instead.

### Step 3 — Update app.js to load api-adapter.js

In `studio-scheduler.php`, the `ss_enqueue_assets()` function already enqueues
`api-adapter.js` before `app.js`. No changes needed in PHP — just make sure
`assets/api-adapter.js` is present.

### Step 4 — Copy brand assets

Copy these files from your original project:
- `brand/1876_Productions_WM_RGB_Pos.png` → `assets/brand/1876_Productions_WM_RGB_Pos.png`
- `brand/1876-logo.svg`                   → `assets/brand/1876-logo.svg`

### Step 5 — Upload to WP Engine

**Option A — SFTP**
1. Connect to your WP Engine site via SFTP
2. Upload the entire `studio-scheduler/` folder to:
   `/wp-content/plugins/studio-scheduler/`

**Option B — WP Admin Upload**
1. Zip the `studio-scheduler/` folder → `studio-scheduler.zip`
2. WP Admin → Plugins → Add New → Upload Plugin
3. Choose `studio-scheduler.zip` → Install Now

### Step 6 — Activate the plugin

WP Admin → Plugins → find "Studio Production Scheduler" → Activate

Activation automatically creates the three database tables:
- `wp_ss_jobs`
- `wp_ss_users`
- `wp_ss_settings`

### Step 7 — Create a WordPress page

1. WP Admin → Pages → Add New
2. Give it a title, e.g. "Studio Scheduler"
3. In the page body, add the shortcode:
   ```
   [studio_scheduler]
   ```
4. Publish the page.

### Step 8 — Migrate your existing data (one-time)

1. Copy your current JSON file to the plugin folder and rename it:
   `/wp-content/plugins/studio-scheduler/migration-data.json`

2. **Edit `migrate.php`** — change the secret key on line 20:
   ```php
   define( 'MIGRATION_SECRET', 'CHANGE_ME_TO_SOMETHING_RANDOM' );
   ```
   to something like:
   ```php
   define( 'MIGRATION_SECRET', 'xK9mP2qR7vL' );
   ```

3. Visit in your browser:
   ```
   https://yoursite.com/wp-content/plugins/studio-scheduler/migrate.php?key=xK9mP2qR7vL
   ```

4. You should see a success message with job + user counts.

5. **DELETE `migrate.php` and `migration-data.json` immediately after.**

### Step 9 — Test

Visit the page you created in Step 7. You should see:
- The scheduler loads with your existing jobs
- The navbar shows "● Connected to Database"
- Editing and saving jobs works

---

## REST API Reference

All endpoints are under: `https://yoursite.com/wp-json/studio-scheduler/v1/`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/data` | Full payload (jobs + users + settings) |
| PUT | `/data` | Bulk replace full payload |
| GET | `/jobs` | All jobs |
| POST | `/jobs` | Create a job |
| GET | `/jobs/{id}` | Single job |
| PATCH | `/jobs/{id}` | Update a job |
| DELETE | `/jobs/{id}` | Delete a job |
| GET | `/users` | All users |
| PUT | `/users` | Bulk replace users |
| GET | `/settings` | All settings |
| PUT | `/settings` | Update settings |

**Authentication:** All write requests require an `X-WP-Nonce` header.
The nonce is injected automatically by WordPress via `wp_localize_script()`.

---

## Troubleshooting

**"Cannot read properties of null" JS errors**
→ An element the JS expects is not in the DOM yet. Check that `app.js` contains
  all the original HTML that was inline in `<body>` (modals, form, etc.).

**404 on REST endpoints**
→ Go to WP Admin → Settings → Permalinks → Save Changes (flushes rewrite rules).

**Data not saving**
→ Open browser DevTools → Network tab → look for failed PUT /data requests.
  Check the response body for the error message.

**Styles look wrong**
→ WordPress may be injecting its own CSS that conflicts. Add `#ss-root` as a
  prefix to your CSS selectors in `app.css` to increase specificity, e.g.:
  `#ss-root .navbar { ... }` instead of `.navbar { ... }`.

**Font (ATT Aleck Sans) not loading**
→ This is a proprietary AT&T font. Either host the font files in `assets/fonts/`
  and add `@font-face` rules to `app.css`, or the CSS already falls back to
  `system-ui, -apple-system, sans-serif`.
