import React, { useEffect, useState } from "react";

const Success = ({ data }) => {
  const [showConfetti, setShowConfetti] = useState(false);

  // Trigger confetti animation on component mount
  useEffect(() => {
    setShowConfetti(true);

    // Hide confetti after 5 seconds
    const timer = setTimeout(() => {
      setShowConfetti(false);
    }, 5000);

    return () => clearTimeout(timer);
  }, []);

  // Format the date for display
  const formatDate = () => {
    const now = new Date();
    const options = {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    };
    return now.toLocaleDateString("fr-FR", options);
  };

  // Calculate estimated response time (2 business days from now)
  const getEstimatedResponseDate = () => {
    const now = new Date();
    let businessDays = 2;
    const responseDate = new Date(now);

    while (businessDays > 0) {
      responseDate.setDate(responseDate.getDate() + 1);
      // Skip weekends (0 = Sunday, 6 = Saturday)
      if (responseDate.getDay() !== 0 && responseDate.getDay() !== 6) {
        businessDays--;
      }
    }

    const options = { weekday: "long", day: "numeric", month: "long" };
    return responseDate.toLocaleDateString("fr-FR", options);
  };

  return (
    <div className="success-message animate__animated animate__fadeIn">
      {showConfetti && (
        <div className="confetti-container">
          {[...Array(50)].map((_, i) => (
            <div
              key={i}
              className="confetti"
              style={{
                left: `${Math.random() * 100}%`,
                animationDelay: `${Math.random() * 5}s`,
                backgroundColor: `hsl(${Math.random() * 360}, 100%, 50%)`,
              }}
            />
          ))}
        </div>
      )}

      <div className="success-icon animate__animated animate__bounceIn animate__delay-1s">
        <i className="fas fa-check-circle"></i>
      </div>

      <h2 className="success-title animate__animated animate__fadeInUp animate__delay-1s">
        Demande envoyée avec succès !
      </h2>

      <p className="success-text animate__animated animate__fadeInUp animate__delay-1s">
        Merci pour votre demande de devis. Notre équipe va l'étudier et vous
        contactera <strong>avant le {getEstimatedResponseDate()}</strong> pour
        vous proposer une solution adaptée à vos besoins.
      </p>

      {data && data.id_devis && (
        <div className="success-reference animate__animated animate__fadeInUp animate__delay-1s">
          <div className="reference-label">Référence de votre demande</div>
          <div className="reference-value">DEV-{data.id_devis}</div>
          <div className="reference-date">Soumise le {formatDate()}</div>
        </div>
      )}

      <div className="success-next-steps animate__animated animate__fadeInUp animate__delay-2s">
        <h4>
          <i className="fas fa-clipboard-list me-2"></i>Prochaines étapes
        </h4>
        <ol className="steps-list">
          <li>
            Vous allez recevoir un email de confirmation à l'adresse indiquée
          </li>
          <li>Notre équipe analysera votre demande sous 48h ouvrées</li>
          <li>
            Nous vous contacterons pour discuter des détails et affiner le devis
          </li>
          <li>Vous recevrez une proposition personnalisée</li>
        </ol>
      </div>

      <div className="success-buttons animate__animated animate__fadeInUp animate__delay-2s">
        <a href="index.php" className="btn-home">
          <i className="fas fa-home"></i> Retour à l'accueil
        </a>

        <a href="index.php?page=mes-devis" className="btn-view-quotes">
          <i className="fas fa-file-invoice-dollar"></i> Voir mes devis
        </a>
      </div>

      <div className="success-contact animate__animated animate__fadeInUp animate__delay-2s">
        <p>
          <i className="fas fa-question-circle me-2"></i>
          Des questions ? Contactez-nous au <strong>01 23 45 67 89</strong>
        </p>
      </div>
    </div>
  );
};

export default Success;
