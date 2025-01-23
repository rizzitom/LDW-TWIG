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

    if (isset($_POST['btModifierType']) && isset($_POST['typeId'])) {
        $typeId = $_POST['typeId'];
        $nomType = $_POST['inputNomType'];
        $stmt = $db->prepare("UPDATE type SET nom = :nom WHERE id = :id");
        $stmt->execute(['nom' => $nomType, 'id' => $typeId]);
        header("Location: index.php?page=type");
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
        $idType = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM type WHERE id = :id");
        $stmt->execute(['id' => $idType]);
        header("Location: index.php?page=type");
        exit;
    }

    echo $twig->render('type.twig', array('types' => $types));
}

?>
