document.addEventListener("DOMContentLoaded", () => {
  const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
  const theme = prefersDark ? "dark" : "light";

  // Mandamos el tema detectado al servidor sólo si hay sesión
  fetch("./php/set-theme.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "theme=" + encodeURIComponent(theme),
    credentials: "same-origin",
  }).catch(() => {
    // si falla no pasa nada, simplemente no se actualizará
  });
});
