-- 1. Table des continents
CREATE TABLE continents (
    nom_continent VARCHAR(50) PRIMARY KEY
);

-- 2. Table des destinations (Relation 1-N avec continents)
CREATE TABLE destinations (
    nom_destination VARCHAR(100) PRIMARY KEY,
    nom_continent VARCHAR(50),
    FOREIGN KEY (nom_continent) REFERENCES continents(nom_continent) ON DELETE SET NULL
);

-- 3. Table des faisceaux de trafic
CREATE TABLE faisceaux (
    code_fsc VARCHAR(20) PRIMARY KEY,
    segment VARCHAR(10) -- NAT/INTL
);

-- 4. Table des liaisons aériennes
CREATE TABLE liaisons (
    id_liaison INT AUTO_INCREMENT PRIMARY KEY,
    point_origine VARCHAR(50) NOT NULL, -- LSN_1 (PARIS, METROPOLE, PROVINCE...)
    nom_destination VARCHAR(100) NOT NULL, -- LSN_2 (PAYS ou PROVINCE...)
    code_fsc VARCHAR(20),
    FOREIGN KEY (nom_destination) REFERENCES destinations(nom_destination),
    FOREIGN KEY (code_fsc) REFERENCES faisceaux(code_fsc)
);

-- 5. Table des périodes temporelles
CREATE TABLE periodes (
    anmois INT PRIMARY KEY -- Format AAAAMM (ex: 202401)
);

-- 6. Table intermédiaire de jointure N-N (Volumes de trafic mensuels)
CREATE TABLE trafic_mensuel_volumes (
    id_liaison INT,
    anmois INT,
    lsn_pax INT DEFAULT 0,       -- Passagers locaux
    lsn_frp DOUBLE DEFAULT 0.0,   -- Fret et poste (en tonnes)
    lsn_drt INT DEFAULT 0,       -- Nombre de vols directs
    lsn_peq INT DEFAULT 0,       -- Passagers équivalents
    PRIMARY KEY (id_liaison, anmois),
    FOREIGN KEY (id_liaison) REFERENCES liaisons(id_liaison) ON DELETE CASCADE,
    FOREIGN KEY (anmois) REFERENCES periodes(anmois) ON DELETE CASCADE
);

-- 7. Table de performance (Relation 1-1 avec trafic_mensuel_volumes)
CREATE TABLE trafic_performances_km (
    id_liaison INT,
    anmois INT,
    lsn_pkt DOUBLE DEFAULT 0.0,   -- Passagers kilomètres
    lsn_tkt DOUBLE DEFAULT 0.0,   -- Tonnes kilomètres transportées
    lsn_peqkt DOUBLE DEFAULT 0.0, -- Passagers équivalents kilomètres
    PRIMARY KEY (id_liaison, anmois),
    FOREIGN KEY (id_liaison, anmois) REFERENCES trafic_mensuel_volumes(id_liaison, anmois) ON DELETE CASCADE
);