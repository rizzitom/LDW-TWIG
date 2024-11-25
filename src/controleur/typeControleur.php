<?php
function typeControleur($twig, $db) {

    $types = $db->query("SELECT * FROM type ORDER BY nom")->fetchAll();


    if (isset($_POST['btAjouterType'])) {
        $nomType = $_POST['inputNomType'];
        $stmt = $db->prepare("INSERT INTO type (nom) VALUES (:nom)");
        $stmt->execute(['nom' => $nomType]);
        header("Location: index.php?page=type"); 
        exit;
    }

    echo $twig->render('type.html.twig', array('types' => $types));
}
?>
