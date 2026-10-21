/**
 * Interactive map module (template-parts/modules/interactive-map.php).
 *
 * Pan  = native scroll of the viewport (both axes, momentum for free).
 * Zoom = the canvas width (%) — pins are positioned in percentages so
 *        they follow the artwork.
 *
 * Inputs: zoom/reset/fullscreen buttons, wheel (zooms towards the cursor;
 * at minimum zoom it passes through to the page/Lenis), pinch, mouse
 * drag-to-pan, hover/tap on pins, Esc / outside click to close.
 */

const ZOOM_STEP = 1.5;
const ZOOM_RANGE = 4; // max zoom relative to the minimum (fit) zoom
const MOBILE_ZOOM = 2; // initial zoom on small screens, centred on the first pin
const mobileQuery = window.matchMedia('(max-width: 767px)');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const fullscreenEnabled = () => document.fullscreenEnabled || document.webkitFullscreenEnabled;
const fullscreenElement = () => document.fullscreenElement || document.webkitFullscreenElement;

/**
 * Wires up one map instance.
 *
 * @param {HTMLElement} map Element with [data-map].
 */
function initMap(map) {
  const viewport = map.querySelector('[data-map-viewport]');
  const canvas = map.querySelector('[data-map-canvas]');
  const image = canvas.querySelector('img');
  const card = map.querySelector('[data-map-card]');
  const cardBody = map.querySelector('[data-map-card-body]');
  const cardClose = map.querySelector('[data-map-card-close]');
  const zoomInBtn = map.querySelector('[data-map-zoom-in]');
  const zoomOutBtn = map.querySelector('[data-map-zoom-out]');
  const resetBtn = map.querySelector('[data-map-reset]');
  const fullscreenBtn = map.querySelector('[data-map-fullscreen]');
  const pins = [...map.querySelectorAll('[data-map-pin]')];

  // Artwork ratio from the width/height attributes (known before load).
  const imageWidth = parseFloat(image.getAttribute('width'));
  const imageHeight = parseFloat(image.getAttribute('height'));
  const imageRatio = imageWidth && imageHeight ? imageHeight / imageWidth : 1;

  let zoomMin = 1;
  let zoom = 1; // currently rendered zoom
  let targetZoom = 1; // where the zoom animation is heading
  const zoomOrigin = { x: 0, y: 0 };
  let zoomRaf = null;
  let sizesUpgraded = false;

  // ---------------------------------------------------------------------------
  // Zoom
  // ---------------------------------------------------------------------------

  const zoomMax = () => zoomMin * ZOOM_RANGE;
  const clampZoom = (value) => Math.min(zoomMax(), Math.max(zoomMin, value));

  // Minimum zoom = the artwork covers the whole frame (no empty space).
  function updateZoomMin() {
    zoomMin = Math.max(1, viewport.clientHeight / (viewport.clientWidth * imageRatio));
  }

  // Once zoomed, ask the browser for a larger srcset candidate.
  function upgradeImageSizes() {
    if (sizesUpgraded || zoom <= zoomMin) {
      return;
    }
    sizesUpgraded = true;
    image.sizes = `${Math.ceil(viewport.clientWidth * zoomMax())}px`;
  }

  /**
   * Renders a zoom level, keeping the content point under (originX,
   * originY) — viewport-relative coordinates — in place.
   */
  function applyZoom(next, originX, originY) {
    const ratioX = (viewport.scrollLeft + originX) / canvas.offsetWidth;
    const ratioY = (viewport.scrollTop + originY) / canvas.offsetHeight;

    zoom = next;
    canvas.style.width = `${zoom * 100}%`;

    viewport.scrollLeft = ratioX * canvas.offsetWidth - originX;
    viewport.scrollTop = ratioY * canvas.offsetHeight - originY;
    upgradeImageSizes();
  }

  function updateZoomButtons() {
    zoomInBtn.disabled = targetZoom >= zoomMax() - 0.001;
    zoomOutBtn.disabled = targetZoom <= zoomMin + 0.001;
  }

  // Exponential ease towards the target zoom.
  function stepZoomAnimation() {
    const diff = targetZoom - zoom;
    if (Math.abs(diff) < 0.003) {
      applyZoom(targetZoom, zoomOrigin.x, zoomOrigin.y);
      zoomRaf = null;
      return;
    }
    applyZoom(zoom + diff * 0.16, zoomOrigin.x, zoomOrigin.y);
    zoomRaf = window.requestAnimationFrame(stepZoomAnimation);
  }

  /** Animated zoom (buttons, wheel). */
  function zoomTo(next, originX = viewport.clientWidth / 2, originY = viewport.clientHeight / 2) {
    targetZoom = clampZoom(next);
    zoomOrigin.x = originX;
    zoomOrigin.y = originY;
    updateZoomButtons();

    if (reducedMotion.matches) {
      applyZoom(targetZoom, originX, originY);
      return;
    }
    if (!zoomRaf) {
      zoomRaf = window.requestAnimationFrame(stepZoomAnimation);
    }
  }

  /** Instant zoom (pinch — the fingers drive the motion). */
  function setZoomImmediate(next, originX, originY) {
    if (zoomRaf) {
      window.cancelAnimationFrame(zoomRaf);
      zoomRaf = null;
    }
    targetZoom = clampZoom(next);
    applyZoom(targetZoom, originX, originY);
    updateZoomButtons();
  }

  /**
   * Initial view: Figma shows the full width centred vertically; small
   * screens start zoomed in on the first pin (the Montela houses).
   */
  function setInitialView() {
    if (zoomRaf) {
      window.cancelAnimationFrame(zoomRaf);
      zoomRaf = null;
    }
    updateZoomMin();

    let focusX = 50;
    let focusY = 50;
    let initialZoom = zoomMin;

    if (mobileQuery.matches && pins.length) {
      focusX = parseFloat(pins[0].style.getPropertyValue('--x'));
      focusY = parseFloat(pins[0].style.getPropertyValue('--y'));
      initialZoom = clampZoom(zoomMin * MOBILE_ZOOM);
    }

    zoom = initialZoom;
    targetZoom = initialZoom;
    canvas.style.width = `${zoom * 100}%`;
    viewport.scrollLeft = (focusX / 100) * canvas.offsetWidth - viewport.clientWidth / 2;
    viewport.scrollTop = (focusY / 100) * canvas.offsetHeight - viewport.clientHeight / 2;
    updateZoomButtons();
    upgradeImageSizes();
  }

  zoomInBtn.addEventListener('click', () => zoomTo(targetZoom * ZOOM_STEP));
  zoomOutBtn.addEventListener('click', () => zoomTo(targetZoom / ZOOM_STEP));
  resetBtn.addEventListener('click', () => {
    closeCard();
    setInitialView();
  });

  /*
   * Wheel = zoom towards the cursor. Only captured when the zoom actually
   * changes, so at minimum zoom the page keeps scrolling. stopPropagation
   * keeps Lenis (listening on window) from scrolling the page meanwhile.
   */
  viewport.addEventListener('wheel', (event) => {
    const factor = event.deltaY < 0 ? 1.2 : 1 / 1.2;
    const next = clampZoom(targetZoom * factor);
    if (Math.abs(next - targetZoom) < 0.001) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    const rect = viewport.getBoundingClientRect();
    zoomTo(next, event.clientX - rect.left, event.clientY - rect.top);
  }, { passive: false });

  // ---------------------------------------------------------------------------
  // Pinch zoom + mouse drag-to-pan (Pointer Events)
  // ---------------------------------------------------------------------------

  const activePointers = new Map();
  let pinchStartDistance = 0;
  let pinchStartZoom = 1;
  let dragStart = null;

  const pinchDistance = () => {
    const [a, b] = [...activePointers.values()];
    return Math.hypot(a.x - b.x, a.y - b.y);
  };

  viewport.addEventListener('pointerdown', (event) => {
    activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

    if (event.pointerType === 'touch' && activePointers.size === 2) {
      pinchStartDistance = pinchDistance();
      pinchStartZoom = zoom;
    }

    // Mouse drag-to-pan (touch panning is native scroll).
    if (event.pointerType === 'mouse' && event.button === 0 && !event.target.closest('button, a')) {
      event.preventDefault();
      viewport.setPointerCapture(event.pointerId);
      dragStart = {
        x: event.clientX,
        y: event.clientY,
        scrollLeft: viewport.scrollLeft,
        scrollTop: viewport.scrollTop,
      };
      viewport.classList.add('is-dragging');
    }
  });

  viewport.addEventListener('pointermove', (event) => {
    if (!activePointers.has(event.pointerId)) {
      return;
    }
    activePointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

    if (event.pointerType === 'touch' && activePointers.size === 2 && pinchStartDistance > 0) {
      const rect = viewport.getBoundingClientRect();
      const [a, b] = [...activePointers.values()];
      setZoomImmediate(
        pinchStartZoom * (pinchDistance() / pinchStartDistance),
        (a.x + b.x) / 2 - rect.left,
        (a.y + b.y) / 2 - rect.top,
      );
    }

    if (dragStart && event.pointerType === 'mouse') {
      viewport.scrollLeft = dragStart.scrollLeft - (event.clientX - dragStart.x);
      viewport.scrollTop = dragStart.scrollTop - (event.clientY - dragStart.y);
    }
  });

  const releasePointer = (event) => {
    activePointers.delete(event.pointerId);
    if (activePointers.size < 2) {
      pinchStartDistance = 0;
    }
    if (event.pointerType === 'mouse') {
      dragStart = null;
      viewport.classList.remove('is-dragging');
    }
  };

  ['pointerup', 'pointercancel', 'pointerleave'].forEach((type) => {
    viewport.addEventListener(type, releasePointer);
  });

  // ---------------------------------------------------------------------------
  // Detail popup
  // ---------------------------------------------------------------------------

  let activePin = null;
  let hoverCloseTimer = null;

  /**
   * Places the popup beside the pin, inside the map bounds (flips to the
   * left when there is no room). On mobile it is a CSS bottom sheet.
   * Closes it when the pin has been panned out of view.
   */
  function positionCard() {
    if (!activePin) {
      return;
    }
    if (mobileQuery.matches) {
      card.style.transform = '';
      return;
    }

    const mapRect = map.getBoundingClientRect();
    const viewRect = viewport.getBoundingClientRect();
    const pinRect = activePin.getBoundingClientRect();
    const pinX = pinRect.left + pinRect.width / 2;
    const pinY = pinRect.top + pinRect.height / 2;

    if (pinX < viewRect.left || pinX > viewRect.right || pinY < viewRect.top || pinY > viewRect.bottom) {
      closeCard();
      return;
    }

    const margin = 8;
    const gap = pinRect.width / 2 + 12;
    const cardWidth = card.offsetWidth;
    const cardHeight = card.offsetHeight;
    const x = pinX - mapRect.left;
    const y = pinY - mapRect.top;

    let left = x + gap;
    if (left + cardWidth > mapRect.width - margin) {
      left = x - gap - cardWidth;
    }
    left = Math.max(margin, left);

    const top = Math.min(Math.max(margin, y - cardHeight / 2), mapRect.height - cardHeight - margin);

    card.style.transform = `translate(${Math.round(left)}px, ${Math.round(Math.max(margin, top))}px)`;
  }

  function openCard(pin) {
    const content = document.getElementById(pin.dataset.content);
    if (!content) {
      return;
    }

    if (activePin && activePin !== pin) {
      activePin.classList.remove('is-active');
      activePin.setAttribute('aria-expanded', 'false');
    }

    activePin = pin;
    pin.classList.add('is-active');
    pin.setAttribute('aria-expanded', 'true');

    cardBody.innerHTML = content.innerHTML;
    card.hidden = false;
    positionCard();
  }

  function closeCard() {
    if (!activePin) {
      return;
    }
    activePin.classList.remove('is-active');
    activePin.setAttribute('aria-expanded', 'false');
    activePin = null;
    card.hidden = true;
  }

  pins.forEach((pin, index) => {
    pin.style.setProperty('--pin-index', String(index));

    // Click / tap / keyboard toggles.
    pin.addEventListener('click', () => {
      if (activePin === pin) {
        closeCard();
      } else {
        openCard(pin);
      }
    });

    // Hover opens on mouse pointers only.
    pin.addEventListener('pointerenter', (event) => {
      if (event.pointerType !== 'mouse' || dragStart) {
        return;
      }
      window.clearTimeout(hoverCloseTimer);
      openCard(pin);
    });

    pin.addEventListener('pointerleave', (event) => {
      if (event.pointerType !== 'mouse') {
        return;
      }
      hoverCloseTimer = window.setTimeout(closeCard, 300);
    });
  });

  // Keep the popup open while the mouse is over it.
  card.addEventListener('pointerenter', () => window.clearTimeout(hoverCloseTimer));
  card.addEventListener('pointerleave', (event) => {
    if (event.pointerType === 'mouse') {
      hoverCloseTimer = window.setTimeout(closeCard, 300);
    }
  });

  cardClose.addEventListener('click', () => {
    const pinToRefocus = activePin;
    closeCard();
    pinToRefocus?.focus();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && activePin && !fullscreenElement()) {
      const pinToRefocus = activePin;
      closeCard();
      pinToRefocus.focus();
    }
  });

  // Click outside the map closes the popup.
  document.addEventListener('click', (event) => {
    if (activePin && !map.contains(event.target)) {
      closeCard();
    }
  });

  // Follow the pin while panning/zooming.
  let positionRaf = null;
  viewport.addEventListener('scroll', () => {
    if (!activePin || positionRaf) {
      return;
    }
    positionRaf = window.requestAnimationFrame(() => {
      positionRaf = null;
      positionCard();
    });
  }, { passive: true });

  // ---------------------------------------------------------------------------
  // Fullscreen
  // ---------------------------------------------------------------------------

  if (fullscreenBtn && fullscreenEnabled()) {
    fullscreenBtn.hidden = false;

    fullscreenBtn.addEventListener('click', () => {
      if (fullscreenElement() === map) {
        (document.exitFullscreen || document.webkitExitFullscreen).call(document);
      } else {
        (map.requestFullscreen || map.webkitRequestFullscreen).call(map);
      }
    });

    const onFullscreenChange = () => {
      fullscreenBtn.setAttribute('aria-pressed', String(fullscreenElement() === map));
      closeCard();
      // Wait for the new frame size before recomputing the view.
      window.requestAnimationFrame(setInitialView);
    };

    document.addEventListener('fullscreenchange', onFullscreenChange);
    document.addEventListener('webkitfullscreenchange', onFullscreenChange);
  }

  // ---------------------------------------------------------------------------
  // Resize + entry animation
  // ---------------------------------------------------------------------------

  let lastWidth = viewport.clientWidth;
  new ResizeObserver(() => {
    // Ignore height-only changes (mobile toolbars) to keep the user's view.
    if (viewport.clientWidth === lastWidth) {
      return;
    }
    lastWidth = viewport.clientWidth;
    closeCard();
    setInitialView();
  }).observe(viewport);

  if (reducedMotion.matches || !('IntersectionObserver' in window)) {
    map.classList.add('is-revealed');
  } else {
    const observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        map.classList.add('is-revealed');
        observer.disconnect();
      }
    }, { threshold: 0, rootMargin: '0px 0px -15% 0px' });
    observer.observe(map);
  }

  setInitialView();
}

/**
 * Initialises every interactive map on the page.
 */
export function initInteractiveMaps() {
  document.querySelectorAll('[data-map]').forEach(initMap);
}
