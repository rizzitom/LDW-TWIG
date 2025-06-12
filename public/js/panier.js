/**

 * Gestion du panier côté client

 * Permet d'ajouter des produits au panier via AJAX

 */

document.addEventListener("DOMContentLoaded", function () {
  // Attacher les gestionnaires d'événements aux boutons d'ajout au panier

  attachAjoutPanierHandlers();

  // Mettre à jour le nombre d'articles dans le panier (header)

  updateCartBadge();
});

/**
 * Met à jour le badge du panier dans le header
 */
function updateCartBadge() {
  // Récupérer le nombre d'articles dans le panier depuis le badge
  const cartCountElement = document.getElementById("cart-count-badge");
  if (cartCountElement) {
    const count = parseInt(cartCountElement.textContent) || 0;
    updateCartCount(count);
  }
}

/**

 * Attache les gestionnaires d'événements aux boutons d'ajout au panier

 */

function attachAjoutPanierHandlers() {
  // Boutons d'ajout au panier - sélecteur général pour fonctionner partout

  const btnsAjoutPanier = document.querySelectorAll(
    ".btn-ajouter-panier, .ajouter-panier"
  );

  btnsAjoutPanier.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();

      // Récupérer l'ID du produit

      const idProduit =
        this.dataset.idProduit || this.getAttribute("data-id-produit");

      const quantite = this.dataset.quantite || 1;

      if (!idProduit) {
        console.error("ID de produit manquant");

        return;
      }

      // Ajouter au panier via AJAX

      ajouterAuPanierAjax(idProduit, quantite, this);
    });
  });
}

/**

 * Ajoute un produit au panier via AJAX

 */

function ajouterAuPanierAjax(idProduit, quantite = 1, element = null) {
  // Préparation des données

  const formData = new FormData();

  formData.append("id_produit", idProduit);

  formData.append("quantite", quantite);

  // Afficher un indicateur de chargement si nécessaire

  if (element) {
    const originalText = element.innerHTML;

    element.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Ajout en cours...';

    element.disabled = true;
  }

  // Créer l'URL avec le protocole actuel (http ou https)
  const url = new URL(window.location.origin);
  // Définir le chemin et les paramètres (sans index.php pour éviter la redirection)
  url.pathname = "/";
  url.searchParams.set("page", "panier");
  url.searchParams.set("action", "ajouter");
  url.searchParams.set("ajax", "1");
  // Ajouter un timestamp pour éviter la mise en cache
  url.searchParams.set("_", Date.now());

  fetch(url.toString(), {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => response.json())

    .then((data) => {
      // Rétablir le bouton original

      if (element) {
        setTimeout(() => {
          element.innerHTML = originalText;

          element.disabled = false;
        }, 500);
      }

      // Traiter la réponse

      if (data.success) {
        // Mettre à jour le badge du panier
        if (data.panier && data.panier.nombre_articles !== undefined) {
          updateCartCount(data.panier.nombre_articles);
        }

        // Afficher une notification de succès
        showNotification("Produit ajouté au panier", "success");
      } else {
        // Afficher une notification d'erreur
        showNotification(
          data.message || "Erreur lors de l'ajout au panier",
          "error"
        );
      }
    })

    .catch((error) => {
      console.error("Erreur AJAX:", error);

      // Rétablir le bouton original en cas d'erreur

      if (element) {
        element.innerHTML = originalText;

        element.disabled = false;
      }

      // Afficher une notification d'erreur

      showNotification("Erreur de connexion, veuillez réessayer.", "error");
    });
}

/**

 * Met à jour le compteur dans le badge du panier

 */

function updateCartCount(count) {
  // Mettre à jour le compteur numérique
  const countElements = document.querySelectorAll(".cart-count, .panier-count");
  countElements.forEach((element) => {
    element.textContent = count;

    // Assurer que le style est correctement appliqué
    element.style.display = "flex";
    element.style.alignItems = "center";
    element.style.justifyContent = "center";

    // Ajuster la taille du badge en fonction du nombre de chiffres
    if (count >= 10) {
      element.style.minWidth = "22px";
      element.style.padding = "0 0.4em";
    } else {
      element.style.minWidth = "20px";
      element.style.padding = "0 0.45em";
    }
  });

  // Gérer l'affichage du badge parent et l'icône d'exclamation
  const badges = document.querySelectorAll(".cart-badge, .panier-badge");
  badges.forEach((badge) => {
    const exclamationIcon = badge.querySelector(".fa-exclamation-triangle");

    if (count > 0) {
      badge.classList.remove("d-none");
      // S'assurer que l'icône d'exclamation est cachée quand il y a des articles
      if (exclamationIcon) {
        exclamationIcon.classList.add("d-none");
      }
      // S'assurer que le compteur est visible
      const countElement = badge.querySelector(".cart-count");
      if (countElement) {
        countElement.classList.remove("d-none");
        // Ajouter une animation pour attirer l'attention
        countElement.classList.add("animate__animated", "animate__bounceIn");
        setTimeout(() => {
          countElement.classList.remove(
            "animate__animated",
            "animate__bounceIn"
          );
        }, 1000);
      }
    } else {
      // Si le panier est vide
      if (exclamationIcon) {
        exclamationIcon.classList.remove("d-none");
      }
      // Cacher le compteur s'il est à zéro
      const countElement = badge.querySelector(".cart-count");
      if (countElement) {
        countElement.classList.add("d-none");
      }
    }
  });
}

/**
 * Affiche une notification à l'utilisateur
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type de notification (success, error, warning)
 */
function showNotification(message, type = "success") {
  // Supprimer les notifications existantes
  const existingNotifications = document.querySelectorAll(".custom-toast");
  existingNotifications.forEach((notification) => {
    notification.remove();
  });

  // Créer la notification
  const toast = document.createElement("div");
  toast.className = `custom-toast ${type}-toast animate__animated animate__fadeInUp`;

  // S'assurer que la notification est visible
  toast.style.display = "block";
  toast.style.opacity = "1";
  toast.style.zIndex = "9999";
  toast.style.position = "fixed";
  toast.style.bottom = "20px";
  toast.style.right = "20px";
  toast.style.minWidth = "280px";
  toast.style.maxWidth = "350px";
  toast.style.backgroundColor = "var(--fond-secondaire, #1e1e1e)";
  toast.style.color = "var(--texte-couleur, #FFFFFF)";
  toast.style.boxShadow = "0 4px 15px rgba(0, 0, 0, 0.3)";
  toast.style.borderRadius = "8px";
  toast.style.overflow = "hidden";
  toast.style.border =
    "1px solid var(--bordure-couleur, rgba(255, 255, 255, 0.1))";

  // Définir l'icône en fonction du type
  let icon = "fa-check-circle";
  if (type === "error") {
    icon = "fa-exclamation-triangle";
  } else if (type === "warning") {
    icon = "fa-exclamation-circle";
  }

  // Construire le HTML de la notification
  toast.innerHTML = `
    <div class="toast-header" style="background-color: var(--fond-secondaire, #1e1e1e); color: var(--texte-couleur, #FFFFFF); border-bottom: 1px solid var(--bordure-couleur, rgba(255, 255, 255, 0.1)); padding: 0.75rem 1rem; display: flex; align-items: center; ${
      type === "success"
        ? "border-left: 4px solid var(--success-color, #4CAF50);"
        : type === "error"
        ? "border-left: 4px solid var(--danger-color, #dc3545);"
        : "border-left: 4px solid var(--warning-color, #ff9800);"
    }">
      <i class="fas ${icon} me-2" style="color: ${
    type === "success"
      ? "var(--success-color, #4CAF50)"
      : type === "error"
      ? "var(--danger-color, #dc3545)"
      : "var(--warning-color, #ff9800)"
  };"></i>
      <strong class="me-auto">${
        type === "success"
          ? "Succès"
          : type === "error"
          ? "Erreur"
          : "Attention"
      }</strong>
      <button type="button" class="btn-close" aria-label="Fermer" style="background: transparent; border: none; font-size: 1.5rem; cursor: pointer; padding: 0; margin-left: auto;"></button>
    </div>
    <div class="toast-body" style="padding: 0.75rem 1rem; color: var(--texte-couleur, #FFFFFF);">
      ${message}
    </div>
  `;

  // Ajouter la notification au document
  document.body.appendChild(toast);

  // Ajouter un gestionnaire d'événement pour fermer la notification
  const closeButton = toast.querySelector(".btn-close");
  if (closeButton) {
    closeButton.addEventListener("click", function () {
      toast.classList.remove("animate__fadeInUp");
      toast.classList.add("animate__fadeOutDown");
      setTimeout(() => {
        toast.remove();
      }, 500);
    });
  }

  // Fermer automatiquement après 5 secondes
  setTimeout(() => {
    if (document.body.contains(toast)) {
      toast.classList.remove("animate__fadeInUp");
      toast.classList.add("animate__fadeOutDown");
      setTimeout(() => {
        if (document.body.contains(toast)) {
          toast.remove();
        }
      }, 500);
    }
  }, 5000);
}
