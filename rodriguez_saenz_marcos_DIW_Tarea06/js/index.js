document.addEventListener("DOMContentLoaded", () => {
  const btnFiltros = document.getElementById("btnFiltros");
  const overlay = document.getElementById("filtersOverlay");
  const cerrarBtn = document.getElementById("cerrarFiltros");

  if (btnFiltros && overlay && cerrarBtn) {
    const abrir = () => overlay.classList.add("open");
    const cerrar = () => overlay.classList.remove("open");

    btnFiltros.addEventListener("click", abrir);
    cerrarBtn.addEventListener("click", cerrar);

    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) cerrar();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") cerrar();
    });
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (c) => {
      switch (c) {
        case "&":
          return "&amp;";
        case "<":
          return "&lt;";
        case ">":
          return "&gt;";
        case '"':
          return "&quot;";
        case "'":
          return "&#039;";
        default:
          return c;
      }
    });
  }

  async function sendReaction(routeId, action, likeBtn, dislikeBtn) {
    if (!routeId) return;

    const formData = new FormData();
    formData.append("route_id", routeId);
    formData.append("action", action);

    try {
      const res = await fetch("./php/route_reaction.php", {
        method: "POST",
        body: formData,
      });

      if (!res.ok) {
        if (res.status === 401) {
          window.location.href = "login.php";
          return;
        }
        throw new Error("Error HTTP en reacción");
      }

      const data = await res.json();
      if (!data.ok) {
        if (data.error === "NO_AUTH") {
          window.location.href = "login.php";
          return;
        }
        console.error("Error reacción:", data.error);
        return;
      }

      const status = data.status; // 'like' | 'dislike' | 'none'

      if (likeBtn) {
        likeBtn.classList.toggle("route-action-btn--active", status === "like");
      }
      if (dislikeBtn) {
        dislikeBtn.classList.toggle(
          "route-action-btn--active",
          status === "dislike"
        );
      }
    } catch (err) {
      console.error(err);
    }
  }

  const routeCards = document.querySelectorAll(".route-card");

  routeCards.forEach((card) => {
    const routeId = card.dataset.routeId;
    let commentsPanel = card.nextElementSibling;
    if (
      !commentsPanel ||
      !commentsPanel.classList.contains("route-comments-panel")
    ) {
      commentsPanel = null;
    }

    const commentsBtn = card.querySelector(".route-action-btn-comments");
    const likeBtn = card.querySelector(".route-action-btn-like");
    const dislikeBtn = card.querySelector(".route-action-btn-dislike");

    // Abrir/cerrar panel de comentarios
    if (commentsBtn && commentsPanel) {
      commentsBtn.addEventListener("click", () => {
        commentsPanel.classList.toggle("is-open");
      });
    }

    // Like
    if (likeBtn) {
      likeBtn.addEventListener("click", () => {
        sendReaction(routeId, "like", likeBtn, dislikeBtn);
      });
    }

    // Dislike
    if (dislikeBtn) {
      dislikeBtn.addEventListener("click", () => {
        sendReaction(routeId, "dislike", likeBtn, dislikeBtn);
      });
    }
  });

  const commentForms = document.querySelectorAll("form.comment-form");

  commentForms.forEach((form) => {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const routeId =
        form.dataset.routeId ||
        form.querySelector('input[name="route_id"]')?.value;
      const textarea = form.querySelector('textarea[name="content"]');
      if (!textarea) return;

      const content = textarea.value.trim();
      if (!content) return;

      const formData = new FormData(form);
      try {
        const res = await fetch(form.action, {
          method: "POST",
          body: formData,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
        });

        if (!res.ok) {
          if (res.status === 401) {
            window.location.href = "login.php";
            return;
          }
          throw new Error("Error HTTP al enviar comentario");
        }

        const data = await res.json();
        if (!data.ok) {
          console.error("Error comentario:", data.error);
          alert("No se ha podido guardar el comentario.");
          return;
        }

        const panel = form.closest(".route-comments-panel");
        const list = panel?.querySelector(".route-comments-list");
        const countSpan = panel?.querySelector(".route-comments-count");
        if (!list) return;

        // Quitar mensaje "no hay comentarios" si existe
        const emptyMsg = list.querySelector(".no-comments-msg");
        if (emptyMsg) emptyMsg.remove();

        const c = data.comment;
        const commentEl = document.createElement("div");
        commentEl.className = "route-comment";
        commentEl.innerHTML = `
          <div class="route-comment-avatar">
            ${escapeHtml(c.initial)}
          </div>
          <div class="route-comment-body">
            <div class="route-comment-meta">
              <span class="route-comment-author">
                ${escapeHtml(c.author_name)}
              </span>
              <span class="route-comment-date">
                ${escapeHtml(c.created_at)}
              </span>
            </div>
            <p class="route-comment-text">
              ${escapeHtml(c.content)}
            </p>
          </div>
        `;

        // Insertar al principio
        list.prepend(commentEl);

        // Actualizar contador
        if (countSpan) {
          const current = parseInt(countSpan.textContent || "0", 10) || 0;
          countSpan.textContent = current + 1;
        }

        // Limpiar textarea
        textarea.value = "";
      } catch (err) {
        console.error(err);
        alert("Ha ocurrido un error al enviar el comentario.");
      }
    });

    // Botón Cancelar: cierra panel y limpia textarea
    const cancelBtn = form.querySelector(".js-comment-cancel");
    if (cancelBtn) {
      cancelBtn.addEventListener("click", () => {
        const panel = form.closest(".route-comments-panel");
        const textarea = form.querySelector('textarea[name="content"]');
        if (textarea) textarea.value = "";
        if (panel) panel.classList.remove("is-open");
      });
    }
  });
});
document.addEventListener("DOMContentLoaded", () => {
  const btnLimpiar = document.getElementById("btnLimpiarFiltros");
  if (btnLimpiar) {
    btnLimpiar.addEventListener("click", () => {
      // Recarga index.php sin query string (borra búsqueda, filtros y página)
      window.location.href = "index.php";
    });
  }
});
