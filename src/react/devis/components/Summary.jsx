import React from "react";

const Summary = ({
  values,
  errors,
  touched,
  handleChange,
  handleBlur,
  goToStep,
}) => {
  // Helper function to get service name from ID
  const getServiceName = (serviceId) => {
    switch (serviceId) {
      case "developpement-web":
        return "Développement Web";
      case "maintenance":
        return "Maintenance Informatique";
      case "montage":
        return "PC sur Mesure";
      default:
        return "Service non spécifié";
    }
  };

  // Helper function to get budget label from value
  const getBudgetLabel = (budgetValue) => {
    switch (budgetValue) {
      case "moins_1000":
        return "Moins de 1 000 €";
      case "1000_3000":
        return "Entre 1 000 € et 3 000 €";
      case "3000_5000":
        return "Entre 3 000 € et 5 000 €";
      case "5000_10000":
        return "Entre 5 000 € et 10 000 €";
      case "plus_10000":
        return "Plus de 10 000 €";
      default:
        return "Non spécifié";
    }
  };

  // Helper function to get delay label from value
  const getDelayLabel = (delayValue) => {
    switch (delayValue) {
      case "urgent":
        return "Urgent (moins d'un mois)";
      case "1_2_mois":
        return "1 à 2 mois";
      case "3_6_mois":
        return "3 à 6 mois";
      case "plus_6_mois":
        return "Plus de 6 mois";
      case "pas_contrainte":
        return "Pas de contrainte de temps";
      default:
        return "Non spécifié";
    }
  };

  // Helper function to get domain status label
  const getDomainLabel = (value) => {
    switch (value) {
      case "oui":
        return "Oui";
      case "non":
        return "Non";
      case "besoin_aide":
        return "Besoin d'aide pour en choisir un";
      default:
        return "Non spécifié";
    }
  };

  // Helper function to get hosting status label
  const getHostingLabel = (value) => {
    switch (value) {
      case "oui":
        return "Oui";
      case "non":
        return "Non";
      case "besoin_aide":
        return "Besoin de conseils";
      default:
        return "Non spécifié";
    }
  };

  // Helper function to get graphic charter status label
  const getCharterLabel = (value) => {
    switch (value) {
      case "oui":
        return "Oui, création complète";
      case "adaptation":
        return "Adaptation de l'existant";
      case "non":
        return "Non, j'ai déjà tous les éléments";
      default:
        return "Non spécifié";
    }
  };

  // Helper function to get source label
  const getSourceLabel = (value) => {
    switch (value) {
      case "recherche":
        return "Moteur de recherche";
      case "reseaux_sociaux":
        return "Réseaux sociaux";
      case "recommandation":
        return "Recommandation";
      case "autre":
        return "Autre";
      default:
        return "Non spécifié";
    }
  };

  // Helper function to get project type label
  const getProjectTypeLabel = (value) => {
    switch (value) {
      case "site_vitrine":
        return "Site vitrine";
      case "site_ecommerce":
        return "Site e-commerce";
      case "application_web":
        return "Application web";
      case "refonte_site":
        return "Refonte de site existant";
      case "autre":
        return "Autre";
      default:
        return "Non spécifié";
    }
  };

  return (
    <div className="devis-form-step">
      <h3>
        <i className="fas fa-clipboard-check"></i> Récapitulatif de votre
        demande
      </h3>

      <p>
        Veuillez vérifier les informations ci-dessous avant de soumettre votre
        demande de devis. Vous pouvez modifier les informations en cliquant sur
        le bouton "Modifier" correspondant.
      </p>

      <div className="summary-section">
        <h4>
          <i className="fas fa-cogs me-2"></i> Service demandé
          <button
            type="button"
            className="summary-edit-btn"
            onClick={() => goToStep(0)}
          >
            <i className="fas fa-edit"></i> Modifier
          </button>
        </h4>
        <div className="summary-item">
          <div className="summary-label">Type de service</div>
          <div className="summary-value">
            {getServiceName(values.serviceType)}
          </div>
        </div>
      </div>

      <div className="summary-section">
        <h4>
          <i className="fas fa-user me-2"></i> Vos informations
          <button
            type="button"
            className="summary-edit-btn"
            onClick={() => goToStep(1)}
          >
            <i className="fas fa-edit"></i> Modifier
          </button>
        </h4>
        <div className="summary-item">
          <div className="summary-label">Nom</div>
          <div className="summary-value">{values.nom}</div>
        </div>
        <div className="summary-item">
          <div className="summary-label">Prénom</div>
          <div className="summary-value">{values.prenom}</div>
        </div>
        <div className="summary-item">
          <div className="summary-label">Email</div>
          <div className="summary-value">{values.email}</div>
        </div>
        {values.telephone && (
          <div className="summary-item">
            <div className="summary-label">Téléphone</div>
            <div className="summary-value">{values.telephone}</div>
          </div>
        )}
        {values.societe && (
          <div className="summary-item">
            <div className="summary-label">Entreprise</div>
            <div className="summary-value">{values.societe}</div>
          </div>
        )}
      </div>

      <div className="summary-section">
        <h4>
          <i className="fas fa-laptop-code me-2"></i> Détails du projet
          <button
            type="button"
            className="summary-edit-btn"
            onClick={() => goToStep(2)}
          >
            <i className="fas fa-edit"></i> Modifier
          </button>
        </h4>
        <div className="summary-item">
          <div className="summary-label">Titre du projet</div>
          <div className="summary-value">{values.titre_projet}</div>
        </div>
        <div className="summary-item">
          <div className="summary-label">Type de projet</div>
          <div className="summary-value">
            {getProjectTypeLabel(values.type_projet)}
          </div>
        </div>
        <div className="summary-item">
          <div className="summary-label">Description</div>
          <div className="summary-value">{values.description_projet}</div>
        </div>
        {values.fonctionnalites.length > 0 && (
          <div className="summary-item">
            <div className="summary-label">Fonctionnalités</div>
            <div className="summary-value">
              {values.fonctionnalites.join(", ")}
            </div>
          </div>
        )}
        {values.budget && (
          <div className="summary-item">
            <div className="summary-label">Budget</div>
            <div className="summary-value">{getBudgetLabel(values.budget)}</div>
          </div>
        )}
        {values.delai && (
          <div className="summary-item">
            <div className="summary-label">Délai</div>
            <div className="summary-value">{getDelayLabel(values.delai)}</div>
          </div>
        )}
      </div>

      <div className="summary-section">
        <h4>
          <i className="fas fa-wrench me-2"></i> Aspects techniques
          <button
            type="button"
            className="summary-edit-btn"
            onClick={() => goToStep(3)}
          >
            <i className="fas fa-edit"></i> Modifier
          </button>
        </h4>
        {values.a_domaine && (
          <div className="summary-item">
            <div className="summary-label">Nom de domaine</div>
            <div className="summary-value">
              {getDomainLabel(values.a_domaine)}
            </div>
          </div>
        )}
        {values.a_hebergement && (
          <div className="summary-item">
            <div className="summary-label">Hébergement web</div>
            <div className="summary-value">
              {getHostingLabel(values.a_hebergement)}
            </div>
          </div>
        )}
        {values.besoin_charte && (
          <div className="summary-item">
            <div className="summary-label">Charte graphique</div>
            <div className="summary-value">
              {getCharterLabel(values.besoin_charte)}
            </div>
          </div>
        )}
        {values.technologies && (
          <div className="summary-item">
            <div className="summary-label">Technologies</div>
            <div className="summary-value">{values.technologies}</div>
          </div>
        )}
        {values.references && (
          <div className="summary-item">
            <div className="summary-label">Sites de référence</div>
            <div className="summary-value">{values.references}</div>
          </div>
        )}
        {values.commentaires && (
          <div className="summary-item">
            <div className="summary-label">Commentaires</div>
            <div className="summary-value">{values.commentaires}</div>
          </div>
        )}
        {values.source && (
          <div className="summary-item">
            <div className="summary-label">Comment nous avez-vous connu</div>
            <div className="summary-value">{getSourceLabel(values.source)}</div>
          </div>
        )}
      </div>

      <div className="terms-container">
        <h4 className="mb-2">
          <i className="fas fa-file-contract me-2"></i> Conditions générales
        </h4>
        <p>
          En soumettant ce formulaire, vous acceptez que les informations
          saisies soient utilisées pour vous recontacter dans le cadre de votre
          demande de devis.
        </p>
        <div className="terms-checkbox">
          <label>
            <input
              type="checkbox"
              name="conditions"
              checked={values.conditions}
              onChange={handleChange}
              onBlur={handleBlur}
            />
            <span className="checkmark"></span>
            J'accepte les{" "}
            <a href="index.php?page=cgv" target="_blank">
              conditions générales de vente
            </a>{" "}
            et la{" "}
            <a
              href="index.php?page=politique-de-confidentialite"
              target="_blank"
            >
              politique de confidentialité
            </a>
            .
          </label>
        </div>
        {errors.conditions && touched.conditions && (
          <div className="form-error">{errors.conditions}</div>
        )}
      </div>
    </div>
  );
};

export default Summary;
