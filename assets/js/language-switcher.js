document.addEventListener('DOMContentLoaded', function() {
    "use strict";
    
    var langBtn = document.getElementById('hkdev-lang-switcher-trigger');
    if (langBtn) {
        langBtn.addEventListener('click', function() {
            var targetLang = this.getAttribute('data-target-lang');
            // Set cookie for 30 days
            document.cookie = "hkdev_lang=" + targetLang + "; path=/; max-age=" + (30 * 24 * 60 * 60);
            window.location.reload(true);
        });
    }
});