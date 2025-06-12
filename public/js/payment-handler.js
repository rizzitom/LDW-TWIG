/**
 * Payment Handler JS - Gestion des paiements Stripe
 */

// Variables globales
let stripe;
let elements;
let paymentElement;
let paymentForm;
let paymentIntentId;

// Initialisation au chargement de la page
document.addEventListener("DOMContentLoaded", function () {
  // Configuration des tabs de méthodes de paiement
  setupPaymentTabs();

  // Initialisation de Stripe
  initializeStripe();

  // Initialisation de PayPal
  initializePayPal();

  // Gestion du formulaire de paiement
  setupPaymentForm();

  // Activer les RGPD checkboxes
  setupConsentCheckbox();
});

/**
 * Configure les onglets de méthode de paiement
 */
function setupPaymentTabs() {
  const paymentTabs = document.querySelectorAll(".payment-tab");
  const paymentContents = document.querySelectorAll(".payment-content");
  const methodeInput = document.getElementById("methode_paiement");

  paymentTabs.forEach((tab) => {
    tab.addEventListener("click", function () {
      const paymentMethod = this.getAttribute("data-payment");

      // Mettre à jour l'onglet actif
      paymentTabs.forEach((t) => t.classList.remove("active"));
      this.classList.add("active");

      // Afficher le contenu correspondant
      paymentContents.forEach((content) => {
        content.classList.remove("active");
        if (content.id === paymentMethod + "-payment-content") {
          content.classList.add("active");
        }
      });

      // Mettre à jour le champ caché pour la méthode de paiement
      methodeInput.value = paymentMethod === "paypal" ? "paypal" : "carte";
    });
  });
}

/**
 * Initialisation de Stripe et création de l'élément de paiement
 */
function initializeStripe() {
  // Récupérer la clé publique Stripe depuis la page
  const stripePublicKey =
    document.querySelector('meta[name="stripe-public-key"]')?.content ||
    "pk_live_51RDozlG7RjirpzdIvkXjsNZ9TcQ6phh5CJ2Xx18wrMJLxaw9sAYzNhSnFe28zRRUgtmHdSFXSbfAOew6LV8Jiodh00E6moh5PE";

  // Initialisation de l'objet Stripe
  stripe = Stripe(stripePublicKey);

  // Création de l'intention de paiement
  createPaymentIntent();
}

/**
 * Crée une intention de paiement via l'API Stripe
 */
function createPaymentIntent() {
  // Afficher l'indicateur de chargement
  const paymentElement = document.getElementById("payment-element");
  if (paymentElement) {
    paymentElement.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Initialisation du paiement...</p>
            </div>
        `;
  }

  // Préparer les données à envoyer
  const formData = new FormData();
  formData.append("payment_methods[]", "card");
  formData.append("payment_methods[]", "link");
  formData.append("payment_methods[]", "revolut_pay");

  // Envoyer la requête à l'API en utilisant une URL relative au protocole
  const url = new URL(
    "index.php?page=paiement&action=creer-intention",
    window.location.origin
  );
  // Ensure the URL uses the same protocol as the current page
  url.protocol = window.location.protocol;

  fetch(url.toString(), {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Erreur serveur: " + response.status);
      }

      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        throw new Error(
          "Format de réponse invalide. Attendu: JSON, reçu: " + contentType
        );
      }

      return response.json();
    })
    .then((result) => {
      if (!result.success) {
        throw new Error(
          result.message ||
            "Erreur lors de la création de l'intention de paiement"
        );
      }

      // Stocker l'ID de l'intention de paiement
      paymentIntentId = result.payment_intent_id;
      document.getElementById("payment_intent_id").value = paymentIntentId;

      // Initialiser l'élément de paiement Stripe
      initializePaymentElement(result.client_secret);
    })
    .catch((error) => {
      showPaymentError(
        error.message || "Erreur de communication avec le serveur",
        error.stack
      );

      // Afficher un bouton pour réessayer
      if (paymentElement) {
        paymentElement.innerHTML = `
                <div class="alert alert-danger">
                    <p><strong>Impossible d'initialiser le paiement</strong></p>
                    <p>Erreur: ${
                      error.message || "Erreur de communication avec le serveur"
                    }</p>
                    <button type="button" class="btn btn-outline-light mt-2" onclick="retryPaymentInitialization()">
                        <i class="fas fa-sync me-2"></i> Réessayer
                    </button>
                </div>
            `;
      }
    });
}

/**
 * Initialise l'élément de paiement Stripe avec la clé secrète client
 */
function initializePaymentElement(clientSecret) {
  // Créer les éléments Stripe
  elements = stripe.elements({
    clientSecret: clientSecret,
    appearance: {
      theme: document.body.classList.contains("light-theme")
        ? "stripe"
        : "night",
      variables: {
        colorPrimary: "#6200b3",
        colorBackground: document.body.classList.contains("light-theme")
          ? "#ffffff"
          : "#2b2b2c",
        colorText: document.body.classList.contains("light-theme")
          ? "#333333"
          : "#f8f9fa",
        colorDanger: "#dc3545",
        fontFamily: '"Inter", Arial, sans-serif',
      },
    },
    locale: "fr",
  });

  // Créer l'élément de paiement
  paymentElement = elements.create("payment", {
    layout: {
      type: "tabs",
      defaultCollapsed: false,
    },
  });

  // Monter l'élément dans le DOM
  paymentElement.mount("#payment-element");

  // Gérer les erreurs et changements
  paymentElement.on("change", function (event) {
    const displayError = document.getElementById("card-errors");
    if (displayError) {
      displayError.textContent = event.error ? event.error.message : "";
    }
  });
}

/**
 * Configuration du formulaire de paiement
 */
function setupPaymentForm() {
  // Prendre le formulaire principal
  paymentForm = document.querySelector("form");
  if (!paymentForm) return;

  // Ajouter l'écouteur d'événement sur le bouton de confirmation
  const confirmButton = document.getElementById("confirmation-button");
  if (confirmButton) {
    confirmButton.addEventListener("click", handleSubmit);
  }
}

/**
 * Gestion de la soumission du formulaire de paiement
 */
async function handleSubmit(e) {
  e.preventDefault();

  // Vérifier que les conditions sont acceptées
  const rgpdConsent = document.getElementById("rgpd_consent");
  if (rgpdConsent && !rgpdConsent.checked) {
    alert(
      "Veuillez accepter les conditions relatives à la politique de confidentialité pour continuer."
    );
    return;
  }

  // Désactiver le bouton pour éviter les soumissions multiples
  const submitButton = document.getElementById("confirmation-button");
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.innerHTML =
      '<i class="fas fa-spinner fa-spin me-2"></i> Traitement en cours...';
  }

  // Vérifier quelle méthode de paiement est sélectionnée
  const paymentMethod = document.getElementById("methode_paiement").value;

  if (paymentMethod === "carte") {
    // Traitement du paiement par carte
    await processCardPayment();
  } else if (paymentMethod === "paypal") {
    // Pour PayPal, le traitement se fait via le SDK PayPal
    // Le SDK PayPal devrait être initialisé séparément et gère sa propre soumission
    if (!document.getElementById("paypal_order_id").value) {
      alert("Veuillez terminer le paiement PayPal avant de continuer.");
      submitButton.disabled = false;
      submitButton.innerHTML =
        '<i class="fas fa-lock me-2"></i> Confirmer et payer';
    } else {
      // Si nous avons un PayPal order ID, soumettre le formulaire
      paymentForm.submit();
    }
  }
}

/**
 * Traitement du paiement par carte avec Stripe
 */
async function processCardPayment() {
  try {
    const { error } = await stripe.confirmPayment({
      elements,
      confirmParams: {
        return_url: new URL(
          "index.php?page=confirmation-commande",
          window.location.origin
        )
          .toString()
          .replace(/^https:/, window.location.protocol),
        payment_method_data: {
          billing_details: {
            name:
              document.getElementById("nom")?.value +
              " " +
              document.getElementById("prenom")?.value,
            email: document.getElementById("email")?.value,
            address: {
              line1: document.getElementById("adresse")?.value,
              postal_code: document.getElementById("code_postal")?.value,
              city: document.getElementById("ville")?.value,
              country: "FR", // France par défaut
            },
            phone: document.getElementById("telephone")?.value || null,
          },
        },
      },
      redirect: "if_required",
    });

    if (error) {
      // Afficher l'erreur et réactiver le bouton
      showPaymentError(error.message);
      const submitButton = document.getElementById("confirmation-button");
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML =
          '<i class="fas fa-lock me-2"></i> Confirmer et payer';
      }
    } else {
      // Le paiement a réussi sans redirection, soumettre le formulaire
      paymentForm.submit();
    }
  } catch (e) {
    console.error("Erreur lors du paiement:", e);
    showPaymentError(
      "Une erreur est survenue lors du traitement du paiement. Veuillez réessayer."
    );

    // Réactiver le bouton
    const submitButton = document.getElementById("confirmation-button");
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML =
        '<i class="fas fa-lock me-2"></i> Confirmer et payer';
    }
  }
}

/**
 * Affiche un message d'erreur de paiement
 */
function showPaymentError(message, details = null) {
  console.error("Erreur de paiement:", message, details);
  const errorElement = document.getElementById("card-errors");

  if (errorElement) {
    // Créer un message d'erreur bien formaté
    let errorHtml = `<div class="alert alert-danger">
            <strong>Erreur lors du paiement:</strong><br>
            ${message}
        </div>`;

    // Ajouter des détails techniques si disponibles
    if (details) {
      errorHtml += `<div class="mt-2 small text-muted">
                Détails techniques: ${details}
            </div>`;
    }

    errorElement.innerHTML = errorHtml;
  }
}

/**
 * Fonction pour réessayer l'initialisation du paiement
 */
function retryPaymentInitialization() {
  const errorElement = document.getElementById("card-errors");
  if (errorElement) {
    errorElement.innerHTML =
      '<div class="text-center py-2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Nouvelle tentative d\'initialisation...</div>';
  }

  createPaymentIntent();
}

/**
 * Configuration de la case à cocher de consentement RGPD
 */
function setupConsentCheckbox() {
  const rgpdConsent = document.getElementById("rgpd_consent");
  const confirmButton = document.getElementById("confirmation-button");

  if (rgpdConsent && confirmButton) {
    rgpdConsent.addEventListener("change", function () {
      confirmButton.disabled = !this.checked;
    });
  }
}

/**
 * Initialisation de PayPal
 */
function initializePayPal() {
  // Vérifier que le SDK PayPal est chargé
  if (typeof paypal === "undefined") {
    console.error("PayPal SDK not loaded");
    document.getElementById("paypal-errors").innerHTML =
      '<div class="alert alert-warning">Le service PayPal n\'est pas disponible pour le moment. Veuillez choisir un autre moyen de paiement.</div>';
    return;
  }

  // Récupérer le montant total du panier
  const totalAmountElem = document.querySelector(
    ".checkout-summary-total span:last-child"
  );
  if (!totalAmountElem) {
    console.error("Cannot find total amount element");
    return;
  }

  // Extraire le montant du texte (ex: "157,90 €" -> 157.90)
  let totalAmount = totalAmountElem.textContent.trim();
  totalAmount = parseFloat(
    totalAmount.replace(/[^\d,]/g, "").replace(",", ".")
  );

  if (isNaN(totalAmount) || totalAmount <= 0) {
    console.error("Invalid order amount for PayPal:", totalAmount);
    document.getElementById("paypal-errors").innerHTML =
      '<div class="alert alert-danger">Montant de commande invalide. Veuillez recharger la page ou contacter le support.</div>';
    return;
  }

  // Initialiser les boutons PayPal
  paypal
    .Buttons({
      // Configuration de la transaction
      createOrder: function (data, actions) {
        return actions.order.create({
          purchase_units: [
            {
              amount: {
                value: totalAmount.toFixed(2),
                currency_code: "EUR",
              },
              description: "Commande NetTech",
            },
          ],
        });
      },

      // Gestion de l'approbation PayPal
      onApprove: function (data, actions) {
        // Afficher l'état de chargement
        document.getElementById("paypal-button-container").innerHTML =
          '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Traitement du paiement en cours...</p></div>';

        // Capturer la commande
        return actions.order.capture().then(function (orderData) {
          // Stocker l'ID de la commande PayPal
          document.getElementById("paypal_order_id").value = orderData.id;
          document.getElementById("methode_paiement").value = "paypal";

          // Soumettre le formulaire
          document.getElementById("confirmation-button").click();
        });
      },

      // Gestion des erreurs
      onError: function (err) {
        console.error("PayPal Error:", err);
        document.getElementById("paypal-errors").innerHTML =
          '<div class="alert alert-danger">Une erreur s\'est produite avec PayPal. Veuillez réessayer ou choisir un autre moyen de paiement.</div>';
      },
    })
    .render("#paypal-button-container");
}
