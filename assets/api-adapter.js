/**
 * Studio Scheduler — WordPress REST API Adapter
 * ─────────────────────────────────────────────
 * Drop this file at:  /wp-content/plugins/studio-scheduler/assets/api-adapter.js
 *
 * Then add ONE line near the top of your existing app.js (after the var declarations):
 *
 *     // Replace the old FSA / JSON file calls:
 *     SS_WP.init();
 *
 * This module monkey-patches the four data-layer functions the app already calls:
 *   • loadStateFromStorage()   → GET  /studio-scheduler/v1/data
 *   • saveStateToStorage()     → PUT  /studio-scheduler/v1/data   (full payload)
 *   • saveToConnectedFile()    → PUT  /studio-scheduler/v1/data   (full payload)
 *   • connectToDataFile()      → no-op (no file picker needed)
 *
 * SS_API is injected by WordPress via wp_localize_script() in the plugin:
 *   SS_API.root   = 'https://yoursite.com/wp-json/studio-scheduler/v1/'
 *   SS_API.nonce  = '<wp_rest nonce>'
 */

/* global SS_API, jobs, users, anchorDate, currentHorizonDays,
          currentUserName, applyLoadedState, renderSchedule,
          saveStateToStorage, buildStatePayload,
          updateFsaStatusUI, ensureUserName, resolveUserRole, applyRoleUI */

const SS_WP = (() => {

  // ── Internal helpers ────────────────────────────────────────────────────────

  function apiUrl(path) {
    return SS_API.root.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
  }

  function authHeaders() {
    return {
      'Content-Type': 'application/json',
      'X-WP-Nonce':   SS_API.nonce,
    };
  }

  // Central fetch wrapper with basic error handling
  async function apiFetch(path, options = {}) {
    const url = apiUrl(path);
    try {
      const res = await fetch(url, {
        ...options,
        headers: { ...authHeaders(), ...(options.headers || {}) },
      });
      if (!res.ok) {
        const err = await res.json().catch(() => ({ message: res.statusText }));
        throw new Error(`API ${options.method || 'GET'} ${path} failed: ${err.message || res.status}`);
      }
      return await res.json();
    } catch (e) {
      console.error('[SS_WP] fetch error:', e);
      throw e;
    }
  }

  // ── Status banner (replaces FSA status UI) ──────────────────────────────────

  function setStatus(state, msg) {
    const colors = { connected: '#22c55e', saving: '#f97316', error: '#ef4444' };
    const icons  = { connected: '●', saving: '↑', error: '○' };
      // Update all fsaStatus spans on the page
    document.querySelectorAll('#fsaStatus').forEach(el => {
        el.textContent = (icons[state] || '○') + ' ' + (msg || '');
        el.style.color = colors[state] || '#9ca3af';
    });
   
        // Update all connectFileBtn buttons on the page
    document.querySelectorAll('#connectFileBtn').forEach(btn => {
        if (state === 'connected' || state === 'saving') {
            btn.textContent = 'Connected to Database';
            btn.style.background  = '#22c55e';
            btn.style.color       = '#fff';
            btn.style.borderColor = '#22c55e';
        } else if (state === 'error') {
            btn.textContent = 'Database Error';
            btn.style.background  = '#ef4444';
            btn.style.color       = '#fff';
            btn.style.borderColor = '#ef4444';
        }
    });
  }

  // ── Load ────────────────────────────────────────────────────────────────────

  /**
   * Replaces the original loadStateFromStorage() / connectToDataFile() flow.
   * Fetches the full payload from the WP REST API and calls applyLoadedState().
   */
  async function loadFromApi() {
    setStatus('saving', 'Loading…');
    try {
      const payload = await apiFetch('/data');
      if (payload && Array.isArray(payload.jobs)) {
        applyLoadedState(payload, { fromEmbedded: false });
        renderSchedule();
        setStatus('connected', 'Loaded from database');
      }
    } catch (e) {
      setStatus('error', 'Could not load data');
      console.error('[SS_WP] loadFromApi failed', e);
    }
  }

  // ── Save ────────────────────────────────────────────────────────────────────

  /**
   * Replaces saveStateToStorage() AND saveToConnectedFile().
   * Sends the full state payload to PUT /data.
   */
  async function saveToApi() {
    setStatus('saving', 'Saving…');
    try {
      const payload = buildStatePayload();
      await apiFetch('/data', {
        method: 'PUT',
        body:   JSON.stringify(payload),
      });
      const t = new Date().toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
      setStatus('connected', 'Saved at ' + t);
    } catch (e) {
      setStatus('error', 'Save failed — check console');
      console.error('[SS_WP] saveToApi failed', e);
    }
  }

  // ── Single-job helpers (used by Status Report inline saves) ─────────────────

  async function patchJob(jobData) {
    const id = String(jobData.id);
    return apiFetch('/jobs/' + encodeURIComponent(id), {
      method: 'PATCH',
      body:   JSON.stringify(jobData),
    });
  }

  async function deleteJob(jobId) {
    return apiFetch('/jobs/' + encodeURIComponent(String(jobId)), {
      method: 'DELETE',
    });
  }

  // ── Poll for external updates ────────────────────────────────────────────────

  let _pollId = null;

  function startPoll(intervalMs) {
    stopPoll();
    _pollId = setInterval(async () => {
      try {
        const payload = await apiFetch('/data');
        if (!payload || !payload.lastEditedAt) return;
        // Show banner if someone else saved since our last load
        const fileTime = new Date(payload.lastEditedAt);
        if (
          window._ssLastLoadedAt &&
          fileTime > window._ssLastLoadedAt &&
          payload.lastEditedBy !== currentUserName
        ) {
          showUpdateBanner(payload.lastEditedBy, fileTime);
        }
      } catch (_) { /* silent */ }
    }, intervalMs || 900000); // default 15 min
  }

  function stopPoll() {
    if (_pollId) { clearInterval(_pollId); _pollId = null; }
  }

  // ── Bootstrap ────────────────────────────────────────────────────────────────

  /**
   * Call SS_WP.init() once near the top of your existing app JS
   * (after all variable declarations but before event listeners).
   *
   * It:
   *  1. Hides the "Connect to JSON" button (no file picker needed)
   *  2. Patches saveStateToStorage and saveToConnectedFile to use the API
   *  3. Loads initial data from the API
   *  4. Starts the poll
   */
  function init() {

    // ── 1. Hide/replace FSA UI elements that no longer apply ────────────────
    const connectBtn = document.getElementById('connectFileBtn');
    if (connectBtn) {
      // Re-purpose it as a "reload from DB" button
      connectBtn.textContent = 'Reload from Database';
      connectBtn.style.background  = '#22c55e';
      connectBtn.style.color       = '#fff';
      connectBtn.style.borderColor = '#22c55e';
      connectBtn.onclick = function() { loadFromApi(); };
    }

    // * correct role assigned to user
async function loadFromApi() {
    setStatus('saving', 'Loading…');
    try {
        const payload = await apiFetch('/data');
        if (payload && Array.isArray(payload.jobs)) {
            applyLoadedState(payload, { fromEmbedded: false });
            // Re-resolve role now that users array is populated
            if (window.resolveUserRole) {
                resolveUserRole(currentUserName);
            }
            if (window.applyRoleUI) applyRoleUI();
            renderSchedule();
            setStatus('connected', 'Loaded from database');
        }
    } catch (e) {
        setStatus('error', 'Could not load data');
        console.error('[SS_WP] loadFromApi failed', e);
    }
}
    // ── 2. Patch global save functions ──────────────────────────────────────
    //  The original app calls saveStateToStorage() after every edit.
    //  We replace it with our API save, and also write to localStorage as a
    //  local cache so back/forward works without a round-trip.
    window.saveStateToStorage = function() {
      // Keep localStorage as a fast local cache
      try {
        const payload = buildStatePayload();
        localStorage.setItem('studioSchedulerState_v1', JSON.stringify(payload));
      } catch (_) {}
      // Debounce API writes: wait 800 ms after the last call before saving
      clearTimeout(window._ssApiSaveTimer);
      window._ssApiSaveTimer = setTimeout(() => saveToApi(), 800);
    };

    //  The original app also calls saveToConnectedFile() directly in some paths.
    window.saveToConnectedFile = function() {
      return saveToApi();
    };

    //  connectToDataFile() is wired to the Connect button — replace it too.
    window.connectToDataFile = function() {
      loadFromApi();
    };

    // ── 3. Load initial data ─────────────────────────────────────────────────
    // First try localStorage for instant render, then sync from DB
    const cached = (() => {
      try { return JSON.parse(localStorage.getItem('studioSchedulerState_v1') || ''); }
      catch (_) { return null; }
    })();

    if (cached && Array.isArray(cached.jobs) && cached.jobs.length) {
      applyLoadedState(cached, { fromEmbedded: false });
      renderSchedule();
      setStatus('connected', 'Loaded from cache — syncing…');
    }

    // Always fetch fresh data from DB
    loadFromApi().then(() => {
      window._ssLastLoadedAt = new Date();
    });

    // ── 4. Ensure user name (same as original) now moved to app.js ───────────────────────────────
    //ensureUserName();

    // ── 5. Start background poll ─────────────────────────────────────────────
    startPoll(900000); // 15 minutes
  }

  // ── Public API ───────────────────────────────────────────────────────────────
  return { init, loadFromApi, saveToApi, patchJob, deleteJob, startPoll, stopPoll };

})();
