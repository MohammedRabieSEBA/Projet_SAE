# ✈️ Observatoire du Trafic Aérien Commercial Français (1990 - 2024)

## 📌 Présentation du Projet
Ce projet a été réalisé dans le cadre de la SAÉ (Structure de Données et Bases de Données). Il consiste à concevoir et développer une application web dynamique (Full-Stack) permettant d'importer, de stocker, de requêter et de visualiser **34 ans de données historiques** de l'aviation civile française issues des publications officielles de la **DGAC** (Direction Générale de l'Aviation Civile).

L'application traite un volume massif de plus de **43 000 lignes de données mensuelles**, gère les évolutions de formats de fichiers sur trois décennies, et propose un tableau de bord analytique interactif mettant en valeur des faits historiques marquants (comme l'impact de la pandémie de Covid-19 sur le trafic aérien).

---

## 🛠️ Technologies Utilisées
* **Back-end :** PHP 8.x (Architecture scriptée propre, requêtes préparées avec l'API **PDO**)
* **Base de données :** MySQL (Modèle relationnel optimisé, gestion des contraintes d'intégrité)
* **Front-end :** HTML5 / CSS3, Framework **Bootstrap 5.3** (Interface responsive et moderne)
* **Visualisation :** **Chart.js** (Graphiques dynamiques et interactifs côté client)

---

## 📐 Schéma Relationnel & Cardinalités
La base de données `aviation_dgac` a été normalisée afin d'éviter toute redondance d'information et de garantir l'intégrité référentielle des données à travers des relations complexes (1:1, 1:N, N:N).

### Rendu Graphique (Généré via Mermaid)

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