/* Kiro Cursor - cursor.js */

(function () {

    // FIX #3: Use correct localized variable name 'kiroCursorConfig' (not SCCP_DATA)
    // FIX #8: disableMobile is now properly passed from PHP
    if (
        typeof kiroCursorConfig !== 'undefined' &&
        kiroCursorConfig.disableMobile == 1 &&
        /Mobi|Android|iPhone|iPad|Touch/i.test(navigator.userAgent)
    ) {
        document.body.style.cursor = 'auto';
        return;
    }

    // FIX #4: IDs now match the injected HTML (#kiro-cursor, #kiro-cursor-ring)
    var cursor = document.getElementById('kiro-cursor');
    var ring   = document.getElementById('kiro-cursor-ring');

    if (!cursor || !ring) return; // Safety guard

    var mouseX = 0, mouseY = 0;
    var ringX  = 0, ringY  = 0;

    // Track mouse position and move dot cursor instantly
    document.addEventListener('mousemove', function (e) {
        mouseX = e.clientX;
        mouseY = e.clientY;
        cursor.style.left = mouseX + 'px';
        cursor.style.top  = mouseY + 'px';
    });

    // Smooth lagging ring animation via requestAnimationFrame
    function animateRing() {
        ringX += (mouseX - ringX) * 0.15;
        ringY += (mouseY - ringY) * 0.15;
        ring.style.left = ringX + 'px';
        ring.style.top  = ringY + 'px';
        requestAnimationFrame(animateRing);
    }
    animateRing();

    // FIX: Use correct CSS class 'kiro-hover' (matches cursor.css)
    document.querySelectorAll('a, button, [role="button"], input[type="submit"], label').forEach(function (el) {
        el.addEventListener('mouseenter', function () {
            document.body.classList.add('kiro-hover');
        });
        el.addEventListener('mouseleave', function () {
            document.body.classList.remove('kiro-hover');
        });
    });

    // Hide cursor when mouse leaves the window
    document.addEventListener('mouseleave', function () {
        cursor.style.opacity = '0';
        ring.style.opacity   = '0';
    });

    document.addEventListener('mouseenter', function () {
        cursor.style.opacity = '1';
        ring.style.opacity   = '0.6';
    });

})();
