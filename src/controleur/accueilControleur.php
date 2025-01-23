<?php
function accueilControleur($twig) {
    echo $twig->render('index.twig', ['name' => 'NetTech']);
}

function contactControleur(){
    echo 'Contact';
   }

function maintenanceControleur(){
    echo 'maintenance';
}