import React from "react";

const ProjectDetails = ({
  values,
  errors,
  touched,
  handleChange,
  handleBlur,
  setFieldValue,
}) => {
  // Available functionalities for web development
  const functionalities = [
    { id: "responsive", label: "Design responsive" },
    { id: "admin", label: "Interface d'administration" },
    { id: "blog", label: "Blog/Actualités" },
    { id: "newsletter", label: "Newsletter" },
    { id: "multilingue", label: "Multilingue" },
    { id: "paiement", label: "Système de paiement" },
    { id: "reservation", label: "Système de réservation" },
    { id: "membres", label: "Espace membres" },
  ];

  // Handle checkbox change for functionalities
  const handleFunctionalityChange = (e) => {
    const { value, checked } = e.target;
    const currentFunctionalities = [...values.fonctionnalites];

    if (checked) {
      // Add to array if checked
      setFieldValue("fonctionnalites", [...currentFunctionalities, value]);
    } else {
      // Remove from array if unchecked
      setFieldValue(
        "fonctionnalites",
        currentFunctionalities.filter((item) => item !== value)
      );
    }
  };

  return (
    <div className="devis-form-step">
      <h3>
        <i className="fas fa-laptop-code"></i> Votre projet web
      </h3>

      <p>
        Décrivez votre projet pour nous permettre de comprendre vos besoins et
        vous proposer une solution adaptée.
      </p>

      <div className="form-group">
        <label htmlFor="titre_projet">Titre du projet *</label>
        <input
          type="text"
          className="form-control"
          id="titre_projet"
          name="titre_projet"
          value={values.titre_projet}
          onChange={handleChange}
          onBlur={handleBlur}
          placeholder="Ex: Site vitrine pour mon entreprise de plomberie"
        />
        {errors.titre_projet && touched.titre_projet && (
          <div className="form-error">{errors.titre_projet}</div>
        )}
      </div>

      <div className="form-group">
        <label htmlFor="type_projet">Type de projet *</label>
        <select
          className="form-control"
          id="type_projet"
          name="type_projet"
          value={values.type_projet}
          onChange={handleChange}
          onBlur={handleBlur}
        >
          <option value="">Sélectionnez une option</option>
          <option value="site_vitrine">Site vitrine</option>
          <option value="site_ecommerce">Site e-commerce</option>
          <option value="application_web">Application web</option>
          <option value="refonte_site">Refonte de site existant</option>
          <option value="autre">Autre (précisez dans la description)</option>
        </select>
        {errors.type_projet && touched.type_projet && (
          <div className="form-error">{errors.type_projet}</div>
        )}
      </div>

      <div className="form-group">
        <label htmlFor="description_projet">
          Description détaillée du projet *
        </label>
        <textarea
          className="form-control"
          id="description_projet"
          name="description_projet"
          rows="5"
          value={values.description_projet}
          onChange={handleChange}
          onBlur={handleBlur}
          placeholder="Décrivez votre projet, ses objectifs, ses fonctionnalités principales..."
        ></textarea>
        {errors.description_projet && touched.description_projet && (
          <div className="form-error">{errors.description_projet}</div>
        )}
      </div>

      <div className="form-group">
        <label>Fonctionnalités souhaitées</label>
        <div className="checkbox-group">
          {functionalities.map((functionality) => (
            <div className="checkbox-item" key={functionality.id}>
              <label>
                <input
                  type="checkbox"
                  name="fonctionnalites"
                  value={functionality.id}
                  checked={values.fonctionnalites.includes(functionality.id)}
                  onChange={handleFunctionalityChange}
                />
                <span className="checkmark"></span>
                {functionality.label}
              </label>
            </div>
          ))}
        </div>
      </div>

      <div className="form-group">
        <label htmlFor="budget">Budget approximatif (en €)</label>
        <select
          className="form-control"
          id="budget"
          name="budget"
          value={values.budget}
          onChange={handleChange}
          onBlur={handleBlur}
        >
          <option value="">Sélectionnez une option</option>
          <option value="moins_1000">Moins de 1 000 €</option>
          <option value="1000_3000">Entre 1 000 € et 3 000 €</option>
          <option value="3000_5000">Entre 3 000 € et 5 000 €</option>
          <option value="5000_10000">Entre 5 000 € et 10 000 €</option>
          <option value="plus_10000">Plus de 10 000 €</option>
        </select>
      </div>

      <div className="form-group">
        <label htmlFor="delai">Délai souhaité</label>
        <select
          className="form-control"
          id="delai"
          name="delai"
          value={values.delai}
          onChange={handleChange}
          onBlur={handleBlur}
        >
          <option value="">Sélectionnez une option</option>
          <option value="urgent">Urgent (moins d'un mois)</option>
          <option value="1_2_mois">1 à 2 mois</option>
          <option value="3_6_mois">3 à 6 mois</option>
          <option value="plus_6_mois">Plus de 6 mois</option>
          <option value="pas_contrainte">Pas de contrainte de temps</option>
        </select>
      </div>

      <div className="form-text">
        <i className="fas fa-info-circle"></i> Les champs marqués d'un
        astérisque (*) sont obligatoires.
      </div>
    </div>
  );
};

export default ProjectDetails;
