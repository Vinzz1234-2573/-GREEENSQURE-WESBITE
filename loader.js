(function(){
  var KEY = 'gsLoaderShown';
  if (sessionStorage.getItem(KEY)) return;
  var ldr = document.getElementById('ldr');
  if (!ldr) { sessionStorage.setItem(KEY, '1'); return; }

  var revealed = false;
  var revealTimer = setTimeout(function(){
    revealed = true;
    ldr.classList.add('show');
  }, 350);

  function finish(){
    sessionStorage.setItem(KEY, '1');
    clearTimeout(revealTimer);
    if (!revealed) return;
    setTimeout(function(){
      ldr.classList.add('out');
      setTimeout(function(){ ldr.style.display = 'none'; }, 400);
    }, 400);
  }

  if (document.readyState === 'complete') finish();
  else window.addEventListener('load', finish);
})();
