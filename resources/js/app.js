import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap; // 🔥 kritik
import '../sass/app.scss';

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });
});
