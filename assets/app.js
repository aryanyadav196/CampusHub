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

        document.addEventListener("click", function (event) {
            if (window.innerWidth > 960) {
                return;
            }

            if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove("open");
            }
        });
    }

    window.setTimeout(function () {
        document.querySelectorAll(".toast-message").forEach(function (node) {
            node.classList.add("toast-hide");
        });
    }, 3200);

    document.querySelectorAll("[data-autosubmit]").forEach(function (node) {
        node.addEventListener("change", function () {
            node.form && node.form.submit();
        });
    });

    initChatbot();
});

function initChatbot() {
    var fab = document.querySelector("[data-chat-toggle]");
    var shell = document.querySelector("[data-chat-shell]");
    var close = document.querySelector("[data-chat-close]");
    var form = document.querySelector("[data-chat-form]");
    var messages = document.querySelector("[data-chat-messages]");

    if (!fab || !shell || !form || !messages) {
        return;
    }

    function setOpen(nextOpen) {
        if (nextOpen) {
            shell.hidden = false;
            shell.classList.add("open");
            var input = form.querySelector("input[name='message']");
            if (input) {
                input.focus();
            }
        } else {
            shell.classList.remove("open");
            window.setTimeout(function () {
                if (!shell.classList.contains("open")) {
                    shell.hidden = true;
                }
            }, 180);
        }
    }

    function appendMessage(content, sender) {
        var article = document.createElement("article");
        article.className = "chat-message " + (sender === "user" ? "is-user" : "is-bot");

        var bubble = document.createElement("div");
        bubble.className = "chat-bubble";
        bubble.textContent = content;
        article.appendChild(bubble);
        messages.appendChild(article);
        messages.scrollTop = messages.scrollHeight;
        return article;
    }

    fab.addEventListener("click", function () {
        setOpen(!shell.classList.contains("open"));
    });

    if (close) {
        close.addEventListener("click", function () {
            setOpen(false);
        });
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        var input = form.querySelector("input[name='message']");
        var endpoint = shell.getAttribute("data-chat-endpoint");
        var message = input ? input.value.trim() : "";

        if (!message || !endpoint) {
            return;
        }

        appendMessage(message, "user");
        input.value = "";
        var pending = appendMessage("Checking records...", "bot");

        fetch(endpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({ message: message })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                pending.remove();
                appendMessage(payload.message || "I could not generate a response right now.", "bot");
            })
            .catch(function () {
                pending.remove();
                appendMessage("The assistant is temporarily unavailable. Please try again.", "bot");
            });
    });
}
