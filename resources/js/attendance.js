// Attendance entry stub for Vite
import './bootstrap';

// Basic initialization for attendance pages
document.addEventListener('DOMContentLoaded', () => {
  document.documentElement.setAttribute('data-page-init', 'attendance');

  // Hint for horizontal scroll on wide tables
  document.querySelectorAll('table').forEach(table => {
    const wrap = document.createElement('div');
    wrap.className = 'table-responsive';
    table.parentNode.insertBefore(wrap, table);
    wrap.appendChild(table);
  });
});
