/**
 * Script de gestion simplifiée des options de livraison - Uniquement livraison à domicile
 */

document.addEventListener('DOMContentLoaded', function() {
    // --- Sélecteurs ---
    const deliveryOption = document.querySelector('.delivery-option');
    const nextStepButton = document.querySelector('.btn-next-step');
    const rgpdConsent = document.getElementById('rgpd_consent');
    const confirmButton = document.getElementById('confirmation-button');
    
    // Notification au serveur de la méthode de livraison sélectionnée
    function notifyDeliveryMethodChange(method) {
        console.log(`Méthode de livraison : ${method}`);
        const formData = new FormData();
        formData.append('methode', method);

        fetch('index.php?page=panier&action=methode-livraison&ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.panier) {
                updateDeliveryCostsDisplay(data.panier);
                
                // Activer le bouton de prochaine étape
                if (nextStepButton) {
                    nextStepButton.disabled = false;
                    nextStepButton.classList.remove('disabled');
                }
            } else {
                console.error('Erreur lors de la mise à jour de la méthode de livraison côté serveur:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur fetch pour mise à jour méthode de livraison:', error);
        });
    }

    // Met à jour l'affichage des frais de livraison et du total dans le récapitulatif
    function updateDeliveryCostsDisplay(panier) {
        // Mise à jour du prix dans l'option de livraison
        const priceElement = deliveryOption.querySelector('.delivery-option-price span');
        const fraisLivraisonDomicile = panier.frais_livraison || 6.90;

        if (priceElement) {
            if (fraisLivraisonDomicile === 0) {
                priceElement.innerHTML = 'Gratuit';
                priceElement.classList.add('text-success');
            } else {
                priceElement.textContent = fraisLivraisonDomicile.toFixed(2).replace('.', ',') + ' €';
            }
        }

        // Mise à jour dans le récapitulatif de commande si visible
        const recapFraisElement = document.querySelector('.checkout-summary-row:nth-child(3) span:last-child');
        const recapTotalElement = document.querySelector('.checkout-summary-total span:last-child');

        if (recapFraisElement) {
            if (fraisLivraisonDomicile === 0) {
                recapFraisElement.innerHTML = '<span class="text-success">Gratuit</span>';
            } else {
                recapFraisElement.textContent = fraisLivraisonDomicile.toFixed(2).replace('.', ',') + ' €';
            }
        }

        if (recapTotalElement) {
            const totalTTC = (panier.total || 0) + (fraisLivraisonDomicile || 0) + (panier.tva || 0);
            recapTotalElement.textContent = totalTTC.toFixed(2).replace('.', ',') + ' €';
        }
    }

    // S'assurer que le formulaire de checkout peut être soumis correctement
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            // Vérifier si on est à l'étape de livraison
            const isLivraisonStep = window.location.href.includes('etape=livraison');
            
            if (isLivraisonStep) {
                // S'assurer qu'une option de livraison est sélectionnée
                const selectedOption = document.querySelector('input[name="mode_livraison"]:checked');
                if (!selectedOption) {
                    e.preventDefault();
                    alert('Veuillez sélectionner une méthode de livraison');
                    return false;
                }
            }
        });
    }

    // Initialisation - Marquer la livraison à domicile comme sélectionnée
    if (deliveryOption) {
        deliveryOption.classList.add('selected');
        // Notification au serveur lors du chargement initial
        notifyDeliveryMethodChange('domicile');
    }
    
    // Activer les boutons pertinents selon le contexte
    // Activer le bouton de confirmation si le consentement RGPD est déjà coché
    if (rgpdConsent && confirmButton) {
        confirmButton.disabled = !rgpdConsent.checked;
        
        rgpdConsent.addEventListener('change', function() {
            confirmButton.disabled = !this.checked;
        });
    }
    
    // S'assurer que le bouton Next est toujours activé puisque l'option domicile est présélectionnée
    if (nextStepButton) {
        nextStepButton.disabled = false;
        nextStepButton.classList.remove('disabled');
    }
});
