import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger);

// Expo-out easing for a cinematic, filmic stop
const easeExpoOut = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));

/**
 * Initialise Lenis with a cinematic glide feel.
 * Tweak duration and wheelMultiplier per project to taste.
 *
 * @returns {Lenis}
 */
function initLenis() {
  const lenis = new Lenis({
    duration: 1.35,          // adjust per project — higher = longer glide
    easing: easeExpoOut,
    smooth: true,
    smoothWheel: true,
    wheelMultiplier: 0.85,   // lower = softer on high-res/trackpad devices
  });

  // Keep ScrollTrigger in sync with Lenis scroll position
  lenis.on('scroll', ScrollTrigger.update);

  // Drive Lenis with GSAP ticker — single RAF source
  gsap.ticker.add((time) => {
    lenis.raf(time * 1000);
  });

  gsap.ticker.lagSmoothing(0);

  return lenis;
}

const lenis = initLenis();

export { gsap, ScrollTrigger, lenis };
