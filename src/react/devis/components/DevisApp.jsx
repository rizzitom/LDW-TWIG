import React, { useState, useEffect, useRef } from "react";
import { Formik, Form } from "formik";
import * as Yup from "yup";
import ServiceSelection from "./ServiceSelection";
import PersonalInfo from "./PersonalInfo";
import ProjectDetails from "./ProjectDetails";
import TechnicalDetails from "./TechnicalDetails";
import Summary from "./Summary";
import Success from "./Success";
import ProgressBar from "./ProgressBar";

const DevisApp = ({ initialServiceType }) => {
  // Define the steps of the form
  const steps = [
    { id: "service", label: "Service" },
    { id: "personal", label: "Informations" },
    { id: "project", label: "Projet" },
    { id: "technical", label: "Technique" },
    { id: "summary", label: "Récapitulatif" },
  ];

  // State to track the current step
  const [currentStep, setCurrentStep] = useState(0);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [submissionData, setSubmissionData] = useState(null);

  // Set initial service type if provided
  useEffect(() => {
    if (initialServiceType) {
      // Skip to personal info step if service type is already known
      setCurrentStep(1);
    }
  }, [initialServiceType]);

  // Initial form values
  const initialValues = {
    // Service selection
    serviceType: initialServiceType || "",

    // Personal information
    nom: "",
    prenom: "",
    email: "",
    telephone: "",
    societe: "",

    // Project details (for development)
    titre_projet: "",
    type_projet: "",
    description_projet: "",
    fonctionnalites: [],
    budget: "",
    delai: "",

    // Technical details
    a_domaine: "",
    a_hebergement: "",
    besoin_charte: "",
    technologies: "",
    references: "",

    // Additional information
    commentaires: "",
    source: "",
    conditions: false,
  };

  // Validation schemas for each step
  const ServiceValidationSchema = Yup.object().shape({
    serviceType: Yup.string().required("Veuillez sélectionner un service"),
  });

  const PersonalValidationSchema = Yup.object().shape({
    nom: Yup.string().required("Le nom est requis"),
    prenom: Yup.string().required("Le prénom est requis"),
    email: Yup.string()
      .email("Adresse email invalide")
      .required("L'email est requis"),
    telephone: Yup.string(),
    societe: Yup.string(),
  });

  const ProjectValidationSchema = Yup.object().shape({
    titre_projet: Yup.string().required("Le titre du projet est requis"),
    type_projet: Yup.string().required("Le type de projet est requis"),
    description_projet: Yup.string().required(
      "La description du projet est requise"
    ),
    budget: Yup.string(),
    delai: Yup.string(),
  });

  const TechnicalValidationSchema = Yup.object().shape({
    a_domaine: Yup.string(),
    a_hebergement: Yup.string(),
    besoin_charte: Yup.string(),
    technologies: Yup.string(),
    references: Yup.string(),
  });

  const SummaryValidationSchema = Yup.object().shape({
    conditions: Yup.boolean()
      .required("Vous devez accepter les conditions")
      .oneOf([true], "Vous devez accepter les conditions"),
  });

  // Get the current validation schema based on the step
  const getValidationSchema = (step) => {
    switch (step) {
      case 0:
        return ServiceValidationSchema;
      case 1:
        return PersonalValidationSchema;
      case 2:
        return ProjectValidationSchema;
      case 3:
        return TechnicalValidationSchema;
      case 4:
        return SummaryValidationSchema;
      default:
        return Yup.object().shape({});
    }
  };

  // Handle form submission
  const handleSubmit = async (values, { setSubmitting }) => {
    if (currentStep < steps.length - 1) {
      setCurrentStep(currentStep + 1);
      setSubmitting(false);
    } else {
      // Final submission
      setIsSubmitting(true);

      try {
        // Prepare form data for submission
        const formData = new FormData();

        // Add all form values to formData
        Object.keys(values).forEach((key) => {
          if (key === "fonctionnalites" && Array.isArray(values[key])) {
            // Handle array values
            values[key].forEach((value) => {
              formData.append(`${key}[]`, value);
            });
          } else {
            formData.append(key, values[key]);
          }
        });

        // Add action parameter
        formData.append("action", "envoyer");

        // Send the form data
        const response = await fetch("index.php?page=devis&action=envoyer", {
          method: "POST",
          body: formData,
        });

        const result = await response.json();

        if (result.success) {
          // Show success message
          setIsSubmitted(true);
          setSubmissionData(result);
        } else {
          // Handle error
          console.error("Submission error:", result.message);
          alert(`Erreur: ${result.message}`);
        }
      } catch (error) {
        console.error("Submission error:", error);
        alert("Une erreur est survenue lors de l'envoi du formulaire.");
      } finally {
        setIsSubmitting(false);
        setSubmitting(false);
      }
    }
  };

  // Go to previous step
  const handlePrevStep = () => {
    setCurrentStep(Math.max(0, currentStep - 1));
  };

  // Go to a specific step (for summary edit)
  const goToStep = (step) => {
    setCurrentStep(step);
  };

  // Render the current step
  const renderStep = (formikProps) => {
    const { values, errors, touched, handleChange, handleBlur, setFieldValue } =
      formikProps;

    switch (currentStep) {
      case 0:
        return (
          <ServiceSelection
            values={values}
            errors={errors}
            touched={touched}
            setFieldValue={setFieldValue}
          />
        );
      case 1:
        return (
          <PersonalInfo
            values={values}
            errors={errors}
            touched={touched}
            handleChange={handleChange}
            handleBlur={handleBlur}
          />
        );
      case 2:
        return (
          <ProjectDetails
            values={values}
            errors={errors}
            touched={touched}
            handleChange={handleChange}
            handleBlur={handleBlur}
            setFieldValue={setFieldValue}
          />
        );
      case 3:
        return (
          <TechnicalDetails
            values={values}
            errors={errors}
            touched={touched}
            handleChange={handleChange}
            handleBlur={handleBlur}
          />
        );
      case 4:
        return (
          <Summary
            values={values}
            errors={errors}
            touched={touched}
            handleChange={handleChange}
            handleBlur={handleBlur}
            goToStep={goToStep}
          />
        );
      default:
        return null;
    }
  };

  // Reference to the form container for scrolling
  const formContainerRef = useRef(null);

  // Scroll to top of form when step changes
  useEffect(() => {
    if (formContainerRef.current) {
      // Smooth scroll to the top of the form with a slight delay
      setTimeout(() => {
        formContainerRef.current.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }, 100);
    }
  }, [currentStep]);

  // If the form has been submitted successfully, show the success component
  if (isSubmitted && submissionData) {
    return <Success data={submissionData} />;
  }

  return (
    <div className="devis-react-container" ref={formContainerRef}>
      <div className="devis-intro-text mb-4">
        <h3 className="text-center mb-3">Demande de devis personnalisé</h3>
        <p className="text-center">
          Complétez ce formulaire en {steps.length} étapes simples pour recevoir
          une proposition adaptée à vos besoins.
        </p>
      </div>

      <ProgressBar steps={steps} currentStep={currentStep} />

      <Formik
        initialValues={initialValues}
        validationSchema={getValidationSchema(currentStep)}
        onSubmit={handleSubmit}
        validateOnMount={false}
        validateOnChange={false}
        validateOnBlur={true}
      >
        {(formikProps) => (
          <Form className="devis-form animate__animated animate__fadeIn">
            {renderStep(formikProps)}

            <div className="devis-form-buttons">
              {currentStep > 0 && (
                <button
                  type="button"
                  className="btn-prev"
                  onClick={handlePrevStep}
                  disabled={isSubmitting}
                >
                  <i className="fas fa-arrow-left"></i> Précédent
                </button>
              )}

              <button
                type="submit"
                className={
                  currentStep === steps.length - 1 ? "btn-submit" : "btn-next"
                }
                disabled={
                  isSubmitting ||
                  (currentStep === steps.length - 1 &&
                    !formikProps.values.conditions)
                }
              >
                {currentStep === steps.length - 1 ? (
                  <>
                    {isSubmitting ? (
                      <>
                        <span
                          className="spinner-border spinner-border-sm me-2"
                          role="status"
                          aria-hidden="true"
                        ></span>
                        Envoi en cours...
                      </>
                    ) : (
                      <>
                        Envoyer la demande{" "}
                        <i className="fas fa-paper-plane"></i>
                      </>
                    )}
                  </>
                ) : (
                  <>
                    Suivant <i className="fas fa-arrow-right"></i>
                  </>
                )}
              </button>
            </div>

            <div className="devis-step-indicator text-center mt-4">
              <small className="text-muted">
                Étape {currentStep + 1} sur {steps.length}
              </small>
            </div>
          </Form>
        )}
      </Formik>

      <div className="devis-help-text mt-4 text-center">
        <p className="small text-muted">
          <i className="fas fa-info-circle me-2"></i>
          Besoin d'aide ? Contactez-nous au <strong>01 23 45 67 89</strong> ou
          par email à <strong>contact@ldw.fr</strong>
        </p>
      </div>
    </div>
  );
};

export default DevisApp;
