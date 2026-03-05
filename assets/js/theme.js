/**
 * Theme Toggle Module
 * Gerencia a alternância entre tema claro e escuro
 */

document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("toggle_theme");
    const themeIcon = document.getElementById("theme-icon");
    const htmlElement = document.documentElement;

    // Verifica se já há um tema salvo no localStorage
    let currentTheme = localStorage.getItem("theme") || "light";
    htmlElement.setAttribute("data-bs-theme", currentTheme);
    themeIcon.className = currentTheme === "dark" ? "bi bi-sun" : "bi bi-moon";

    if (themeToggle) {
        themeToggle.addEventListener("click", function () {
            let newTheme = htmlElement.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
            htmlElement.setAttribute("data-bs-theme", newTheme);
            themeIcon.className = newTheme === "dark" ? "bi bi-sun" : "bi bi-moon";
            localStorage.setItem("theme", newTheme);

            // Se houver URL de toggle definida, salva no banco
            if (window.ROUTES && window.ROUTES.toggleTheme) {
                fetch(window.ROUTES.toggleTheme, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error("Erro ao alternar tema:", data.error);
                    }
                })
                .catch(error => console.error("Erro na requisição:", error));
            }
        });
    }
});