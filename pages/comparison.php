<?php
require_once '../includes/header.php';
require_once '../config/database.php';
$conn = getConnection();

$evalSOM    = $conn->query("SELECT * FROM evaluation_results WHERE method='SOM Standar' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$evalHybrid = $conn->query("SELECT * FROM evaluation_results WHERE method='Hybrid SOM-PSO' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$somParam   = $conn->query("SELECT * FROM som_parameters ORDER BY id DESC LIMIT 1")->fetch_assoc();
$psoResult  = $conn->query("SELECT * FROM pso_results ORDER BY iteration DESC LIMIT 1")->fetch_assoc();
$conn->close();

$silDiff = 0; $dbiDiff = 0;
if ($evalSOM && $evalHybrid) {
    $silDiff = round((($evalHybrid['silhouette_score'] - $evalSOM['silhouette_score']) / max(0.001, $evalSOM['silhouette_score'])) * 100, 1);
    $dbiDiff = round((($evalSOM['dbi_score'] - $evalHybrid['dbi_score']) / max(0.001, $evalSOM['dbi_score'])) * 100, 1);
}
?>
<?php require_once '../includes/sidebar.php'; ?>

<h4 class="fw-bold mb-3"><i class="bi bi-bar-chart-line text-success me-2"></i>Perbandingan SOM Standar vs Hybrid SOM-PSO</h4>

<!-- Peningkatan -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <i class="bi bi-arrow-up-circle-fill fs-2 mb-2"></i>
                <div class="fs-3 fw-bold">+<?= $silDiff ?>%</div>
                <div>Peningkatan Silhouette Score</div>
                <small class="opacity-75"><?= $evalSOM['silhouette_score'] ?? '-' ?> → <?= $evalHybrid['silhouette_score'] ?? '-' ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <i class="bi bi-arrow-down-circle-fill fs-2 mb-2"></i>
                <div class="fs-3 fw-bold">-<?= $dbiDiff ?>%</div>
                <div>Penurunan Davies-Bouldin Index</div>
                <small class="opacity-75"><?= $evalSOM['dbi_score'] ?? '-' ?> → <?= $evalHybrid['dbi_score'] ?? '-' ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Tabel Perbandingan -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-table me-2"></i>Tabel Perbandingan Lengkap
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Aspek</th>
                            <th class="text-center">SOM Standar</th>
                            <th class="text-center">Hybrid SOM-PSO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Silhouette Score</td>
                            <td class="text-center"><span class="badge bg-secondary fs-6"><?= $evalSOM['silhouette_score'] ?? '-' ?></span></td>
                            <td class="text-center"><span class="badge bg-success fs-6"><?= $evalHybrid['silhouette_score'] ?? '-' ?></span></td>
                        </tr>
                        <tr>
                            <td>Davies-Bouldin Index</td>
                            <td class="text-center"><span class="badge bg-danger fs-6"><?= $evalSOM['dbi_score'] ?? '-' ?></span></td>
                            <td class="text-center"><span class="badge bg-primary fs-6"><?= $evalHybrid['dbi_score'] ?? '-' ?></span></td>
                        </tr>
                        <tr>
                            <td>Learning Rate</td>
                            <td class="text-center"><?= $somParam['learning_rate'] ?? '0.5' ?></td>
                            <td class="text-center"><strong class="text-success"><?= $psoResult['best_learning_rate'] ?? '0.0116' ?></strong></td>
                        </tr>
                        <tr>
                            <td>Sigma</td>
                            <td class="text-center"><?= $somParam['sigma'] ?? '1.0' ?></td>
                            <td class="text-center"><strong class="text-success"><?= $psoResult['best_sigma'] ?? '2.4497' ?></strong></td>
                        </tr>
                        <tr>
                            <td>Epoch</td>
                            <td class="text-center"><?= $somParam['epoch'] ?? '100' ?></td>
                            <td class="text-center"><strong class="text-success"><?= $psoResult['best_epoch'] ?? '1964' ?></strong></td>
                        </tr>
                        <tr>
                            <td>Cluster Rendah</td>
                            <td class="text-center"><span class="badge badge-rendah"><?= $evalSOM['cluster_low'] ?? '-' ?></span></td>
                            <td class="text-center"><span class="badge badge-rendah"><?= $evalHybrid['cluster_low'] ?? '-' ?></span></td>
                        </tr>
                        <tr>
                            <td>Cluster Sedang</td>
                            <td class="text-center"><span class="badge badge-sedang"><?= $evalSOM['cluster_medium'] ?? '-' ?></span></td>
                            <td class="text-center"><span class="badge badge-sedang"><?= $evalHybrid['cluster_medium'] ?? '-' ?></span></td>
                        </tr>
                        <tr>
                            <td>Cluster Tinggi</td>
                            <td class="text-center"><span class="badge badge-tinggi"><?= $evalHybrid['cluster_high'] ?? '-' ?></span></td>
                            <td class="text-center"><span class="badge badge-tinggi"><?= $evalHybrid['cluster_high'] ?? '-' ?></span></td>
                        </tr>
                        <tr class="table-success fw-bold">
                            <td>Kesimpulan</td>
                            <td class="text-center text-muted">Baseline</td>
                            <td class="text-center text-success"><i class="bi bi-trophy-fill me-1"></i>Lebih Baik</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Chart Perbandingan -->
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-bar-chart me-2"></i>Grafik Metrik Evaluasi
            </div>
            <div class="card-body">
                <canvas id="compChart" height="200"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-pie-chart me-2"></i>Distribusi Cluster (Hybrid SOM-PSO)
            </div>
            <div class="card-body">
                <canvas id="hybridDistChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Kesimpulan -->
<div class="card">
    <div class="card-header bg-success text-white fw-bold">
        <i class="bi bi-check-circle-fill me-2"></i>Kesimpulan Perbandingan
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="fw-bold text-success">✅ Hybrid SOM-PSO Lebih Baik Karena:</h6>
                <ul class="mb-0">
                    <li>Silhouette Score lebih tinggi (+<?= $silDiff ?>%) → pemisahan cluster lebih jelas</li>
                    <li>DBI lebih rendah (-<?= $dbiDiff ?>%) → cluster lebih kompak dan terpisah</li>
                    <li>Parameter optimal ditemukan secara otomatis oleh PSO</li>
                    <li>Tidak memerlukan trial-and-error manual</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold text-primary">📌 Parameter Optimal PSO:</h6>
                <ul class="mb-0">
                    <li>Learning Rate: <strong><?= $psoResult['best_learning_rate'] ?? '0.0116' ?></strong> (lebih kecil = lebih presisi)</li>
                    <li>Sigma: <strong><?= $psoResult['best_sigma'] ?? '2.4497' ?></strong> (radius neighborhood optimal)</li>
                    <li>Epoch: <strong><?= $psoResult['best_epoch'] ?? '1964' ?></strong> (lebih banyak iterasi pelatihan)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function renderCharts() {
    // Grafik Perbandingan Metrik (Silhouette & DBI)
    new Chart(document.getElementById('compChart'), {
        type: 'bar',
        data: {
            labels: ['Silhouette Score ↑', 'Davies-Bouldin Index ↓'],
            datasets: [
                {
                    label: 'SOM Standar',
                    data: [<?= $evalSOM['silhouette_score'] ?? 0.151 ?>, <?= $evalSOM['dbi_score'] ?? 2.1715 ?>],
                    backgroundColor: 'rgba(108,117,125,0.8)',
                    borderRadius: 6
                },
                {
                    label: 'Hybrid SOM-PSO',
                    data: [<?= $evalHybrid['silhouette_score'] ?? 0.2623 ?>, <?= $evalHybrid['dbi_score'] ?? 1.3998 ?>],
                    backgroundColor: 'rgba(25,135,84,0.9)',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Grafik Distribusi Cluster
    new Chart(document.getElementById('hybridDistChart'), {
        type: 'bar',
        data: {
            labels: ['Rendah', 'Sedang', 'Tinggi'],
            datasets: [
                {
                    label: 'SOM Standar',
                    data: [<?= $evalSOM['cluster_low'] ?? 720 ?>, <?= $evalSOM['cluster_medium'] ?? 800 ?>, <?= $evalSOM['cluster_high'] ?? 680 ?>],
                    backgroundColor: 'rgba(108,117,125,0.7)',
                    borderRadius: 4
                },
                {
                    label: 'Hybrid SOM-PSO',
                    data: [<?= $evalHybrid['cluster_low'] ?? 710 ?>, <?= $evalHybrid['cluster_medium'] ?? 820 ?>, <?= $evalHybrid['cluster_high'] ?? 670 ?>],
                    backgroundColor: ['rgba(220,53,69,0.8)','rgba(253,126,20,0.8)','rgba(25,135,84,0.8)'],
                    borderRadius: 4
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { legend: { position: 'top' } }, 
            scales: { y: { beginAtZero: true } } 
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
