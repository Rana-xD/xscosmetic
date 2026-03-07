<script>
   (function() {
      var storageKey = 'xcosmetic-theme';
      var theme = 'light';

      try {
         var savedTheme = window.localStorage ? window.localStorage.getItem(storageKey) : null;
         if (savedTheme === 'light' || savedTheme === 'dark') {
            theme = savedTheme;
         }
      } catch (error) {
         theme = 'light';
      }

      window.__APP_THEME__ = theme;

      var root = document.documentElement;
      root.setAttribute('data-theme', theme);
      root.className = (root.className || '')
         .replace(/\btheme-(light|dark)\b/g, '')
         .replace(/^\s+|\s+$/g, '');
      root.className = (root.className ? root.className + ' ' : '') + 'theme-' + theme;
   })();
</script>
