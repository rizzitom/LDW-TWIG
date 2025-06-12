# Projet LDW-TWIG

Ce projet est un site de boutique en ligne développée avec PHP, le moteur de templates Twig et une base de données MySQL.

---

## 🚀 Installation et mise en route

### Prérequis

- **PHP** (7.4 ou version supérieure recommandé)
- **MySQL**
- Un serveur web local (Apache, Nginx…)

### 1. Mise en place de la base de données

1. Vérifiez que MySQL fonctionne sur votre machine.
2. Placez-vous à la racine du projet dans un terminal et exécutez :

3. php init_db.php

Ce script :
- Se connecte au serveur MySQL
- Crée la base de données si elle n’existe pas
- Crée toutes les tables nécessaires avec des données d’exemple

### 2. Configuration de PHP

1. Ouvrez votre fichier `php.ini` (par exemple `C:\php\php.ini` sur Windows ou `/etc/php/7.x/cli/php.ini` sur Linux).
2. Vérifiez que ces lignes ne sont pas commentées (pas de point-virgule `;` au début) :

extension=pdo_mysql
extension=mysqli


3. Redémarrez votre serveur web pour que les modifications soient prises en compte.

---

## 🛠️ Utilisation

1. Lancez votre serveur web (Apache ou Nginx).
2. Accédez au dossier `public/` du projet via votre navigateur

## 🎯 Comptes de test

Utiliser un compte administrateur existant dans la base de données :
- Email : admin@ledesignduweb.com
- Mot de passe : admin123


4. ## 📜 Licence

Ce projet est protégé par le droit d’auteur.  
**Toute réutilisation, modification, distribution ou reproduction, totale ou partielle, du code ou de ses éléments est strictement interdite sans autorisation expresse préalable de l’auteur.**

