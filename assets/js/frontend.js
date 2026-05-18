/**
 * Optional client-side odds format toggle per block instance.
 */
(function () {
  'use strict';

  if (typeof wpocFrontend === 'undefined') {
    return;
  }

  document.querySelectorAll('.wpoc-odds-comparison').forEach(function (root) {
    var format = root.getAttribute('data-odds-format') || 'decimal';
    root.setAttribute('data-loaded-format', format);
  });
})();
