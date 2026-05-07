<?php
/**
 * Shortcode template for Studio Scheduler.
 * Rendered by ss_render_shortcode() via [studio_scheduler] shortcode.
 *
 * This file contains only the structural HTML shell — the navbar, tab bar,
 * and mount-point divs. All dynamic content is rendered by app.js exactly
 * as it was in the original standalone HTML file.
 *
 * The <style> block and all <script> tags from the original HTML have been
 * split out into:
 *   assets/app.css  — all CSS from the original <style> block
 *   assets/app.js   — all JS from the original <script> blocks
 *
 * Enqueuing is handled by ss_enqueue_assets() in studio-scheduler.php.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Logo URLs — served from the plugin's assets folder.
// Upload your logo files to /wp-content/plugins/studio-scheduler/assets/brand/
$logo_png = SS_PLUGIN_URL . 'assets/brand/1876_Productions_WM_RGB_Pos.png';
$logo_svg = SS_PLUGIN_URL . 'assets/brand/1876-logo.svg';
?>
<div id="ss-root">

  <!-- ── Navbar ─────────────────────────────────────────────────────────── -->
  <div class="navbar">
    <div class="navbar-inner">
      <div class="brand">
        <img src="<?php echo esc_url( $logo_png ); ?>"
             alt="1876 Productions"
             class="logo"
             onerror="this.onerror=null; this.src='<?php echo esc_url( $logo_svg ); ?>'">
      </div>
      <div class="nav-title">Studio Production Scheduler <span class="small">2.4.26</span></div>
      <div class="spacer"></div>

      <!-- User / role display -->
      <div style="display:flex; align-items:center; gap:8px;">
        <span id="navRoleBadge"
              style="font-size:0.65rem; font-weight:700; color:#fff; background:#6b7280;
                     padding:2px 8px; border-radius:10px; text-transform:uppercase; letter-spacing:0.05em;">
          View
        </span>
        <div id="navUserDisplay"
            
             title="Click to change your name"
             style="font-size:0.95rem; font-weight:600; color:#0ea5e9; cursor:pointer;
                    white-space:nowrap; padding:5px 10px; border-radius:6px;
                    border:1px solid #0ea5e9; background:rgba(14,165,233,0.08);">
        </div>
        <span id="navUserNotYou"
             
              title="Click to change your name"
              style="font-size:0.78rem; color:#94a3b8; cursor:pointer; white-space:nowrap;
                     text-decoration:underline; display:none;">
          Not you?
        </span>
      </div>

      <!-- Database connection status (replaces FSA file status) -->
      <div style="display:flex; align-items:center; gap:8px; margin-left:12px;">
        <span id="fsaStatus" style="font-size:0.75rem; color:#9ca3af;">○ Loading…</span>
        <button id="connectFileBtn"
                style="font-size:0.72rem; padding:3px 10px; border-radius:6px;
                       border:1px solid #ef4444; background:#ef4444; color:#fff;
                       font-weight:600; cursor:pointer; white-space:nowrap;">
          Connect to Database
        </button>
      </div>

    </div>

    <!-- Update banner (shown when another user saves while you're viewing) -->
    <div id="updateBanner"
         style="display:none; background:#fef3c7; border-top:1px solid #fcd34d;
                padding:8px 20px; font-size:0.82rem; color:#92400e;">
      <span id="updateBannerMsg"></span>
      <button onclick="location.reload()"
              style="margin-left:12px; padding:3px 10px; border-radius:5px;
                     border:1px solid #d97706; background:#d97706; color:#fff;
                     font-size:0.78rem; font-weight:600; cursor:pointer;">
        Reload Now
      </button>
      <button onclick="document.getElementById('updateBanner').style.display='none'"
              style="margin-left:6px; padding:3px 10px; border-radius:5px;
                     border:1px solid #9ca3af; background:#fff; color:#374151;
                     font-size:0.78rem; cursor:pointer;">
        Dismiss
      </button>
    </div>
  </div>

  <!-- ── App shell ──────────────────────────────────────────────────────── -->
  <div class="app-shell">

    <div class="header">
      <div>
        <div class="subtitle">
          Project Capacity and Schedule Planning for Development, Creative Review,
          QA, and Content Delivery. This provides a 6-week view.
        </div>
      </div>
      <div class="pill">v2.4.26 · Live</div>
    </div>

    <!-- Tab bar — visibility controlled by applyRoleUI() in app.js -->
    <div class="tab-bar">
      <button type="button" class="tab active" data-view="calendar">Calendar</button>
      <button type="button" class="tab"         data-view="status">Status Report</button>
    </div>

    <!--
      All view containers (calendarView, statusView, inputView, capacityView)
      are created and mounted by app.js exactly as in the original HTML.
      Nothing else is needed here — the JS handles the DOM.
    -->

  </div><!-- /.app-shell -->
  
     <div id="inputView" class="layout">
      <!-- ── Left: Job entry form ─────────────────────────────── -->
      <div class="card">
        <div class="card-inner">
          <h2 id="formTitle" style="margin-top:0; margin-bottom:4px;">New / Edit Job</h2>
          <div class="card-description" style="margin-bottom:10px;">To create or edit a calendar entry please fill out the fields below.</div>

          <!-- PROJECT INFORMATION -->
          <div class="form-section-label">Project Information</div>
          <div class="row" id="rowInfo">
            <div>
              <input id="jobName" placeholder="Job Name / WF ID">
            </div>
            <div>
              <select id="lob">
                <option value="">LOB</option>
                <option value="MOB">MOB</option>
                <option value="FIB">FIB</option>
                <option value="BUS">BUS</option>
                <option value="FNT">FNT</option>
              </select>
            </div>
            <div>
              <select id="jobType">
                <option value="Adapt">Adapt</option>
                <option value="Adopt">Adopt</option>
                <option value="AssetOrigination">Asset Origination</option>
                <option value="AdHocRequest">Ad Hoc Request</option>
                <option value="ContentDeliveryOnly">Content Delivery Only</option>
                <option value="Vacation">Vacation / Time Off</option>
              </select>
            </div>
          </div>

          <!-- Project Dates -->
          <div style="display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:4px;">
            <div style="font-size:0.72rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em;">Project Dates</div>
            <button type="button" id="slaFillBtn" title="Auto-fill empty dates from Start Date using SLA rules" style="font-size:0.7rem; padding:2px 8px; height:22px; border-radius:5px; border:1px solid #009FDB; background:#fff; color:#009FDB; font-weight:600; cursor:pointer; line-height:1;">↻ SLA</button>
          </div>
          <div class="date-btn-row">
            <div class="date-btn-wrap">
              <label for="startDate">Start Date</label>
              <input id="startDate" type="date">
            </div>
            <div class="date-btn-wrap">
              <label for="cdDate" id="cdDateLabel">CD Hand Off</label>
              <input id="cdDate" type="date">
            </div>
            <div class="date-btn-wrap" id="fieldLiveDate">
              <label for="liveDate">Media Hand Off</label>
              <input id="liveDate" type="date">
            </div>
            <div class="date-btn-wrap">
              <label for="goLiveDate">Live Date</label>
              <input id="goLiveDate" type="date">
            </div>
          </div>

          <div class="row" style="align-items:center;">
            <div>
              <select id="loe">
                <option value="0.75">LOE: Light (0.75×)</option>
                <option value="1" selected>LOE: Normal (1.0×)</option>
                <option value="1.5">LOE: Heavy (1.5×)</option>
                <option value="2">LOE: Extreme (2.0×)</option>
              </select>
            </div>
            <div>
              <select id="risk">
                <option value="">Risk</option>
                <option value="On Track">On Track</option>
                <option value="At Risk">At Risk</option>
                <option value="Off Track">Off Track</option>
              </select>
            </div>
            <div id="fieldPriority">
              <select id="priority">
                <option value="">Priority</option>
                <option value="1">1 – Highest</option>
                <option value="2">2 – High</option>
                <option value="3">3 – Low</option>
                <option value="4">4 – Lowest</option>
              </select>
            </div>
            <div style="width:1px; background:#BDC2C7; align-self:stretch; margin:0 4px; flex-shrink:0;"></div>
            <div style="flex:1;">
              <input id="mediaPartner" placeholder="Media Partner" style="width:100%;">
            </div>
            <div id="fieldMarketer" style="flex:1;">
              <input id="marketer" list="dlMarketer" placeholder="Marketer" style="width:100%;">
            </div>
          </div>

          <!-- Vacation-only row (hidden for regular jobs) -->
          <div class="row" id="vacationOnlyFields" style="display:none;">
            <div>
              <input id="vacationPerson" list="vacationPersonList" placeholder="Person" autocomplete="off">
              <datalist id="vacationPersonList">
                <option value="Keith Mallett">
                <option value="Eric Howell">
                <option value="Neal Lee">
                <option value="Ariel Manigsaca">
                <option value="Aaron Sterczewski">
                <option value="Jeremy Hagan">
                <option value="Angela Gomez">
                <option value="Joe Wilk">
                <option value="Bradley Lammie">
                <option value="Chris Depietro">
                <option value="Joe Brown">
                <option value="Tracy McClendon">
              </datalist>
            </div>
            <div>
              <label for="vacationTrack">Function Group Affected</label>
              <select id="vacationTrack">
                <option value="all">All Groups</option>
                <option value="social+display">Social + Display (Dev)</option>
                <option value="social">Social</option>
                <option value="display">Display</option>
                <option value="statics">Statics</option>
                <option value="review">Creative Review</option>
                <option value="qa">Quality Assurance</option>
                <option value="cd">Content Delivery</option>
              </select>
            </div>
          </div>

          <!-- RESOURCES -->
          <div class="form-section-label" id="sectionResources">Resources</div>
          <datalist id="dlSocial"></datalist>
          <datalist id="dlDisplay"></datalist>
          <datalist id="dlStatics"></datalist>
          <datalist id="dlCreative"></datalist>
          <datalist id="dlProofing"></datalist>
          <datalist id="dlQA"></datalist>
          <datalist id="dlContent"></datalist>
          <datalist id="dlProducer"></datalist>
          <datalist id="dlCp"></datalist>
          <datalist id="dlMarketer"></datalist>
          <div class="row" id="jobOnlyAssignee1">
            <div id="fieldCp">
              <input id="cp" list="dlCp" placeholder="CP">
            </div>
            <div>
              <input id="producer" list="dlProducer" placeholder="Producer">
            </div>
            <div>
              <input id="assigneeAd" list="dlCreative" placeholder="Creative">
            </div>
            <div>
              <input id="assigneeSocial" list="dlSocial" placeholder="Social">
            </div>
            <div>
              <input id="assigneeDisplay" list="dlDisplay" placeholder="Display">
            </div>
            <div>
              <input id="assigneeStatic" list="dlStatics" placeholder="Static">
            </div>
          </div>
          <input type="hidden" id="assigneeReview">
          <div class="row" id="jobOnlyAssignee2">
            <div>
              <input id="assigneeQa" list="dlProofing" placeholder="Proofing">
            </div>
            <div>
              <input id="assigneeCd" list="dlQA" placeholder="QA">
            </div>
            <div>
              <input id="assigneeContent" list="dlContent" placeholder="Content">
            </div>
          </div>

          <!-- NUMBER OF ASSETS -->
          <div class="form-section-label" id="sectionAssets">Number of Assets</div>
          <div class="row" id="jobOnlyAssets">
            <div>
              <input id="fxfAssets" type="number" min="0" value="" placeholder="FxF">
            </div>
            <div>
              <input id="socialAssets" type="number" min="0" value="" placeholder="Social">
            </div>
            <div>
              <input id="displayAssets" type="number" min="0" value="" placeholder="Display">
            </div>
            <div>
              <input id="staticAssets" type="number" min="0" value="" placeholder="Statics">
            </div>
            <div>
              <input id="cdOnlyAssets" type="number" min="0" value="" placeholder="CD Only">
            </div>
            <div>
              <input id="cdPlacements" type="number" min="0" value="" placeholder="CD Place">
            </div>
          </div>
          <input type="hidden" id="totalAssets" value="0">

          <!-- TASK TIME -->
          <div style="display:flex; align-items:center; justify-content:space-between; margin:20px 0 8px;">
            <div class="form-section-label" id="sectionTaskTime" style="margin:0;">Task Time (in mins)</div>
            <div style="display:flex; align-items:center; gap:8px;">
              <label for="estRounds" style="font-size:0.72rem; font-weight:700; color:#00388F; white-space:nowrap;">Est. Rounds</label>
              <input id="estRounds" type="range" min="1" max="5" step="1" value="1"
                style="width:90px; height:4px; accent-color:#009FDB; cursor:pointer; padding:0; background:none; border:none; box-shadow:none;">
              <span id="estRoundsLabel" style="font-size:0.75rem; font-weight:700; color:#009FDB; min-width:48px; text-align:right;">1 (×1.00)</span>
            </div>
          </div>
          <div class="row" id="jobOnlyMinutes">
            <div>
              <div class="field-label">FxF</div>
              <input id="fxfMinutes" type="number" min="0" value="30">
            </div>
            <div>
              <div class="field-label">Social</div>
              <input id="devMinutesSocial" type="number" min="0" value="30">
            </div>
            <div>
              <div class="field-label">Display</div>
              <input id="devMinutesDisplay" type="number" min="0" value="45">
            </div>
            <div>
              <div class="field-label">Static</div>
              <input id="devMinutesStatic" type="number" min="0" value="15">
            </div>
            <div>
              <div class="field-label">Proofing</div>
              <input id="reviewMinutes" type="number" min="0" value="10">
            </div>
            <div>
              <div class="field-label">Creative</div>
              <input id="qaMinutes" type="number" min="0" value="10">
            </div>
            <div>
              <div class="field-label">QA</div>
              <input id="cdMinutes" type="number" min="0" value="10">
            </div>
            <div>
              <div class="field-label">CD</div>
              <input id="cdDeliveryMinutes" type="number" min="0" value="10">
            </div>
          </div>

          <!-- HOURS hidden spans — values computed for sidebar -->
          <span id="socialDevHours" style="display:none;">0.0</span>
          <span id="displayDevHours" style="display:none;">0.0</span>
          <span id="staticDevHours" style="display:none;">0.0</span>
          <span id="reviewHours" style="display:none;">0.0</span>
          <span id="qaHours" style="display:none;">0.0</span>
          <span id="cdHours" style="display:none;">0.0</span>

          <!-- PROJECT NOTES -->
          <div class="form-section-label">Project Notes</div>
          <div>
            <textarea id="jobNotes" placeholder="Add any notes or context for this job…" style="width:100%; min-height:80px; padding:8px 10px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#111827; font:inherit; font-size:0.9em; resize:vertical;"></textarea>
          </div>

          <div style="margin-top:14px; display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap;">
            <button type="button" class="btn" id="saveJobBtn">Save Job to Schedule</button>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
              <button type="button" class="btn btn-secondary" id="clearFormBtn">Clear Form</button>
              <button type="button" class="btn btn-secondary" id="deleteJobBtn" style="border-color:#b91c1c;">Delete Job</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Right: Project Summary sidebar ──────────────────── -->
      <div class="summary-card" id="jobSummarySidebar">
        <h3>Project Summary</h3>

        <div class="summary-label" id="sumJobName">JOB ID / WF #</div>

        <div style="display:flex; gap:8px; margin-bottom:10px;">
          <div style="flex:1; background:#fff; border:1px solid #009FDB; border-radius:10px; padding:6px 8px; text-align:center;">
            <div style="font-size:0.55rem; color:#00388F; font-weight:700; text-transform:uppercase; white-space:nowrap;">Total Assets</div>
            <div style="font-size:1.1rem; font-weight:700; color:#00388F;" id="sumTotalAssets">0</div>
          </div>
          <div style="flex:1; background:#fff; border:1px solid #009FDB; border-radius:10px; padding:6px 8px; text-align:center;">
            <div style="font-size:0.65rem; color:#00388F; font-weight:700; text-transform:uppercase;">LOE</div>
            <div style="font-size:1.1rem; font-weight:700; color:#00388F;" id="sumLoe">—</div>
          </div>
          <div style="flex:1; background:#fff; border:1px solid #009FDB; border-radius:10px; padding:6px 8px; text-align:center;">
            <div style="font-size:0.65rem; color:#00388F; font-weight:700; text-transform:uppercase;">Priority</div>
            <div style="font-size:1.1rem; font-weight:700; color:#00388F;" id="sumPriority">—</div>
          </div>
        </div>

        <hr class="summary-divider">
        <div class="summary-dates">
          <p><span class="lbl">Start Date:</span> <span id="sumStartDate">00/00/0000</span></p>
          <p><span class="lbl">CD Hand Off:</span> <span id="sumCdDate">00/00/0000</span></p>
          <p><span class="lbl">Media Hand Off:</span> <span id="sumLiveDate">00/00/0000</span></p>
          <p><span class="lbl">Live Date:</span> <span id="sumGoLiveDate">00/00/0000</span></p>
        </div>

        <hr class="summary-divider">
        <div style="font-size:16px; font-weight:700; color:#00388F; margin-bottom:6px;">Estimated Project Hours</div>
        <div class="summary-asset-grid" id="sumAssetGrid">
          <div class="summary-asset-cell"><span class="lbl">Social</span><span class="num" id="sumSocial">0</span></div>
          <div class="summary-asset-cell"><span class="lbl">Display</span><span class="num" id="sumDisplay">0</span></div>
          <div class="summary-asset-cell"><span class="lbl">Statics</span><span class="num" id="sumStatics">0</span></div>
          <div class="summary-asset-cell"><span class="lbl">Proofing</span><span class="num" id="sumProofing">0</span></div>
          <div class="summary-asset-cell"><span class="lbl">Creative</span><span class="num" id="sumCreative">0</span></div>
          <div class="summary-asset-cell"><span class="lbl">CD</span><span class="num" id="sumCD">0</span></div>
        </div>

        <div style="display:flex; gap:8px; margin-top:10px;">
          <div style="flex:1; background:#fff; border:1px solid #009FDB; border-radius:10px; padding:6px 8px; text-align:center;">
            <div style="font-size:0.65rem; color:#00388F; font-weight:700; text-transform:uppercase;">Total Est. Hours</div>
            <div style="font-size:1.1rem; font-weight:700; color:#00388F;" id="sumTotalHours">0</div>
          </div>
        </div>
      </div>
    </div>

    <div id="calendarView" style="display:none; margin-top:20px;">
      <div class="card">
        <div class="card-inner">
          <div class="toolbar">
            <div style="display:flex; align-items:center; justify-content:space-between; width:100%; gap:12px;">
              <div>
                <h2 style="margin-bottom:4px;">Schedule View</h2>
                <div class="card-description" style="margin-bottom:0;">Six-week studio calendar, starting from today.</div>
              </div>
              <div style="display:flex; gap:6px; align-items:center; flex-shrink:0;">
    <span id="fsaStatus" style="font-size:0.7rem; white-space:nowrap; color:var(--muted);">&#9675; Loading...</span>
    <span id="fsaUserDisplay" style="font-size: 0.7rem; white-space:nowrap;color:var(--muted);"></span>
    <button type="button" class="btn btn-secondary" id="connectFileBtn" style="font-size:0.72rem; padding:4px 10px; background:#22c55e; color:#fff; border-color:#22c55e;">Connected to Database</button>
                <button type="button" class="btn btn-secondary" id="importExcelBtn" style="font-size:0.72rem; padding:4px 10px;">Import Excel</button>
                <button type="button" class="btn btn-secondary" id="importUsersExcelBtn" style="font-size:0.72rem; padding:4px 10px;">Import Users</button>
                <button type="button" class="btn btn-secondary" id="mergeJsonBtn" style="font-size:0.72rem; padding:4px 10px;" title="Add jobs from another export without overwriting existing jobs">Merge JSON</button>
                <button type="button" class="btn btn-secondary" id="exportJsonBtn" style="font-size:0.72rem; padding:4px 10px;">Export JSON</button>
                <input type="file" id="importJsonFile" accept="application/json" style="display:none;">
                <input type="file" id="mergeJsonFile" accept="application/json" style="display:none;">
                <input type="file" id="importExcelFile" accept=".xlsx,.xls" style="display:none;">
                <input type="file" id="importUsersExcelFile" accept=".xlsx,.xls,.csv" style="display:none;">
              </div>
            </div>
              <div style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; width:100%; justify-content:space-between; padding-top:20px;">
              <div style="display:flex; gap:8px; align-items:center; flex-wrap:nowrap; overflow-x:auto;">
                <span style="font-size:0.8rem; color:var(--muted);">Filter</span>
                <input id="filterJobName" placeholder="Job Number / ID" style="min-width:260px; max-width:320px;">
                <select id="filterLob" style="min-width:120px; max-width:160px;">
                  <option value="">LOB</option>
                  <option value="MOB">MOB</option>
                  <option value="BUS">BUS</option>
                  <option value="FIB">FIB</option>
                  <option value="FNT">FNT</option>
                </select>
                <select id="filterJobType" style="min-width:160px; max-width:220px;">
                  <option value="">Job Type</option>
                  <option value="Adapt">Adapt</option>
                  <option value="Adopt">Adopt</option>
                  <option value="AssetOrigination">Asset Origination</option>
                  <option value="AdHocRequest">Ad Hoc Request</option>
                  <option value="ContentDeliveryOnly">Content Delivery Only</option>
                  <option value="Vacation">Vacation / Time Off</option>
                </select>
                <select id="filterPhase" style="min-width:180px; max-width:240px;">
                  <option value="">All Phases</option>
                  <option value="active">Active Only</option>
                  <option value="Discovery">Discovery</option>
                  <option value="Pre-Production">Pre-Production</option>
                  <option value="In Production">In Production</option>
                  <option value="Post-Production">Post-Production</option>
                  <option value="Project Closeout">Project Closeout</option>
                  <option value="Completed">Completed</option>
                  <option value="Canceled">Canceled</option>
                </select>
                <input id="filterResource" placeholder="Resource" list="dlAllResources" autocomplete="off" style="min-width:160px; max-width:220px;">
                <datalist id="dlAllResources"></datalist>
              </div>
              <div style="display:flex; gap:8px; align-items:center; justify-content:center; width:100%; padding-top:16px;">
                <button type="button" class="btn btn-secondary" id="prevWeekBtn">← Prev</button>
                <button type="button" class="btn btn-secondary" id="todayBtn">Jump to Today</button>
                <button type="button" class="btn btn-secondary" id="nextWeekBtn">Next →</button>
              </div>
            </div>
          </div>

          <div id="updateBanner" style="display:none; background:#f97316; color:#fff; padding:8px 16px; border-radius:6px; margin-bottom:10px; gap:12px;">
            <span id="updateBannerMsg" style="flex:1; font-size:0.85rem;"></span>
            <div style="display:flex; gap:8px; flex-shrink:0;">
              <button type="button" id="updateBannerReloadBtn" style="background:#fff; color:#f97316; border:none; padding:4px 12px; border-radius:4px; cursor:pointer; font-size:0.8rem; font-weight:600;">Reload</button>
              <button type="button" id="updateBannerDismissBtn" style="background:rgba(255,255,255,0.25); color:#fff; border:none; padding:4px 12px; border-radius:4px; cursor:pointer; font-size:0.8rem;">Dismiss</button>
            </div>
          </div>
          <div style="margin-bottom:8px; font-size:0.8rem; color:var(--muted); text-align:center;" id="windowLabel">Showing 6 weeks from today.</div>

          <div id="statusBoard" style="margin-bottom:12px;"></div>

          <div class="schedule-grid" id="scheduleGrid">
            <!-- Header + rows rendered by JS -->
          </div>

          <div id="unscheduledPanel" style="display:none; margin-top:20px; margin-bottom:16px; font-size:0.75rem; border-radius:6px; border:1px solid #e5e7eb; background:#fff; overflow:hidden;"></div>
          <div id="capacitySummary" class="capacity-summary"></div>
          <div id="assigneeSummary" class="assignee-summary"></div>
          <div id="timeOffSummary" class="capacity-summary" style="display:none;"></div>
        </div>
      </div>
    </div>

    <!-- ── STATUS REPORT VIEW ─────────────────────────────────────────── -->
    <div id="statusReportView" style="display:none; margin-top:20px;">

      <!-- ── Team Panel ──────────────────────────────────────────── -->
      <div class="card" id="teamPanelCard" style="margin-bottom:14px;">
        <div class="card-inner">
          <div style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;" id="teamPanelToggle">
            <h2 style="margin:0;font-size:1rem;">Team &amp; Resources</h2>
            <span id="teamPanelChevron" style="font-size:0.9rem;color:#009FDB;font-weight:700;">▶ Show</span>
          </div>
          <div id="teamPanelBody" style="display:none;margin-top:12px;">
            <div style="font-size:0.72rem;color:#6b7280;margin-bottom:10px;">Add team members, set their access role, and check which scheduling dropdowns they appear in. One person can have multiple functions.</div>
            <table id="teamTable" style="width:100%;border-collapse:collapse;font-size:0.82rem;">
              <thead>
                <tr style="border-bottom:2px solid #e5e7eb;">
                  <th style="text-align:left;padding:4px 8px;color:#00388F;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;width:20%;">Name</th>
                  <th style="text-align:left;padding:4px 8px;color:#00388F;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;width:14%;">Access Role</th>
                  <th style="text-align:left;padding:4px 8px;color:#00388F;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;">Functions (dropdowns)</th>
                  <th style="text-align:left;padding:4px 8px;color:#00388F;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;">LOBs (CP/Producer access)</th>
                  <th style="width:32px;"></th>
                </tr>
              </thead>
              <tbody id="teamTableBody">
                <!-- rows rendered by renderTeamPanel() -->
              </tbody>
            </table>
            <button type="button" id="teamAddRowBtn" style="margin-top:10px;font-size:0.78rem;padding:4px 12px;border-radius:6px;border:1px solid #009FDB;background:#fff;color:#009FDB;font-weight:600;cursor:pointer;">+ Add Person</button>
          </div>
        </div>
      </div>
        <div class="card-inner">
          <div class="toolbar">
            <!-- Title row + action buttons -->
            <div style="display:flex; align-items:flex-start; justify-content:space-between; width:100%; gap:12px; flex-wrap:wrap; margin-bottom:14px;">
              <div>
                <h2 style="margin-bottom:2px;">Project Status Report</h2>
                <div class="card-description" style="margin-bottom:0;">Living list — click ▶ to expand a job and edit details. Calendar updates automatically.</div>
              </div>
              <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                <button type="button" id="srAddJobBtn" style="font-size:0.78rem; padding:4px 12px; border-radius:6px; border:1px solid #009FDB; background:#009FDB; color:#fff; font-weight:600; cursor:pointer;">+ New Job</button>
                <button type="button" id="srImportJsonBtn" style="font-size:0.78rem; padding:4px 12px; border-radius:6px; border:1px solid #6b7280; background:#fff; color:#374151; font-weight:600; cursor:pointer;">⬆ Import JSON</button>
                <button type="button" id="srMergeJsonBtn" style="font-size:0.78rem; padding:4px 12px; border-radius:6px; border:1px solid #6b7280; background:#fff; color:#374151; font-weight:600; cursor:pointer;">⊕ Merge JSON</button>
                <button type="button" id="srExportJsonBtn" style="font-size:0.78rem; padding:4px 12px; border-radius:6px; border:1px solid #6b7280; background:#fff; color:#374151; font-weight:600; cursor:pointer;">⬇ Export JSON</button>
                <button type="button" id="srExportXlsxBtn" style="font-size:0.78rem; padding:4px 12px; border-radius:6px; border:1px solid #00388F; background:#00388F; color:#fff; font-weight:600; cursor:pointer;">⬇ Export XLSX</button>
                <input type="file" id="srImportJsonFile" accept="application/json" style="display:none;">
                <input type="file" id="srMergeJsonFile" accept="application/json" style="display:none;">
              </div>
            </div>

            <!-- Search / Filter section -->
            <div style="border-top:1px solid #e5e7eb; padding-top:12px;">
              <div style="display:flex; gap:8px; align-items:center; flex-wrap:nowrap; overflow-x:auto;">
                <span style="font-size:0.8rem; color:var(--muted); white-space:nowrap;">Filter</span>
                <input id="srFilterName" placeholder="Search by Job Name / WF ID…" style="min-width:260px; max-width:380px;">
                <select id="srFilterLob">
                  <option value="">LOB</option>
                  <option value="BUS">BUS</option>
                  <option value="FIB">FIB</option>
                  <option value="MOB">MOB</option>
                  <option value="FNT">FNT</option>
                </select>
                <select id="srFilterType">
                  <option value="">All Project Types</option>
                  <option>Creative Origination</option>
                  <option>Creative Adaptation/Adoption</option>
                  <option>Trafficking Only</option>
                </select>
                <select id="srFilterPhase">
                  <option value="">All Phases</option>
                  <option value="active">Active Only</option>
                  <option value="Discovery">Discovery</option>
                  <option value="Pre-Production">Pre-Production</option>
                  <option value="In Production">In Production</option>
                  <option value="Post-Production">Post-Production</option>
                  <option value="Project Closeout">Project Closeout</option>
                  <option value="Completed">Completed</option>
                  <option value="Canceled">Canceled</option>
                </select>
              </div>
            </div>
          </div>

          <div id="srTable" style="margin-top:12px; overflow-x:auto;">
            <!-- rendered by renderStatusReport() -->
          </div>
        </div>
      </div>
    </div>

    <div id="jobModal" class="modal-backdrop">
      <div class="modal-card" role="dialog" aria-modal="true" style="max-width:620px;">
        <div class="modal-header">
          <div class="modal-title" id="jobModalTitle">Job Details</div>
          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="jobModalEditBtn" style="padding:3px 12px; font-size:0.75rem; white-space:nowrap;">Edit Job</button>
            <button type="button" class="modal-close" id="jobModalCloseBtn">&times;</button>
          </div>
        </div>

        <!-- Row 2: LOB · Priority · Job Type -->
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
          <span id="jobModalLobBadge" style="font-size:0.72rem; font-weight:700; padding:2px 8px; border-radius:10px; background:#009FDB; color:#fff;"></span>
          <span id="jobModalPriorityBadge" style="font-size:0.72rem; font-weight:700; padding:2px 8px; border-radius:10px; background:#6b7280; color:#fff;"></span>
          <span id="jobModalTypeBadge" style="font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:10px; background:#f3f4f6; color:#374151; border:1px solid #e5e7eb;"></span>
          <span id="jobModalLoeBadge" style="font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:10px; background:#f3f4f6; color:#374151; border:1px solid #e5e7eb;"></span>
        </div>

        <!-- Row 3: Marketer / CP / Producer -->
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:14px; padding-bottom:14px; border-bottom:1px solid var(--border);">
          <div>
            <div class="label">Marketer</div>
            <div class="value" id="jobModalMarketer">—</div>
          </div>
          <div>
            <div class="label">CP</div>
            <div class="value" id="jobModalCp">—</div>
          </div>
          <div>
            <div class="label">Producer</div>
            <div class="value" id="jobModalProducer">—</div>
          </div>
        </div>

        <!-- Row 4: Dates -->
        <div style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#6b7280; margin-bottom:6px;">Project Dates</div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:14px; padding-bottom:14px; border-bottom:1px solid var(--border);">
          <div>
            <div class="label">Start</div>
            <div class="value" id="jobModalStart">—</div>
          </div>
          <div>
            <div class="label">CD Hand Off</div>
            <div class="value" id="jobModalCd">—</div>
          </div>
          <div>
            <div class="label">Media Hand Off</div>
            <div class="value" id="jobModalLive">—</div>
          </div>
          <div>
            <div class="label">Go Live</div>
            <div class="value" id="jobModalGoLive">—</div>
          </div>
        </div>

        <!-- Row 5: Resources -->
        <div style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#6b7280; margin-bottom:6px;">Resources</div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:14px; padding-bottom:14px; border-bottom:1px solid var(--border);">
          <div>
            <div class="label">Social</div>
            <div class="value" id="jobModalAssigneeSocial">—</div>
          </div>
          <div>
            <div class="label">Display</div>
            <div class="value" id="jobModalAssigneeDisplay">—</div>
          </div>
          <div>
            <div class="label">Statics</div>
            <div class="value" id="jobModalAssigneeStatic">—</div>
          </div>
          <div>
            <div class="label">Creative Review</div>
            <div class="value" id="jobModalAssigneeReview">—</div>
          </div>
          <div>
            <div class="label">QA / Proofing</div>
            <div class="value" id="jobModalAssigneeQa">—</div>
          </div>
          <div>
            <div class="label">CD</div>
            <div class="value" id="jobModalAssigneeCd">—</div>
          </div>
          <div>
            <div class="label">Content</div>
            <div class="value" id="jobModalAssigneeContent">—</div>
          </div>
        </div>

        <!-- Row 6: Asset Counts -->
        <div style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#6b7280; margin-bottom:6px;">Asset Counts</div>
        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:14px; padding-bottom:14px; border-bottom:1px solid var(--border);">
          <div>
            <div class="label">Social</div>
            <div class="value" id="jobModalAssetSocial">—</div>
          </div>
          <div>
            <div class="label">Display</div>
            <div class="value" id="jobModalAssetDisplay">—</div>
          </div>
          <div>
            <div class="label">Statics</div>
            <div class="value" id="jobModalAssetStatic">—</div>
          </div>
          <div>
            <div class="label">CD Only</div>
            <div class="value" id="jobModalAssetCdOnly">—</div>
          </div>
          <div>
            <div class="label">Total</div>
            <div class="value" style="font-weight:700;" id="jobModalTotalAssets">—</div>
          </div>
        </div>

        <!-- Row 7: Estimated Hours -->
        <div style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#6b7280; margin-bottom:6px;">Estimated Hours</div>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; padding-bottom:14px; border-bottom:1px solid var(--border);">
          <div>
            <div class="label">Social Dev</div>
            <div class="value" id="jobModalSocialDevH">—</div>
          </div>
          <div>
            <div class="label">Display Dev</div>
            <div class="value" id="jobModalDisplayDevH">—</div>
          </div>
          <div>
            <div class="label">Static Dev</div>
            <div class="value" id="jobModalStaticDevH">—</div>
          </div>
          <div>
            <div class="label">Creative Review</div>
            <div class="value" id="jobModalReviewH">—</div>
          </div>
          <div>
            <div class="label">QA</div>
            <div class="value" id="jobModalQAH">—</div>
          </div>
          <div>
            <div class="label">CD</div>
            <div class="value" id="jobModalCDH">—</div>
          </div>
        </div>

        <!-- CD Placements -->
        <div style="font-size:0.75rem; color:#6b7280; margin-bottom:14px;">
          CD Basis: <span id="jobModalCDPlacements" style="font-weight:600; color:#374151;">—</span>
        </div>

        <!-- Added by / Notes -->
        <div id="jobModalCreatedRow" style="padding-top:10px; border-top:1px solid var(--border); font-size:0.75rem; color:#6b7280; display:none; margin-bottom:10px;">
          Added <span id="jobModalCreatedAt"></span> by <span id="jobModalCreatedBy" style="font-weight:600;"></span>
        </div>
        <div id="jobModalNotesRow" style="padding-top:10px; border-top:1px solid var(--border); display:none;">
          <div class="label" style="margin-bottom:4px;">Notes</div>
          <div id="jobModalNotes" style="font-size:0.85rem; color:#111827; white-space:pre-wrap; line-height:1.5;"></div>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:16px; padding-top:12px; border-top:1px solid var(--border);">
          <button type="button" class="btn btn-secondary" id="jobModalDeleteBtn" style="padding:4px 14px; font-size:0.75rem; border-color:#b91c1c; color:#f97373;">Delete Job</button>
        </div>

        <!-- Comments section — shown for view-* roles only -->
        <div id="jobModalCommentsSection" style="display:none; margin-top:16px; padding-top:14px; border-top:1px solid var(--border);">
          <div style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:#6b7280; margin-bottom:8px;">Comments</div>
          <div id="jobModalCommentsList" style="max-height:180px; overflow-y:auto; margin-bottom:10px;"></div>
          <div style="display:flex; gap:6px; align-items:flex-end;">
            <textarea id="jobModalCommentInput" placeholder="Add a comment…" rows="2" style="flex:1; padding:6px 8px; border-radius:6px; border:1px solid #d1d5db; font:inherit; font-size:0.82rem; resize:none; color:#111827;"></textarea>
            <button type="button" id="jobModalCommentSubmit" style="padding:6px 14px; border-radius:6px; border:1px solid #009FDB; background:#009FDB; color:#fff; font-size:0.8rem; font-weight:600; cursor:pointer; white-space:nowrap;">Post</button>
          </div>
        </div>
      </div>
    </div>
  

  <!-- Resource breakdown modal -->
  <div id="statusDrillModal" class="modal-backdrop">
    <div class="modal-card" role="dialog" aria-modal="true" style="max-width:560px;">
      <div class="modal-header">
        <div class="modal-title" id="statusDrillTitle">Jobs</div>
        <button type="button" class="modal-close" id="statusDrillCloseBtn">&times;</button>
      </div>
      <div id="statusDrillBody" style="font-size:0.82rem;"></div>
    </div>
  </div>

  <div id="assigneeBreakdownModal" class="modal-backdrop">
    <div class="modal-card" role="dialog" aria-modal="true" style="max-width:600px;">
      <div class="modal-header">
        <div class="modal-title" id="assigneeBreakdownTitle">Assignee Breakdown</div>
        <button type="button" class="modal-close" id="assigneeBreakdownCloseBtn">&times;</button>
      </div>
      <div style="font-size:0.8rem; color:var(--muted); margin-bottom:12px;" id="assigneeBreakdownSubtitle"></div>
      <div id="assigneeBreakdownBody"></div>
    </div>
  </div>

  <!-- ── Excel Column Mapping Modal ── -->
  <div id="excelMapModal" class="modal-backdrop">
    <div class="modal-card" role="dialog" aria-modal="true" style="max-width:700px; max-height:90vh; display:flex; flex-direction:column;">
      <div class="modal-header">
        <div class="modal-title">Import from Excel — Map Columns</div>
        <button type="button" class="modal-close" id="excelMapCloseX">&times;</button>
      </div>
      <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px; flex-wrap:wrap;">
        <p style="font-size:0.82rem; color:#6b7280; margin:0; flex:1;">Assign each column to a scheduler field. LOB is set automatically from the sheet name.</p>
        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
          <label style="font-size:0.82rem; font-weight:600; color:#374151; white-space:nowrap;">Import sheet:</label>
          <select id="excelSheetSelector" style="font-size:0.82rem; height:30px; border-radius:4px; border:1px solid #cbd5e1; background:#fff; color:#111827; padding:2px 8px;"></select>
        </div>
      </div>
      <div style="flex:1; overflow-y:auto; border:1px solid #e5e7eb; border-radius:6px;">
        <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
          <thead>
            <tr style="background:#f1f5f9; position:sticky; top:0; z-index:1;">
              <th style="text-align:left; padding:6px 10px; font-size:0.72rem; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e5e7eb; width:36%;">Excel Column</th>
              <th style="text-align:left; padding:6px 10px; font-size:0.72rem; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e5e7eb; width:24%;">Sample Value</th>
              <th style="text-align:left; padding:6px 10px; font-size:0.72rem; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #e5e7eb; width:40%;">Maps To</th>
            </tr>
          </thead>
          <tbody id="excelMapTableBody"></tbody>
        </table>
      </div>
      <div style="margin-top:14px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding-top:12px; border-top:1px solid #e5e7eb;">
        <div style="display:flex; align-items:center; gap:16px; font-size:0.82rem; color:#374151;">
          <span style="font-weight:600;">Import Mode:</span>
          <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
            <input type="radio" name="excelImportMode" value="append"> Append to existing jobs
          </label>
          <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
            <input type="radio" name="excelImportMode" value="replace" checked> Replace all jobs
          </label>
          <label id="smartMergeOption" style="display:flex; align-items:center; gap:5px; cursor:pointer;">
            <input type="radio" name="excelImportMode" value="smartmerge"> Smart Merge
          </label>
        </div>
        <div style="display:flex; gap:8px;">
          <button type="button" class="btn btn-secondary" id="excelMapResetBtn" title="Clear saved column mapping and re-auto-detect">Reset Mapping</button>
          <button type="button" class="btn btn-secondary" id="excelMapCancelBtn">Cancel</button>
          <button type="button" class="btn" id="excelMapImportBtn">Import Jobs</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- ── New Job Modal ──────────────────────────────────────────────────────── -->
  <div id="jobFormModal" style="display:none; position:fixed; inset:0; z-index:900; overflow-y:auto; background:rgba(0,0,0,0.55); padding:24px 16px;">
    <div style="max-width:960px; margin:0 auto; background:#fff; border-radius:12px; box-shadow:0 24px 64px rgba(0,0,0,0.45); overflow:hidden; display:flex; flex-direction:column;">
      <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 20px; background:#00388F; color:#fff; flex-shrink:0;">
        <span style="font-weight:700; font-size:1rem; letter-spacing:0.02em;">New Job</span>
        <button id="jobFormModalClose" style="background:none; border:none; color:#fff; font-size:1.6rem; line-height:1; cursor:pointer; padding:0 4px;">&times;</button>
      </div>
      <div id="jobFormModalBody" style="overflow-y:auto; max-height:calc(100vh - 200px);"></div>
      <div style="display:flex; justify-content:flex-end; gap:10px; padding:12px 20px; border-top:1px solid #e5e7eb; flex-shrink:0; background:#f9fafb;">
        <button id="jobFormModalCancel" style="padding:7px 20px; border-radius:6px; border:1px solid #d1d5db; background:#fff; cursor:pointer; font-size:0.85rem; font-weight:600;">Cancel</button>
        <button id="jobFormModalSave" style="padding:7px 20px; border-radius:6px; border:none; background:#00388F; color:#fff; cursor:pointer; font-size:0.85rem; font-weight:700;">Save Job</button>
      </div>
    </div>
  </div>

</div><!-- /#ss-root -->

<!--
  IMPORTANT:
  app.js references several <div id="..."> and <input id="..."> elements that
  were defined inline in the original HTML (job modals, the input form, etc.).

  Those elements are rendered by app.js itself — they do NOT need to be in this
  template. If you see "Cannot read properties of null" errors in the console,
  check that the element is being created by the JS before it is accessed.

  The api-adapter.js is loaded BEFORE app.js (via wp_enqueue_script dependency)
  and calls SS_WP.init() after the DOM is ready.
-->
