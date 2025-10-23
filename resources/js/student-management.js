// Student management entry stub for Vite
import './bootstrap';

// Initialize page-specific enhancements
document.addEventListener('DOMContentLoaded', () => {
  document.documentElement.setAttribute('data-page-init', 'student-management');

  // Touch-friendly form improvements
  document.querySelectorAll('input, select, textarea, button').forEach(el => {
    el.style.minHeight = '44px';
    el.style.touchAction = 'manipulation';
  });
});
