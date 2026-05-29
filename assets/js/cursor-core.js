/**
 * cursor-core.js — Kiro Cursor v1.0.0
 *
 * ARCHITECTURE — JS + CSS separation:
 *   JS moves #kiro-cursor and #kiro-cursor-ring (OUTER wrappers) only.
 *   CSS animations run on .kiro-inner (INNER shells) inside each wrapper.
 *   These are different DOM elements — JS and CSS never conflict.
 *
 *   Using translate3d (not left/top) keeps movement GPU-composited and
 *   avoids layout triggers on every animation frame.
 */
(function () {
    'use strict';

    // ── Mobile guard ──────────────────────────────────────────────────────────
    if (
        typeof kiroCursorConfig !== 'undefined' &&
        /Mobi|Android|iPhone|iPad|iPod|Touch/i.test(navigator.userAgent)
    ) {
        document.body.style.cursor = 'auto';
        return;
    }

    // ── Config injected by PHP (kiro_cursor_inline_config) ────────────────────
    var config = (typeof kiroCursorConfig !== 'undefined')
        ? kiroCursorConfig
        : { style: 'dot-ring', animation: 'none' };

    // ── DOM references ────────────────────────────────────────────────────────
    var body   = document.body;
    var cursor = document.getElementById('kiro-cursor');      // outer wrapper
    var ring   = document.getElementById('kiro-cursor-ring'); // outer wrapper

    if (!cursor || !ring) return;

    // ── Activate ──────────────────────────────────────────────────────────────
    // data attributes scope CSS style + animation selectors
    body.setAttribute('data-kiro-style',     config.style);
    body.setAttribute('data-kiro-animation', config.animation);
    body.classList.add('kiro-active');

    // ── Position state ────────────────────────────────────────────────────────
    // Start at viewport centre — avoids a jump from 0,0 before first mousemove.
    var mouseX = window.innerWidth  / 2;
    var mouseY = window.innerHeight / 2;
    var ringX  = mouseX;
    var ringY  = mouseY;

    // Ghost style gets a slower, floatier ring lag coefficient.
    var LAG = (config.style === 'ghost') ? 0.06 : 0.13;

    // Magnetic snap state
    var magnetTarget = null;

    // ── Mouse tracking ────────────────────────────────────────────────────────
    document.addEventListener('mousemove', function (e) {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    // ── Move functions — write ONLY to outer wrappers ─────────────────────────
    function moveCursor(x, y) {
        cursor.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0)';
    }

    function moveRing(x, y) {
        ring.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0)';
    }

    // ── rAF tick ──────────────────────────────────────────────────────────────
    function tick() {

        // Magnetic preset: pull cursor toward hovered element centre.
        if (config.animation === 'magnetic' && magnetTarget) {
            var rect   = magnetTarget.getBoundingClientRect();
            var cx     = rect.left + rect.width  / 2;
            var cy     = rect.top  + rect.height / 2;
            var dx     = mouseX - cx;
            var dy     = mouseY - cy;
            var dist   = Math.sqrt(dx * dx + dy * dy);
            var radius = Math.max(rect.width, rect.height) * 0.9;

            if (dist < radius) {
                var pull = (1 - dist / radius) * 0.4;
                // Offset rendered position only — real mouseX/Y are not mutated.
                moveCursor(mouseX - dx * pull, mouseY - dy * pull);
            } else {
                moveCursor(mouseX, mouseY);
            }
        } else {
            moveCursor(mouseX, mouseY);
        }

        // Ring lags behind mouse with exponential smoothing (LAG coefficient).
        ringX += (mouseX - ringX) * LAG;
        ringY += (mouseY - ringY) * LAG;
        moveRing(ringX, ringY);

        requestAnimationFrame(tick);
    }

    requestAnimationFrame(tick);

    // ── Hover detection via event delegation ─────────────────────────────────
    // Delegated so dynamically injected elements (AJAX content) are covered.
    var HOVER_SEL = 'a, button, [role="button"], input[type="submit"], input[type="reset"], label, select, textarea, [tabindex]:not([tabindex="-1"]), .kiro-hoverable';

    document.addEventListener('mouseover', function (e) {
        if (!e.target || !e.target.matches) return;
        if (e.target.matches(HOVER_SEL)) {
            body.classList.add('kiro-hover');
            if (config.animation === 'magnetic') {
                magnetTarget = e.target;
            }
        }
    });

    document.addEventListener('mouseout', function (e) {
        if (!e.target || !e.target.matches) return;
        if (e.target.matches(HOVER_SEL)) {
            body.classList.remove('kiro-hover');
            magnetTarget = null;
        }
    });

    // ── Window visibility ─────────────────────────────────────────────────────
    document.addEventListener('mouseleave', function () {
        body.classList.add('kiro-hidden');
    });
    document.addEventListener('mouseenter', function () {
        body.classList.remove('kiro-hidden');
    });

    // ── Public namespace for the animations module ────────────────────────────
    window.KiroCursor = {
        config:    config,
        getMouseX: function () { return mouseX; },
        getMouseY: function () { return mouseY; },
        cursor:    cursor,
        ring:      ring,
    };

}());
