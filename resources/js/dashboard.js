// Dashboard entry stub for Vite
import './bootstrap';

// Initialize common UI behaviors safely
document.addEventListener('DOMContentLoaded', () => {
  // Enable Bootstrap tooltips
  const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.forEach(el => {
    try { new window.bootstrap.Tooltip(el); } catch (e) { /* noop */ }
  });

  // Mark page as initialized
  document.documentElement.setAttribute('data-page-init', 'dashboard');
});
