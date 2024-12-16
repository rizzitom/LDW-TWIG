<?php
function utilisateurModifControleur($twig, $db){
    $form = array();
    
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $utilisateur = new Utilisateur($db);
        
        $unUtilisateur = $utilisateur->selectById($_GET['id']);
        
        if ($unUtilisateur != null) {
            $form['utilisateur'] = $unUtilisateur;
            $form['roles'] = $utilisateur->getAllRoles(); 
        } else {
            $form['message'] = 'Utilisateur incorrect';
        }
    } else {
        $form['message'] = 'Utilisateur non précisé';
    }
    
    echo $twig->render('utilisateur-modif.html.twig', array('form' => $form));
}
?>
