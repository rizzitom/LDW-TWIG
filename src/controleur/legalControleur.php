<?php

function legalControleur($twig, $db, $page) {
    $entreprise = [
        'nom' => 'LE DESIGN DU WEB',
        'siren' => '948 362 355',
        'siret' => '948 362 355 00026',
        'adresse' => '',
        'email' => 'contact@ledesignduweb.com',
        'telephone' => '06 22 51 66 29'
    ];
    
    switch ($page) {
        case 'cgv':
            echo $twig->render('cgv.twig', ['entreprise' => $entreprise]);
            break;
            
        case 'politique-de-confidentialite':
            echo $twig->render('politique-de-confidentialite.twig', ['entreprise' => $entreprise]);
            break;
            
        case 'mentions-legales':
            echo $twig->render('mentions-legales.twig', ['entreprise' => $entreprise]);
            break;
            
        case 'cookies':
            echo $twig->render('cookies.twig', ['entreprise' => $entreprise]);
            break;
            
        default:
            header('Location: index.php');
            exit;
    }
}
