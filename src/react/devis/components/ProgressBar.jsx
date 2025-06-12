import React, { useEffect, useRef } from "react";

const ProgressBar = ({ steps, currentStep }) => {
  // Calculate progress percentage
  const progressPercentage = (currentStep / (steps.length - 1)) * 100;

  // Reference to the progress bar fill element
  const progressFillRef = useRef(null);

  // Add animation effect when progress changes
  useEffect(() => {
    if (progressFillRef.current) {
      // Add a pulse animation class
      progressFillRef.current.classList.add("progress-pulse");

      // Remove the class after animation completes
      const timer = setTimeout(() => {
        if (progressFillRef.current) {
          progressFillRef.current.classList.remove("progress-pulse");
        }
      }, 800);

      return () => clearTimeout(timer);
    }
  }, [currentStep]);

  // Get icon for each step
  const getStepIcon = (index) => {
    if (index < currentStep) {
      return <i className="fas fa-check"></i>;
    } else if (index === currentStep) {
      const icons = {
        0: <i className="fas fa-list-ul"></i>, // Service selection
        1: <i className="fas fa-user"></i>, // Personal info
        2: <i className="fas fa-project-diagram"></i>, // Project details
        3: <i className="fas fa-cogs"></i>, // Technical details
        4: <i className="fas fa-clipboard-check"></i>, // Summary
      };
      return icons[index] || index + 1;
    } else {
      return index + 1;
    }
  };

  return (
    <div className="devis-progress-container">
      <div className="devis-progress-bar">
        {/* Progress bar fill */}
        <div
          ref={progressFillRef}
          className="devis-progress-bar-fill"
          style={{ width: `${progressPercentage}%` }}
        ></div>

        {/* Step indicators */}
        {steps.map((step, index) => (
          <div
            key={step.id}
            className={`devis-step ${
              index === currentStep
                ? "active"
                : index < currentStep
                ? "completed"
                : ""
            }`}
            title={`Étape ${index + 1}: ${step.label}`}
          >
            {getStepIcon(index)}
            <span className="devis-step-label">{step.label}</span>
          </div>
        ))}
      </div>

      {/* Add a description for the current step */}
      <div className="devis-step-description text-center mt-3 mb-4">
        <h4 className="step-title">
          {currentStep === 0 && <i className="fas fa-list-ul me-2"></i>}
          {currentStep === 1 && <i className="fas fa-user me-2"></i>}
          {currentStep === 2 && <i className="fas fa-project-diagram me-2"></i>}
          {currentStep === 3 && <i className="fas fa-cogs me-2"></i>}
          {currentStep === 4 && <i className="fas fa-clipboard-check me-2"></i>}
          {steps[currentStep]?.label}
        </h4>
        <p className="step-description text-muted">
          {currentStep === 0 &&
            "Sélectionnez le service qui correspond à votre besoin."}
          {currentStep === 1 &&
            "Partagez vos coordonnées pour personnaliser notre proposition."}
          {currentStep === 2 &&
            "Décrivez votre projet et vos objectifs principaux."}
          {currentStep === 3 &&
            "Précisez les aspects techniques de votre projet."}
          {currentStep === 4 &&
            "Vérifiez les informations avant de soumettre votre demande."}
        </p>
      </div>
    </div>
  );
};

export default ProgressBar;
