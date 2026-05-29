/**
 * cursor-animations.js — Kiro Cursor v1.0.0
 *
 * Handles JS-driven animation modules: particles and magnetic spring.
 * CSS-only animations (pulse, breathe, spin-ring) require no JS — they
 * activate via body[data-kiro-animation] targeting .kiro-inner in CSS.
 *
 * Depends on: cursor-core.js (must run first — window.KiroCursor must exist).
 */
(function () {
    'use strict';

    // Guard: core must be initialised before this module runs.
    if (typeof window.KiroCursor === 'undefined') return;

    var KC     = window.KiroCursor;
    var config = KC.config;

    if (config.animation === 'particles') {
        initParticles();
    } else if (config.animation === 'magnetic') {
        initMagneticSpring();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PARTICLES
    // Spawns small colour-matched dots along the cursor trail.
    // Each particle is a fixed-position <span> that self-removes after its
    // CSS animation (kiro-particle-fade) ends. Pool cap prevents DOM bloat.
    // ═══════════════════════════════════════════════════════════════════════════
    function initParticles() {

        // Read the CSS variable colour so particles always match the cursor.
        var color = getComputedStyle(document.documentElement)
            .getPropertyValue('--kiro-color').trim() || '#00ffff';

        var lastX       = KC.getMouseX();
        var lastY       = KC.getMouseY();
        var SPAWN_DIST  = 7;   // px of travel before spawning a new particle
        var MAX_ACTIVE  = 40;  // hard pool cap — prevents unbounded DOM growth
        var activeCount = 0;

        document.addEventListener('mousemove', function (e) {
            var dx = e.clientX - lastX;
            var dy = e.clientY - lastY;

            // Only spawn after cursor has moved SPAWN_DIST px to avoid
            // flooding the DOM when the mouse is stationary.
            if (Math.sqrt(dx * dx + dy * dy) < SPAWN_DIST) return;
            if (activeCount >= MAX_ACTIVE) return;

            lastX = e.clientX;
            lastY = e.clientY;

            // Spawn 1–2 particles per step for a richer trail.
            spawnParticle(e.clientX, e.clientY, color);
            if (Math.random() > 0.5) {
                spawnParticle(e.clientX, e.clientY, color);
            }
        });

        function spawnParticle(x, y, color) {
            var el = document.createElement('span');
            el.className = 'kiro-particle';

            var size    = 2 + Math.random() * 5;         // 2–7 px
            var offsetX = (Math.random() - 0.5) * 14;   // ±7 px scatter
            var offsetY = (Math.random() - 0.5) * 14;

            // fixed positioning uses viewport coords (clientX/Y directly).
            el.style.cssText =
                'width:'       + size + 'px;' +
                'height:'      + size + 'px;' +
                'left:'        + (x + offsetX) + 'px;' +
                'top:'         + (y + offsetY) + 'px;' +
                'background:'  + color + ';';

            document.body.appendChild(el);
            activeCount++;

            el.addEventListener('animationend', function () {
                if (el.parentNode) el.parentNode.removeChild(el);
                activeCount = Math.max(0, activeCount - 1);
            }, { once: true });
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MAGNETIC SPRING
    // Pull physics live in cursor-core.js's rAF loop.
    // This module adds the spring-overshoot CSS transition on the INNER shell
    // so the cursor visually snaps toward the element then wobbles back.
    // ═══════════════════════════════════════════════════════════════════════════
    function initMagneticSpring() {

        var inner     = KC.cursor.querySelector('.kiro-inner');
        var ringInner = KC.ring.querySelector('.kiro-inner');
        var HOVER_SEL = 'a, button, [role="button"], input[type="submit"], label';

        // cubic-bezier overshoot at 1.56 creates the spring bounce.
        var SPRING_EASE = 'transform 0.28s cubic-bezier(0.34,1.56,0.64,1)';
        var BASE_EASE   = 'transform 0.18s cubic-bezier(0.34,1.56,0.64,1), opacity 0.15s ease';

        document.addEventListener('mouseover', function (e) {
            if (!e.target || !e.target.matches) return;
            if (!e.target.matches(HOVER_SEL))   return;
            if (inner)     inner.style.transition     = SPRING_EASE;
            if (ringInner) ringInner.style.transition = SPRING_EASE;
        });

        document.addEventListener('mouseout', function (e) {
            if (!e.target || !e.target.matches) return;
            if (!e.target.matches(HOVER_SEL))   return;
            if (inner)     inner.style.transition     = BASE_EASE;
            if (ringInner) ringInner.style.transition = BASE_EASE;
        });
    }

}());
