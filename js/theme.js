/**
 * WordWise Theme & Editorial UI Interactions
 * Manages light/dark/system themes without flash of unstyled content,
 * typewriter effects, and reading progress indicators.
 */

(function () {
  'use strict';

  // Apply theme immediately to document root
  function applyTheme(theme) {
    const root = document.documentElement;
    const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    
    if (isDark) {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
  }

  // Get current stored or default theme
  const currentTheme = localStorage.getItem('wordwise_theme') || 'system';
  applyTheme(currentTheme);

  // Listen to OS scheme changes when in 'system' mode
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (localStorage.getItem('wordwise_theme') === 'system' || !localStorage.getItem('wordwise_theme')) {
      applyTheme('system');
    }
  });

  // Global helper on window
  window.WordWiseTheme = {
    set: function (theme) {
      if (!['light', 'dark', 'system'].includes(theme)) return;
      localStorage.setItem('wordwise_theme', theme);
      applyTheme(theme);
      this.updateUI(theme);
    },
    get: function () {
      return localStorage.getItem('wordwise_theme') || 'system';
    },
    cycle: function () {
      const current = this.get();
      const next = current === 'light' ? 'dark' : (current === 'dark' ? 'system' : 'light');
      this.set(next);
      return next;
    },
    updateUI: function (theme) {
      document.querySelectorAll('[data-theme-indicator]').forEach(el => {
        el.textContent = theme.toUpperCase();
      });
      document.querySelectorAll('[data-theme-icon]').forEach(el => {
        el.setAttribute('data-current-theme', theme);
      });
    }
  };

  // Typewriter text reveal effect
  window.initTypewriter = function (elementId, text, speed = 40) {
    const el = document.getElementById(elementId);
    if (!el) return;

    // Respect reduced motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      el.textContent = text;
      return;
    }

    el.textContent = '';
    let i = 0;
    function type() {
      if (i < text.length) {
        el.textContent += text.charAt(i);
        i++;
        setTimeout(type, speed);
      }
    }
    type();
  };

  // Reading progress bar updater
  window.initReadingProgress = function () {
    const progressBar = document.getElementById('reading-progress');
    if (!progressBar) return;

    window.addEventListener('scroll', () => {
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      if (docHeight <= 0) return;
      const scrollPercent = (window.scrollY / docHeight) * 100;
      progressBar.style.width = Math.min(100, Math.max(0, scrollPercent)) + '%';
    }, { passive: true });
  };

  // Initialize UI indicators once DOM is ready
  document.addEventListener('DOMContentLoaded', () => {
    window.WordWiseTheme.updateUI(window.WordWiseTheme.get());
    window.initReadingProgress();
  });
})();
