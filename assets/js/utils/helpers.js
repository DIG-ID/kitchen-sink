/**
 * General-purpose utility functions.
 */

/**
 * Debounce — delays a function call until after a given wait time.
 * Useful for resize/scroll listeners.
 *
 * @param {Function} fn   Function to debounce.
 * @param {number}   wait Wait time in milliseconds.
 * @returns {Function}
 */
export function debounce(fn, wait = 200) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), wait);
  };
}

/**
 * Returns true if the current device is touch-based.
 *
 * @returns {boolean}
 */
export function isTouchDevice() {
  return window.matchMedia('(hover: none)').matches;
}
