import React from "react";

const TechnicalDetails = ({
  values,
  errors,
  touched,
  handleChange,
  handleBlur,
}) => {
  return (
    <div className="devis-form-step">
      <h3>
        <i className="fas fa-cogs"></i> Aspects techniques
      </h3>

      <p>
        Ces informations techniques nous aideront à mieux comprendre vos besoins
        et à vous proposer des solutions adaptées.
      </p>

      <div className="form-group">
        <label>Avez-vous déjà un nom de domaine ?</label>
        <div className="radio-group">
          <div className="radio-item">
            <label>
              <input
                type="radio"
                name="a_domaine"
                value="oui"
                checked={values.a_domaine === "oui"}
                onChange={handleChange}
                onBlur={handleBlur}
              />
              <span className="checkmark"></span>
              Oui
            </label>
          </div>
          <div className="radio-item">
            <label>
              <input
                type="radio"
                name="a_domaine"
                value="non"
                checked={values.a_domaine === "non"}
                onChange={handleChange}
                onBlur={handleBlur}
              />
              <span className="checkmark"></span>
              Non
            </label>
          </div>
          <div className="radio-item">
            <label>
              <input
                type="radio"
                name="a_domaine"
                value="besoin_aide"
                checked={values.a_domaine === "besoin_aide"}
                onChange={handleChange}
                onBlur={handleBlur}
              />
              <span className="checkmark"></span>
              J'ai besoin d'aide pour en choisir un
            </label>
          </div>
        </div>
      </div>

      <div className="form-group">
        <label>Avez-vous déjà un hébergement web ?</label>
        <div className="radio-group">
          <div className="radio-item">
            <label>
              <input
                type="radio"
                name="a_hebergement"
                value="oui"
                checked={values.a_hebergement === "oui"}
                onChange={handleChange}
                onBlur={handleBlur}
              />
              <span className="checkmark"></span>
              Oui
            </label>
          </div>
          <div className="radio-item">
            <label>
              <input
                type="radio"
                name="a_hebergement"
                value="non"
                checked={values.a_hebergement === "non"}
                onChange={handleChange}
                onBlur={handleBlur}
              />
              <span className="checkmark"></span>
              Non
            </label>
          </div>
          <div className="radio-item">
            <label>
              <input
                type="radio"
                name="a_hebergement"
                value="besoin_aide"
                checked={values.a_hebergement === "besoin_aide"}
                onChange={handleChange}
                onBlur={handleBlur}
              />
              <span className="checkmark"></span>
              J'ai besoin de conseils
            </label>
          </div>
        </div>
      </div>

      <div className="form-group">
        <label>Avez-vous besoin d'une charte graphique ?</label>
        <div className="radio-group">
          <div className="radio-item">
            <label>
              <input
                type="radio"
                name="besoin_charte"
                value="oui"
                checked={values.besoin_charte === "oui"}
                onChange={handleChange}
                onBlur={handleBlur}
              />
              <span className="checkmark"></span>
              Oui, création complète
            </label>
          </div>
          <div className="radio-item">
            <label>
              <input
                type="radio"
                name="besoin_charte"
                value="adaptation"
                checked={values.besoin_charte === "adaptation"}
                onChange={handleChange}
                onBlur={handleBlur}
              />
              <span className="checkmark"></span>
              Adaptation de l'existant
            </label>
          </div>
          <div className="radio-item">
            <label>
              <input
                type="radio"
                name="besoin_charte"
                value="non"
                checked={values.besoin_charte === "non"}
                onChange={handleChange}
                onBlur={handleBlur}
              />
              <span className="checkmark"></span>
              Non, j'ai déjà tous les éléments
            </label>
          </div>
        </div>
      </div>

      <div className="form-group">
        <label htmlFor="technologies">
          Technologies préférées (si vous avez des préférences)
        </label>
        <input
          type="text"
          className="form-control"
          id="technologies"
          name="technologies"
          value={values.technologies}
          onChange={handleChange}
          onBlur={handleBlur}
          placeholder="Ex: WordPress, PHP, React, etc."
        />
      </div>

      <div className="form-group">
        <label htmlFor="references">
          Sites web de référence (qui vous inspirent)
        </label>
        <textarea
          className="form-control"
          id="references"
          name="references"
          rows="3"
          value={values.references}
          onChange={handleChange}
          onBlur={handleBlur}
          placeholder="URLs de sites que vous aimez, avec ce qui vous plaît dans chacun"
        ></textarea>
      </div>

      <div className="form-group">
        <label htmlFor="commentaires">Autres informations ou questions</label>
        <textarea
          className="form-control"
          id="commentaires"
          name="commentaires"
          rows="3"
          value={values.commentaires}
          onChange={handleChange}
          onBlur={handleBlur}
        ></textarea>
      </div>

      <div className="form-group">
        <label htmlFor="source">Comment avez-vous connu LDW ?</label>
        <select
          className="form-control"
          id="source"
          name="source"
          value={values.source}
          onChange={handleChange}
          onBlur={handleBlur}
        >
          <option value="">Sélectionnez une option</option>
          <option value="recherche">Moteur de recherche</option>
          <option value="reseaux_sociaux">Réseaux sociaux</option>
          <option value="recommandation">Recommandation</option>
          <option value="autre">Autre</option>
        </select>
      </div>
    </div>
  );
};

export default TechnicalDetails;
