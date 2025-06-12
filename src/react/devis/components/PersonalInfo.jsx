import React from "react";

const PersonalInfo = ({
  values,
  errors,
  touched,
  handleChange,
  handleBlur,
}) => {
  return (
    <div className="devis-form-step">
      <h3>
        <i className="fas fa-user"></i> Vos informations
      </h3>

      <p>
        Parlez-nous de vous pour personnaliser votre devis. Ces informations
        nous permettront de vous contacter.
      </p>

      <div className="row">
        <div className="col-md-6">
          <div className="form-group">
            <label htmlFor="nom">Nom *</label>
            <input
              type="text"
              className="form-control"
              id="nom"
              name="nom"
              value={values.nom}
              onChange={handleChange}
              onBlur={handleBlur}
            />
            {errors.nom && touched.nom && (
              <div className="form-error">{errors.nom}</div>
            )}
          </div>
        </div>

        <div className="col-md-6">
          <div className="form-group">
            <label htmlFor="prenom">Prénom *</label>
            <input
              type="text"
              className="form-control"
              id="prenom"
              name="prenom"
              value={values.prenom}
              onChange={handleChange}
              onBlur={handleBlur}
            />
            {errors.prenom && touched.prenom && (
              <div className="form-error">{errors.prenom}</div>
            )}
          </div>
        </div>
      </div>

      <div className="row">
        <div className="col-md-6">
          <div className="form-group">
            <label htmlFor="email">Email *</label>
            <input
              type="email"
              className="form-control"
              id="email"
              name="email"
              value={values.email}
              onChange={handleChange}
              onBlur={handleBlur}
            />
            {errors.email && touched.email && (
              <div className="form-error">{errors.email}</div>
            )}
          </div>
        </div>

        <div className="col-md-6">
          <div className="form-group">
            <label htmlFor="telephone">Téléphone</label>
            <input
              type="tel"
              className="form-control"
              id="telephone"
              name="telephone"
              value={values.telephone}
              onChange={handleChange}
              onBlur={handleBlur}
            />
            {errors.telephone && touched.telephone && (
              <div className="form-error">{errors.telephone}</div>
            )}
          </div>
        </div>
      </div>

      <div className="form-group">
        <label htmlFor="societe">Nom de l'entreprise (si applicable)</label>
        <input
          type="text"
          className="form-control"
          id="societe"
          name="societe"
          value={values.societe}
          onChange={handleChange}
          onBlur={handleBlur}
        />
      </div>

      <div className="form-text">
        <i className="fas fa-info-circle"></i> Les champs marqués d'un
        astérisque (*) sont obligatoires.
      </div>
    </div>
  );
};

export default PersonalInfo;
