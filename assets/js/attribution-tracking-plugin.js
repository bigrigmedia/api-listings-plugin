/**
 * Legacy Communities - Multi-Touch Attribution Tracker
 * v2.0 - Plugin-injected version (no Google Tag Manager dependency)
 *
 * WHAT THIS DOES
 * 1. Captures original source (rolling 30-day window, refreshed on every visit),
 *    last touch (updates on new sessions or any tracked click), and independent
 *    30-day flags for Google ad clicks (gclid) and Facebook ad clicks (fbclid).
 * 2. Populates matching hidden <input> fields on every <form> on the page.
 * 3. Re-populates on form submission and on any form added to the page after
 *    initial load (e.g. AJAX-loaded contact forms, page-builder widgets).
 * 4. Optionally pushes to window.dataLayer if GTM/GA4 is also present on the
 *    site, so nothing is lost for sites still using GTM for other purposes.
 *
 * DEPLOYMENT
 * Inject this entire script near the top of <body>, or via wp_footer /
 * wp_head if this is a WordPress plugin. It is safe to run as early as
 * possible - the capture logic has no DOM dependency, and the form-population
 * logic waits for DOMContentLoaded internally.
 *
 * REQUIRED HIDDEN FIELDS ON EACH SITE'S FORM(S):
 *   utm_source, utm_medium, utm_campaign, utm_content, utm_term, gclid, fbclid,
 *   original_utm_source, original_utm_medium, original_utm_campaign,
 *   google_ad_clicked, google_ad_last_clicked_at,
 *   facebook_ad_clicked, facebook_ad_last_clicked_at
 * (14 total - see the dev handoff doc for the exact <input> markup.)
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'lc_attribution_v2';
  var SESSION_KEY = 'lc_attrib_session_marked';
  var THIRTY_DAYS_MS = 30 * 24 * 60 * 60 * 1000;

  // ---------------------------------------------------------------------
  // PART 1: CAPTURE - runs immediately, no DOM dependency
  // ---------------------------------------------------------------------
  function captureAttribution() {
    var params;
    try {
      params = new URLSearchParams(window.location.search);
    } catch (e) {
      return null; // Extremely old browser without URLSearchParams support - bail safely
    }

    var gclid = params.get('gclid') || '';
    var fbclid = params.get('fbclid') || '';
    var utm_source = params.get('utm_source') || '';
    var utm_medium = params.get('utm_medium') || '';
    var utm_campaign = params.get('utm_campaign') || '';
    var utm_content = params.get('utm_content') || '';
    var utm_term = params.get('utm_term') || '';

    var hasAnyParam = !!(utm_source || utm_medium || utm_campaign || utm_content || utm_term || gclid || fbclid);

    var referrer = document.referrer || '';
    var referrerHost = '';
    try { referrerHost = new URL(referrer).hostname; } catch (e) {}
    var isSameSite = referrerHost === window.location.hostname;

    var searchEngines = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'baidu.', 'yandex.'];
    var isSearchEngine = searchEngines.some(function (s) { return referrerHost.indexOf(s) !== -1; });

    function classify() {
      var src = utm_source, med = utm_medium;
      if (!src && !med) {
        if (gclid) {
          src = 'google'; med = 'paid search';
        } else if (fbclid) {
          src = 'facebook'; med = 'paid social';
        } else if (isSearchEngine) {
          src = referrerHost; med = 'organic';
        } else if (referrer && !isSameSite) {
          src = referrerHost; med = 'referral';
        } else if (!referrer) {
          src = '(direct)'; med = '(none)';
        }
      }
      return {
        utm_source: src, utm_medium: med, utm_campaign: utm_campaign,
        utm_content: utm_content, utm_term: utm_term,
        gclid: gclid, fbclid: fbclid,
        referrer: referrer, landing_page: window.location.pathname
      };
    }

    var now = Date.now();
    var store = null;
    try { store = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null'); } catch (e) { store = null; }
    if (!store) store = {};

    // ORIGINAL SOURCE: rolling 30-day window based on last visit, not first visit
    var originalExpired = true;
    if (store.original && store.original.last_seen_at) {
      originalExpired = (now - new Date(store.original.last_seen_at).getTime()) > THIRTY_DAYS_MS;
    }
    if (!store.original || originalExpired) {
      var c = classify();
      store.original = {
        utm_source: c.utm_source, utm_medium: c.utm_medium, utm_campaign: c.utm_campaign,
        utm_content: c.utm_content, utm_term: c.utm_term,
        gclid: c.gclid, fbclid: c.fbclid,
        referrer: c.referrer, landing_page: c.landing_page,
        first_captured_at: new Date(now).toISOString(),
        last_seen_at: new Date(now).toISOString()
      };
    } else {
      store.original.last_seen_at = new Date(now).toISOString();
    }

    // LAST TOUCH: updates on any new session, or any visit carrying tracking params.
    // Does NOT update on plain page-to-page navigation within the same session.
    var isNewSession = false;
    try {
      isNewSession = !sessionStorage.getItem(SESSION_KEY);
      sessionStorage.setItem(SESSION_KEY, '1');
    } catch (e) {
      // sessionStorage unavailable (e.g. privacy mode edge case) - treat every load as new session
      isNewSession = true;
    }

    if (hasAnyParam || isNewSession) {
      var c2 = classify();
      store.last = {
        utm_source: c2.utm_source, utm_medium: c2.utm_medium, utm_campaign: c2.utm_campaign,
        utm_content: c2.utm_content, utm_term: c2.utm_term,
        gclid: c2.gclid, fbclid: c2.fbclid,
        referrer: c2.referrer, landing_page: c2.landing_page,
        captured_at: new Date(now).toISOString()
      };
    }

    // GOOGLE AD FLAG: independent 30-day clock, reset only by a new gclid
    if (gclid) {
      store.google_ad = {
        clicked: true, gclid: gclid,
        utm_campaign: utm_campaign, utm_content: utm_content, utm_term: utm_term,
        last_clicked_at: new Date(now).toISOString()
      };
    } else if (store.google_ad && store.google_ad.clicked) {
      var googleExpired = (now - new Date(store.google_ad.last_clicked_at).getTime()) > THIRTY_DAYS_MS;
      if (googleExpired) store.google_ad.clicked = false;
    }

    // FACEBOOK AD FLAG: same idea, independent of Google's
    if (fbclid) {
      store.facebook_ad = {
        clicked: true, fbclid: fbclid,
        utm_campaign: utm_campaign, utm_content: utm_content, utm_term: utm_term,
        last_clicked_at: new Date(now).toISOString()
      };
    } else if (store.facebook_ad && store.facebook_ad.clicked) {
      var fbExpired = (now - new Date(store.facebook_ad.last_clicked_at).getTime()) > THIRTY_DAYS_MS;
      if (fbExpired) store.facebook_ad.clicked = false;
    }

    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
    } catch (e) {
      // localStorage unavailable/full - attribution won't persist this visit, but don't break the page
    }

    // Optional: still push to dataLayer for sites that also run GTM/GA4 for other tags
    if (window.dataLayer && typeof window.dataLayer.push === 'function') {
      window.dataLayer.push({ event: 'attribution_updated', attribution: store });
    }

    return store;
  }

  // ---------------------------------------------------------------------
  // PART 2: POPULATE - fills matching hidden fields on every form
  // ---------------------------------------------------------------------
  function buildFieldValues(store) {
    var now = Date.now();
    var fields = {};

    if (store.last) {
      fields.utm_source = store.last.utm_source || '';
      fields.utm_medium = store.last.utm_medium || '';
      fields.utm_campaign = store.last.utm_campaign || '';
      fields.utm_content = store.last.utm_content || '';
      fields.utm_term = store.last.utm_term || '';
      fields.gclid = store.last.gclid || '';
      fields.fbclid = store.last.fbclid || '';
    }

    if (store.original && store.original.last_seen_at) {
      var originalExpired = (now - new Date(store.original.last_seen_at).getTime()) > THIRTY_DAYS_MS;
      if (!originalExpired) {
        fields.original_utm_source = store.original.utm_source || '';
        fields.original_utm_medium = store.original.utm_medium || '';
        fields.original_utm_campaign = store.original.utm_campaign || '';
      }
    }

    var googleValid = store.google_ad && store.google_ad.clicked && store.google_ad.last_clicked_at &&
      (now - new Date(store.google_ad.last_clicked_at).getTime()) <= THIRTY_DAYS_MS;
    fields.google_ad_clicked = googleValid ? 'true' : 'false';
    fields.google_ad_last_clicked_at = googleValid ? store.google_ad.last_clicked_at : '';

    var fbValid = store.facebook_ad && store.facebook_ad.clicked && store.facebook_ad.last_clicked_at &&
      (now - new Date(store.facebook_ad.last_clicked_at).getTime()) <= THIRTY_DAYS_MS;
    fields.facebook_ad_clicked = fbValid ? 'true' : 'false';
    fields.facebook_ad_last_clicked_at = fbValid ? store.facebook_ad.last_clicked_at : '';

    return fields;
  }

  function populateForm(form, fields) {
    Object.keys(fields).forEach(function (name) {
      var field = form.querySelector('input[name="' + name + '"]');
      if (field && !field.value) {
        field.value = fields[name];
      }
    });
  }

  function populateAllForms() {
    var raw;
    try { raw = localStorage.getItem(STORAGE_KEY); } catch (e) { raw = null; }
    if (!raw) return;

    var store;
    try { store = JSON.parse(raw); } catch (e) { return; }

    var fields = buildFieldValues(store);
    var forms = document.querySelectorAll('form');
    for (var i = 0; i < forms.length; i++) {
      populateForm(forms[i], fields);
    }
  }

  // ---------------------------------------------------------------------
  // PART 3: WIRE IT UP
  // ---------------------------------------------------------------------

  // Run capture immediately - no DOM dependency.
  captureAttribution();

  function init() {
    // Initial population pass for forms already in the DOM.
    populateAllForms();

    // Safety net #1: re-populate right before any form submits, in case a
    // form was added to the page after this script ran its initial pass,
    // or its fields were cleared/reset by other page scripts.
    document.addEventListener('submit', function () {
      populateAllForms();
    }, true); // capture phase, so this runs before the form's own submit handlers

    // Safety net #2: watch for forms added to the page later (AJAX-loaded
    // contact forms, page-builder widgets that render after initial load,
    // multi-step forms, etc.) and populate those too, without needing a
    // full page reload.
    if (window.MutationObserver) {
      var observer = new MutationObserver(function (mutations) {
        var shouldRepopulate = false;
        for (var i = 0; i < mutations.length; i++) {
          var added = mutations[i].addedNodes;
          for (var j = 0; j < added.length; j++) {
            var node = added[j];
            if (node.nodeType === 1 && (node.tagName === 'FORM' || node.querySelector && node.querySelector('form'))) {
              shouldRepopulate = true;
              break;
            }
          }
          if (shouldRepopulate) break;
        }
        if (shouldRepopulate) populateAllForms();
      });
      observer.observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    // DOM already ready (script injected late) - run immediately
    init();
  }
})();

console.log('Attribution Tracking Plugin Loaded');