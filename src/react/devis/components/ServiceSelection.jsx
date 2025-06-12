import React from "react";

const ServiceSelection = ({ values, errors, touched, setFieldValue }) => {
  // Service options
  const services = [
    {
      id: "developpement-web",
      title: "Développement Web",
      description:
        "Création de sites vitrines élégants, plateformes e-commerce robustes et applications web personnalisées.",
      icon: "fas fa-laptop-code",
      price: "À partir de 18€/h",
      features: [
        "Design Responsive Unique",
        "Optimisation SEO Avancée",
        "Interface d'administration simple",
        "Technologies Modernes",
      ],
    },
    {
      id: "maintenance",
      title: "Maintenance Informatique",
      description:
        "Intervention rapide à distance ou sur site pour résoudre vos problèmes informatiques.",
      icon: "fas fa-tools",
      price: "À partir de 20€/h",
      features: [
        "Diagnostic Rapide et Précis",
        "Réparation Matériel & Logiciel",
        "Optimisation Système",
        "Récupération de Données",
      ],
    },
    {
      id: "montage-pc",
      title: "PC sur Mesure",
      description:
        "Configurez la machine de vos rêves ! Nous assemblons des PC optimisés pour vos besoins spécifiques.",
      icon: "fas fa-desktop",
      price: "À partir de 20€/h",
      features: [
        "Sélection Composants Premium",
        "Assemblage Expert & Tests",
        "Optimisation Performance",
        "Conseils Personnalisés",
      ],
    },
  ];

  // Handle service selection
  const handleServiceSelect = (serviceId) => {
    setFieldValue("serviceType", serviceId);
  };

  return (
    <div className="devis-form-step">
      <h3>
        <i className="fas fa-cogs"></i> Choisissez votre service
      </h3>

      <p>
        Sélectionnez le service qui correspond à votre besoin pour obtenir un
        devis personnalisé.
      </p>

      <div className="service-cards">
        {services.map((service) => (
          <div
            key={service.id}
            className={`service-card ${
              values.serviceType === service.id ? "selected" : ""
            }`}
            onClick={() => handleServiceSelect(service.id)}
          >
            <div className="service-card-header">
              <div className="service-card-icon">
                <i className={service.icon}></i>
              </div>
              <h4 className="service-card-title">{service.title}</h4>
            </div>

            <p className="service-card-description">{service.description}</p>

            <ul className="service-features">
              {service.features.map((feature, index) => (
                <li key={index}>{feature}</li>
              ))}
            </ul>

            <div className="service-card-price">{service.price}</div>
          </div>
        ))}
      </div>

      {errors.serviceType && touched.serviceType && (
        <div className="form-error">{errors.serviceType}</div>
      )}
    </div>
  );
};

export default ServiceSelection;
