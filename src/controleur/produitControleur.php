<?php
function produitControleur($twig, $db) {

    $types = $db->query("SELECT * FROM type ORDER BY nom")->fetchAll();

    if (isset($_POST['btAjouterProduit'])) {
        $designation = $_POST['inputDesignation'];
        $description = $_POST['inputDescription'];
        $prix = $_POST['inputPrix'];
        $idType = $_POST['selectType'];

        $stmt = $db->prepare("INSERT INTO produit (designation, description, prix, idType) VALUES (:designation, :description, :prix, :idType)");
        $stmt->execute([
            'designation' => $designation,
            'description' => $description,
            'prix' => $prix,
            'idType' => $idType,
        ]);
        header("Location: index.php?page=produit");
        exit;
    }

    echo $twig->render('produit.html.twig', array('types' => $types));
}
?>
