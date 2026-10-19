/* Accessible, progressive-enhancement mobile menu. */
(function () {
  'use strict';
  var toggle = document.querySelector('.adim-menu-toggle');
  var navigation = document.getElementById('adim-primary-navigation');
  if (!toggle || !navigation) return;

  document.body.classList.add('adim-js');
  toggle.addEventListener('click', function () {
    var open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    navigation.classList.toggle('is-open', open);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      toggle.setAttribute('aria-expanded', 'false');
      navigation.classList.remove('is-open');
      toggle.focus();
    }
  });
})();
