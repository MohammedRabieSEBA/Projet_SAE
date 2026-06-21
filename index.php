<?php

require_once 'db.php';

// Requête A : Top 10 des pays les plus visités
$sqlTop10 = "SELECT 
                d.nom_destination AS pays,
                d.nom_continent AS continent,
                SUM(v.lsn_pax) AS total_passagers
             FROM trafic_mensuel_volumes v
             JOIN liaisons l ON v.id_liaison = l.id_liaison
             JOIN destinations d ON l.nom_destination = d.nom_destination
             WHERE d.nom_destination NOT LIKE '\_%'
             GROUP BY d.nom_destination, d.nom_continent
             ORDER BY total_passagers DESC
             LIMIT 10";
$stmtTop10 = $pdo->query($sqlTop10);
$top10Pays = $stmtTop10->fetchAll();

// Requête B : Impact du Covid (2019, 2020, 2024)
$sqlCovid = "SELECT 
                LEFT(v.anmois, 4) AS annee,
                SUM(v.lsn_pax) AS total_passagers
             FROM trafic_mensuel_volumes v
             WHERE v.anmois LIKE '2019%' OR v.anmois LIKE '2020%' OR v.anmois LIKE '2024%'
             GROUP BY LEFT(v.anmois, 4)";
$stmtCovid = $pdo->query($sqlCovid);
$donneesCovid = $stmtCovid->fetchAll();

// --- PRÉPARATION DES DONNÉES POUR LE GRAPHIQUE ---
// Chart.js a besoin de deux listes : une pour les étiquettes (les années) et une pour les valeurs (les passagers)
$labelsCovid = [];
$valeursCovid = [];
foreach ($donneesCovid as $ligne) {
    $labelsCovid[] = $ligne['annee'];
    $valeursCovid[] = $ligne['total_passagers'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Aviation DGAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col">
            <h1 class="text-primary text-center">✈️ Observatoire du Trafic Aérien Français</h1>
            <p class="text-muted text-center">Analyse des données historiques (1990 - 2024)</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0">🏆 Top 10 des destinations historiques</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pays</th>
                                <th>Continent</th>
                                <th class="text-end">Passagers Totaux</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rang = 1;
                            foreach ($top10Pays as $ligne): 
                            ?>
                            <tr>
                                <td><?= $rang++ ?></td>
                                <td><strong><?= htmlspecialchars($ligne['pays']) ?></strong></td>
                                <td><?= htmlspecialchars($ligne['continent'] ?? 'N/A') ?></td>
                                <td class="text-end"><?= number_format($ligne['total_passagers'], 0, ',', ' ') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow-sm border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">📉 Impact du Covid-19 sur le trafic</h5>
                </div>
                <div class="card-body">
                    <canvas id="covidChart" width="400" height="300"></canvas>
                    
                    <div class="alert alert-warning mt-4 mb-0" role="alert">
                        <small>Comparaison du volume de passagers avant la pandémie (2019), pendant le crash mondial (2020) et la reprise actuelle (2024).</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    const ctx = document.getElementById('covidChart').getContext('2d');

    // injection data PHP directement dans le Javascript grâce à json_encode
    const labelsAnnees = <?= json_encode($labelsCovid) ?>;
    const dataPassagers = <?= json_encode($valeursCovid) ?>;

    // graphe
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labelsAnnees,
            datasets: [{
                label: 'Nombre de passagers',
                data: dataPassagers,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.6)', // Bleu pour 2019
                    'rgba(255, 99, 132, 0.8)', // Rouge pour 2020 (Covid)
                    'rgba(75, 192, 192, 0.6)'  // Vert pour 2024 (Reprise)
                ],
                borderColor: [
                    'rgb(54, 162, 235)',
                    'rgb(255, 99, 132)',
                    'rgb(75, 192, 192)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Volume de Passagers'
                    }
                }
            }
        }
    });
</script>

</body>
</html>