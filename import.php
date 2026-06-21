<?php

require_once 'db.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

echo "--- IMPORT START --- <br>\n";

$dir = 'dataset/';
$files = glob($dir . 'ASP_LSN_*.csv');

if (empty($files)) {
    die("Error: file not found in '$dir'.<br>\n");
}

sort($files);

// Preparation de requetes
$stmtContinent   = $pdo->prepare("INSERT IGNORE INTO continents (nom_continent) VALUES (:nom)");
$stmtDestination = $pdo->prepare("INSERT IGNORE INTO destinations (nom_destination, nom_continent) VALUES (:dest, :cont)");
$stmtFaisceau    = $pdo->prepare("INSERT IGNORE INTO faisceaux (code_fsc, segment) VALUES (:fsc, :seg)");
$stmtPeriode     = $pdo->prepare("INSERT IGNORE INTO periodes (anmois) VALUES (:anmois)");

$stmtInsertLiaison = $pdo->prepare("INSERT INTO liaisons (point_origine, nom_destination, code_fsc) VALUES (:orig, :dest, :fsc)");


$stmtVolume = $pdo->prepare("INSERT INTO trafic_mensuel_volumes (id_liaison, anmois, lsn_pax, lsn_frp, lsn_drt, lsn_peq) 
                             VALUES (:id_l, :anmois, :pax, :frp, :drt, :peq) 
                             ON DUPLICATE KEY UPDATE lsn_pax = :pax_up, lsn_frp = :frp_up, lsn_drt = :drt_up, lsn_peq = :peq_up");

$stmtPerf   = $pdo->prepare("INSERT INTO trafic_performances_km (id_liaison, anmois, lsn_pkt, lsn_tkt, lsn_peqkt) 
                             VALUES (:id_l, :anmois, :pkt, :tkt, :peqkt) 
                             ON DUPLICATE KEY UPDATE lsn_pkt = :pkt_up, lsn_tkt = :tkt_up, lsn_peqkt = :peqkt_up");

// Chargement liaisons
$liaisonsCache = [];
$existingLiaisons = $pdo->query("SELECT id_liaison, point_origine, nom_destination FROM liaisons")->fetchAll();
foreach ($existingLiaisons as $l) {
    $key = $l['point_origine'] . '->' . $l['nom_destination'];
    $liaisonsCache[$key] = $l['id_liaison'];
}

function cleanDecimal($val) {
    if ($val === '' || $val === null) return 0.0;
    $val = str_replace(' ', '', $val);
    $val = str_replace(',', '.', $val);
    return (double)$val;
}

function cleanInt($val) {
    if ($val === '' || $val === null) return 0;
    $val = str_replace(' ', '', $val);
    return (int)$val;
}

// file per year
foreach ($files as $file) {
    echo "Lecture de : $file ... ";
    flush();

    if (($handle = fopen($file, "r")) !== FALSE) {
        
        fgetcsv($handle, 0, ";"); // entete ignoré
        
        $pdo->beginTransaction();
        $rowCount = 0;

        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
            // ligne vide ignoré
            if (empty($data) || !isset($data[0]) || empty($data[0])) continue;
            // du faite que dans les annee 1990 les colonnes sont moin nombreuses que dans les annee plus recente, 
            // on force la ligne à contenir au moin 13 colonnes
            $data = array_pad($data, 13, '');

            // extraction des valeurs
            $anmois      = cleanInt($data[0]);
            $segment     = ($data[1] !== '') ? $data[1] : 'INCONNU';
            $code_fsc    = ($data[2] !== '') ? $data[2] : 'INCONNU';
            $origine     = ($data[3] !== '') ? $data[3] : 'INCONNU';
            $destination = ($data[4] !== '') ? $data[4] : 'INCONNU';
            $continent   = ($data[5] !== '') ? $data[5] : null;

            /// format colonne detection
            // si fichier ancien moin de 13 colonnes 
            if (empty($data[10]) && empty($data[11]) && empty($data[12])) {
                $lsn_peq     = cleanInt($data[6]);
                $lsn_pax     = cleanInt($data[7]);
                $lsn_frp     = cleanDecimal($data[8]);
                $lsn_drt     = cleanInt($data[9]);
                

                $lsn_peqkt   = 0.0;
                $lsn_pkt     = 0.0;
                $lsn_tkt     = 0.0;
            } else {
              
                $lsn_peq     = cleanInt($data[6]);
                $lsn_peqkt   = cleanDecimal($data[7]);
                $lsn_pax     = cleanInt($data[8]);
                $lsn_pkt     = cleanDecimal($data[9]);
                $lsn_frp     = cleanDecimal($data[10]);
                $lsn_tkt     = cleanDecimal($data[11]);
                $lsn_drt     = cleanInt($data[12]);
            }

            
            if ($continent !== null) {
                $stmtContinent->execute([':nom' => $continent]);
            }
            $stmtDestination->execute([':dest' => $destination, ':cont' => $continent]);
            $stmtFaisceau->execute([':fsc' => $code_fsc, ':seg' => $segment]);
            $stmtPeriode->execute([':anmois' => $anmois]);

            // Résolution de l'identifiant de la liaison
            $liaisonKey = $origine . '->' . $destination;
            if (!isset($liaisonsCache[$liaisonKey])) {
                $stmtInsertLiaison->execute([
                    ':orig' => $origine,
                    ':dest' => $destination,
                    ':fsc'  => $code_fsc
                ]);
                $id_liaison = $pdo->lastInsertId();
                $liaisonsCache[$liaisonKey] = $id_liaison;
            } else {
                $id_liaison = $liaisonsCache[$liaisonKey];
            }


            $stmtVolume->execute([
                ':id_l'     => (int)$id_liaison,
                ':anmois'   => (int)$anmois,
                ':pax'      => (int)$lsn_pax,
                ':frp'      => (double)$lsn_frp,
                ':drt'      => (int)$lsn_drt,
                ':peq'      => (int)$lsn_peq,
                ':pax_up'   => (int)$lsn_pax,
                ':frp_up'   => (double)$lsn_frp,
                ':drt_up'   => (int)$lsn_drt,
                ':peq_up'   => (int)$lsn_peq
            ]);

            
            $stmtPerf->execute([
                ':id_l'     => (int)$id_liaison,
                ':anmois'   => (int)$anmois,
                ':pkt'      => (double)$lsn_pkt,
                ':tkt'      => (double)$lsn_tkt,
                ':peqkt'    => (double)$lsn_peqkt,
                ':pkt_up'   => (double)$lsn_pkt,
                ':tkt_up'   => (double)$lsn_tkt,
                ':peqkt_up' => (double)$lsn_peqkt
            ]);

            $rowCount++;
        }
        
        fclose($handle);
        $pdo->commit();
        echo "Succès ($rowCount lignes importées).<br>\n";
    } else {
        echo "Impossible d'ouvrir le fichier.<br>\n";
    }
}

echo "OPR TERMINE <br>\n";