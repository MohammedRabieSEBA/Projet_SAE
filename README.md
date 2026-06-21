# ✈️ Observatoire du Trafic Aérien Commercial Français (1990 - 2024)

## 📌 Présentation Générale
Ce projet a été réalisé dans le cadre de la SAÉ (Structure de Données et Bases de Données). Il consiste à concevoir et développer une application web Full-Stack permettant d'importer, de stocker de manière pérenne, de requêter et de visualiser **34 ans de données historiques** de l'aviation civile française issues des publications officielles de la **DGAC**.

---

## 🎯 Partie I : Réponses aux exigences du cahier des charges

Cette section détaille la manière dont chaque problématique technique de la SAÉ a été traitée et résolue au sein du projet.

### Exigence 1 : Modélisation et conception de la Base de Données
Le premier défi consistait à transformer des fichiers plats (CSV) en une base de données relationnelle normalisée (`aviation_dgac`). L'objectif est d'éviter la redondance et de garantir l'intégrité des données sur le long terme.

**Schéma Relationnel (Généré via Mermaid) :**

```mermaid
erDiagram
    %% Relations 1..n
    continents ||--o{ destinations : "1..n (Un continent regroupe plusieurs pays)"
    destinations ||--o{ liaisons : "1..n (Un pays reçoit plusieurs liaisons)"
    faisceaux ||--o{ liaisons : "1..n (Un faisceau caractérise plusieurs liaisons)"
    
    periodes ||--o{ trafic_mensuel_volumes : "1..n (Un mois concerne plusieurs volumes)"
    liaisons ||--o{ trafic_mensuel_volumes : "1..n (Une liaison génère plusieurs volumes)"

    %% Relations 1..1
    trafic_mensuel_volumes ||--|| trafic_performances_km : "1..1 (Même liaison + Même mois = 1 seule perf)"

    continents {
        string nom_continent PK
    }
    destinations {
        string nom_destination PK
        string nom_continent FK
    }
    faisceaux {
        string code_fsc PK
        string segment
    }
    periodes {
        int anmois PK
    }
    liaisons {
        int id_liaison PK
        string point_origine
        string nom_destination FK
        string code_fsc FK
    }
    trafic_mensuel_volumes {
        int id_liaison PK_FK
        int anmois PK_FK
        int lsn_pax
        double lsn_frp
        int lsn_drt
        int lsn_peq
    }
    trafic_performances_km {
        int id_liaison PK_FK
        int anmois PK_FK
        double lsn_pkt
        double lsn_tkt
        double lsn_peqkt
    }

### Justification des Cardinalités complexes :
* **Relation 1..N (`continents` ➔ `destinations`) :** Un continent contient plusieurs pays de destination, mais un pays n'appartient qu'à un seul et unique continent.
* **Relation 1..1 (`trafic_mensuel_volumes` ➔ `trafic_performances_km`) :** Pour un couple unique (ex: Paris-Madrid en Janvier 2024), il existe exactement une ligne de volume (passagers/fret) et au maximum une ligne de performances associée. La clé primaire est donc composite (`id_liaison`, `anmois`).

### Exigence 2 : Importation massive et gestion des données hétérogènes (ETL)
Le fichier `import.php` a été conçu comme un pipeline **ETL (Extract, Transform, Load)** capable de traiter plus de 43 000 lignes issues de 34 fichiers CSV différents.
* **Transformation (Gestion de l'hétérogénéité) :** Les fichiers historiques de la DGAC ont évolué (10 colonnes en 1990 contre 13 colonnes en 2024). Le script utilise la fonction native `array_pad($data, 13, '')` pour normaliser toutes les lignes sur la structure la plus récente avant l'insertion.
* **Performances de Chargement :** L'insertion de dizaines de milliers de lignes ligne par ligne saturerait le serveur. Le script enveloppe les requêtes dans une **Transaction PDO** (`$pdo->beginTransaction()` et `$pdo->commit()`), réduisant le temps d'importation à quelques secondes.

### Exigence 3 : Restitution dynamique et visualisation (Front-end)
Le fichier `index.php` propose un tableau de bord analytique interactif, codé sans rechargement lourd en JavaScript, en privilégiant l'intelligence côté serveur (PHP) et les standards HTML5.
* **Requêtes SQL Dynamiques :** Le tableau de bord réagit aux variables `$_GET` passées dans l'URL pour adapter les requêtes SQL (Top 10 et évolution mensuelle) en temps réel avec des clauses `WHERE` et `LIKE` conditionnelles.
* **Autocomplétion performante :** La recherche de destination utilise la balise HTML5 `<datalist>` peuplée dynamiquement depuis la base de données, offrant une autocomplétion fluide à l'utilisateur lors de la frappe.
* **Visualisation de données :** Intégration de la bibliothèque **Chart.js** pour générer des graphiques comparatifs mettant en évidence les chocs historiques (ex: effondrement du trafic lors de la pandémie de Covid-19 en 2020).

### Exigence 4 : Sécurité de l'application et gestion des erreurs
L'application respecte les standards de sécurité fondamentaux du développement web.
* **Injections SQL :** Toutes les variables issues de l'utilisateur sont injectées dans la base de données via l'API **PDO** (requêtes préparées avec `prepare()` et `bindValue()`).
* **Failles XSS (Cross-Site Scripting) :** Toutes les chaînes de caractères affichées dans les vues HTML sont échappées avec `htmlspecialchars()`.
* **Résilience (Try / Catch) :** L'intégralité de la logique d'accès aux données est sécurisée par un bloc `try { ... } catch (PDOException)`. Si le serveur MySQL tombe en panne, l'application n'affiche pas d'erreurs fatales brutes, mais un composant d'alerte propre signalant le problème à l'utilisateur.

### Exigence 5 : Étude comparative - Modèle SQL (Relationnel) vs NoSQL (Document)
Dans l'optique d'une montée en charge vers du Big Data, une étude architecturale a été menée pour comparer notre implémentation actuelle (MySQL) avec une solution orientée Document (MongoDB).

| Critère technique | Modèle SQL (Ex: MySQL) | Modèle NoSQL (Ex: MongoDB) |
| :--- | :--- | :--- |
| **Stockage** | Structure tabulaire stricte (Lignes / Colonnes). | Documents BSON/JSON flexibles. |
| **Relations** | Multiples jointures (`JOIN`) via des clés étrangères. | Imbrication directe des données associées. |
| **Requêtage** | Langage SQL, puissant pour les agrégations complexes (`GROUP BY`). | Langage de requêtes orienté objet / pipelines. |
| **Scalabilité** | **Verticale** : Augmentation des ressources matérielles du serveur. | **Horizontale** : Distribution native sur plusieurs nœuds (*Sharding*). |

**Conclusion de l'étude :**
Pour le périmètre de la SAÉ (34 ans de données mensuelles consolidées), le modèle **SQL** reste le choix optimal car il garantit l'intégrité référentielle (normes ACID) et simplifie la génération de statistiques transversales. En revanche, si la DGAC souhaitait stocker les événements de télémétrie de chaque vol individuel à la seconde près, le volume de données nécessiterait un passage à **MongoDB**, permettant d'éviter le goulot d'étranglement des jointures complexes et de distribuer le stockage horizontalement.

## 🛠️ Partie II : Stack Technologique
* **Serveur & Logique :** PHP 8.x (Architecture procédurale propre, PDO).
* **Persistance des données :** MySQL.
* **Interface Utilisateur :** HTML5, CSS3, Framework **Bootstrap 5.3**.
* **Graphiques :** Chart.js.

## 📦 Partie III : Guide de déploiement en local

1. **Cloner ou extraire le projet** dans le dossier public de votre serveur web (`htdocs` sous XAMPP, `www` sous MAMP/WAMP).
2. **Configuration de la Base de Données :**
   * Ouvrez votre gestionnaire (phpMyAdmin).
   * Créez une base de données vide nommée `aviation_dgac`.
   * Exécutez le script SQL fourni (`schema.sql`) pour instancier les tables et leurs contraintes.
3. **Préparation du Dataset :**
   * Assurez-vous que l'ensemble des fichiers CSV historiques (1990 à 2024) est bien présent dans le répertoire `dataset/` à la racine du projet.
4. **Exécution du pipeline d'importation :**
   * Démarrez vos services Apache et MySQL.
   * Accédez depuis votre navigateur à : `http://localhost/chemin_du_projet/import.php` pour lancer le traitement ETL. L'opération prendra quelques secondes.
5. **Lancement de l'application :**
   * Une fois l'importation validée, accédez à `http://localhost/chemin_du_projet/index.php` pour exploiter le tableau de bord interactif.