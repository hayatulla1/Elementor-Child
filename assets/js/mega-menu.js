document.addEventListener("DOMContentLoaded", function() {
    "use strict";

    var wrappers = document.querySelectorAll('.hkdev-mm-wrapper');

    wrappers.forEach(function(wrapper) {
        var btn = wrapper.querySelector(".hkdev-mm-toggle-btn");
        var menu = wrapper.querySelector(".hkdev-mm-content");

        if (btn && menu) {
            // Toggle on click (for mobile and desktop button)
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                menu.classList.toggle("show");
            });
        }
    });

    // Close when clicking outside
    document.addEventListener("click", function(e) {
        wrappers.forEach(function(wrapper) {
            var menu = wrapper.querySelector(".hkdev-mm-content");
            if (menu && !wrapper.contains(e.target)) {
                menu.classList.remove("show");
            }
        });
    });

});