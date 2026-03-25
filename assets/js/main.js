(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    const wrappers = document.querySelectorAll(".fqe-wrapper");

    wrappers.forEach(function (wrapper) {
      const computed = window.getComputedStyle(wrapper);
      if (computed.position === "static") {
        wrapper.style.position = "relative";
      }
    });
  });
})();
