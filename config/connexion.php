<?php

function connect($config){
    try{
        // Check if PDO MySQL driver is available
        if (!in_array('mysql', PDO::getAvailableDrivers())) {
            throw new Exception("PDO MySQL n'est pas installé ou activé");
        }
        
        $db = new PDO('mysql:host='.$config['serveur'].';dbname='.$config['bd'],$config['login'],$config['password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }
    catch(Exception $e){
        die("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}
?>
