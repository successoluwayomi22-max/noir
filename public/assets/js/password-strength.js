(function () {
  function init() {
    document.querySelectorAll('input[data-password-strength]').forEach(function (input) {
      if (input.dataset.strengthReady === '1') return;
      input.dataset.strengthReady = '1';
      var meter = document.createElement('div');
      meter.className = 'password-strength-meter';
      meter.innerHTML = '<div class="password-strength-bar"><i></i></div><small></small>';
      var host = input.closest('.password-input-wrapper,.password-visibility-wrap') || input;
      host.parentNode.insertBefore(meter, host.nextSibling);
      var bar = meter.querySelector('i'), label = meter.querySelector('small');
      input.addEventListener('input', function () {
        var value = input.value, score = 0;
        if (value.length >= 8) score++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;
        var names = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        bar.style.width = (score * 25) + '%';
        meter.dataset.level = score ? names[score].toLowerCase() : '';
        label.textContent = score === 4 ? 'Strong password' : (score ? names[score] + ' password — use upper/lowercase, a number, and a symbol.' : 'Use 8+ characters with upper/lowercase, a number, and a symbol.');
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
}());
