document.addEventListener("DOMContentLoaded", function () {
    var root = document.documentElement;
    var storedTheme = localStorage.getItem("campushub-theme");
    if (storedTheme === "dark") {
        root.setAttribute("data-theme", "dark");
    }

    var toggle = document.querySelector("[data-theme-toggle]");
    if (toggle) {
        toggle.addEventListener("click", function () {
            var nextTheme = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
            if (nextTheme === "dark") {
                root.setAttribute("data-theme", "dark");
            } else {
                root.removeAttribute("data-theme");
            }
            localStorage.setItem("campushub-theme", nextTheme);
        });
    }

    var sidebar = document.querySelector("[data-sidebar]");
    var sidebarToggle = document.querySelector("[data-sidebar-toggle]");
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener("click", function () {
            sidebar.classList.toggle("open");
        });
    }

    window.setTimeout(function () {
        document.querySelectorAll(".toast-message").forEach(function (node) {
            node.classList.add("toast-hide");
        });
    }, 3200);
});
