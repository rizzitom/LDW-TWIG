import React from "react";
import { createRoot } from "react-dom/client";
import DevisApp from "./components/DevisApp";
import "./styles/DevisForm.css";

// Wait for DOM to be fully loaded
document.addEventListener("DOMContentLoaded", () => {
  // Find the container element
  const container = document.getElementById("devis-react-app");

  // Only initialize if the container exists
  if (container) {
    // Get the service type from the data attribute if available
    const serviceType = container.dataset.serviceType || null;

    // Create a root
    const root = createRoot(container);

    // Render the app
    root.render(
      <React.StrictMode>
        <DevisApp initialServiceType={serviceType} />
      </React.StrictMode>
    );
  }
});
