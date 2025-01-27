<?php
function accueilControleur($twig) {
    echo $twig->render('index.twig', ['name' => 'Le Design du web']);
}

function servicesControleur($twig) {
    echo $twig->render('services.twig', ['title' => 'Nos Services']);
}

function contactControleur(){
    echo 'Contact';
   }

function maintenanceControleur(){
    echo 'maintenance';
}