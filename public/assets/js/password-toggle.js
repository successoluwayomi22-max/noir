(function(){
  function init(){
    document.querySelectorAll('input[type="password"]').forEach(function(input){
      // Several templates already render their own eye button. Do not wrap
      // those inputs again or a second control will overlap the first one.
      if(input.dataset.passwordToggleReady === '1' ||
         input.closest('.password-input-wrapper,.password-visibility-wrap') ||
         input.parentElement?.querySelector('.password-toggle,.password-toggle-btn,.password-visibility-toggle')) return;
      var wrap=document.createElement('span');wrap.className='password-visibility-wrap';input.parentNode.insertBefore(wrap,input);wrap.appendChild(input);input.dataset.passwordToggleReady='1';
      var button=document.createElement('button');button.type='button';button.className='password-visibility-toggle';button.setAttribute('aria-label','Show password');
      button.innerHTML='<svg class="password-eye-open" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.8-7 10-7 10 7 10 7-3.8 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.8"></circle></svg><svg class="password-eye-closed" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="m3 3 18 18"></path><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.2 0 10 7 10 7a17.4 17.4 0 0 1-3.1 3.8"></path><path d="M6.2 6.2C3.6 8.2 2 12 2 12s3.8 7 10 7a9.8 9.8 0 0 0 3.2-.5"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>';
      button.addEventListener('click',function(){var visible=input.type==='text';input.type=visible?'password':'text';button.classList.toggle('is-visible',!visible);button.querySelector('.password-eye-open').style.display=visible?'':'none';button.querySelector('.password-eye-closed').style.display=visible?'none':'';button.setAttribute('aria-label',visible?'Show password':'Hide password')});wrap.appendChild(button);
    });
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init); else init();
})();
