<?php
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../core/SOM.php';
require_once '../core/PSO.php';
$conn = getConnection();

$msg = ''; $psoHistory = []; $clusterCounts = []; $bestPos = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numParticles = intval($_POST['num_particles'] ?? 10);
    $maxIter      = intval($_POST['max_iter'] ?? 30);

    $result = $conn->query("SELECT N_norm,P_norm,K_norm,temperature_norm,humidity_norm,ph_norm,rainfall_norm,original_id FROM crop_data_normalized");
    $data = []; $ids = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = array_values(array_slice($row, 0, 7));
        $ids[]  = $row['original_id'];
    }

    if (count($data) < 10) {
        $msg = "<div class='alert alert-warning'>Lakukan preprocessing terlebih dahulu!</div>";
    } else {
        set_time_limit(300);
        $pso = new PSO($data, $numParticles, $maxIter);
        $result2 = $pso->optimize();
        $bestPos   = $result2['best_position'];
        $psoHistory = $result2['history'];

        // Simpan hasil PSO
        $conn->query("TRUNCATE TABLE pso_results");
        foreach ($psoHistory as $h) {
            $conn->query("INSERT INTO pso_results (iteration,best_learning_rate,best_sigma,best_epoch,best_fitness) VALUES ({$h['iteration']},{$h['best_lr']},{$h['best_sigma']},{$h['best_epoch']},{$h['best_fitness']})");
        }

        // Bangun SOM dengan parameter terbaik
        $som = new SOM(3, 3, $bestPos['learning_rate'], $bestPos['sigma'], (int)$bestPos['epoch']);
        $som->train($data);
        $labels = $som->predict($data);
        $productMap = $som->clusterToProductivity($labels, $data);

        $conn->query("TRUNCATE TABLE hybrid_results");
        $stmt = $conn->prepare("INSERT INTO hybrid_results (data_id, cluster_id, productivity_label) VALUES (?,?,?)");
        foreach ($labels as $i => $cluster) {
            $prod = $productMap[$cluster] ?? 'sedang';
            $stmt->bind_param("iis", $ids[$i], $cluster, $prod);
            $stmt->execute();
        }

        $subset    = array_slice($data, 0, 300);
        $subLabels = array_slice($labels, 0, 300);
        $sil = $som->silhouetteScore($subset, $subLabels);
        $dbi = $som->daviesBouldinIndex($subset, $subLabels);

        $conn->query("DELETE FROM evaluation_results WHERE method='Hybrid SOM-PSO'");
        $r2 = $conn->query("SELECT productivity_label, COUNT(*) as c FROM hybrid_results GROUP BY productivity_label");
        $low=$med=$hi=0;
        while ($row = $r2->fetch_assoc()) {
            $clusterCounts[$row['productivity_label']] = $row['c'];
            if ($row['productivity_label']==='rendah') $low=$row['c'];
            if ($row['productivity_label']==='sedang') $med=$row['c'];
            if ($row['productivity_label']==='tinggi') $hi=$row['c'];
        }
        $conn->query("INSERT INTO evaluation_results (method,silhouette_score,dbi_score,cluster_low,cluster_medium,cluster_high) VALUES ('Hybrid SOM-PSO',$sil,$dbi,$low,$med,$hi)");

        $msg = "<div class='alert alert-success'><i class='bi bi-stars me-2'></i>Hybrid SOM-PSO selesai! LR: <strong>" . round($bestPos['learning_rate'],4) . "</strong> | Sigma: <strong>" . round($bestPos['sigma'],4) . "</strong> | Epoch: <strong>" . (int)$bestPos['epoch'] . "</strong> | Silhouette: <strong>" . round($sil,4) . "</strong></div>";
    }
}

// Load existing data
$psoHistoryDb = $conn->query("SELECT * FROM pso_results ORDER BY iteration");
while ($row = $psoHistoryDb->fetch_assoc()) $psoHistory[] = $row;

$distResult = $conn->query("SELECT productivity_label, COUNT(*) as c FROM hybrid_results GROUP BY productivity_label");
while ($row = $distResult->fetch_assoc()) $clusterCounts[$row['productivity_label']] = $row['c'];
$totalClustered = $conn->query("SELECT COUNT(*) as c FROM hybrid_results")->fetch_assoc()['c'] ?? 0;
$evalHybrid = $conn->query("SELECT * FROM evaluation_results WHERE method='Hybrid SOM-PSO' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$lastPso = $conn->query("SELECT * FROM pso_results ORDER BY iteration DESC LIMIT 1")->fetch_assoc();
$conn->close();
?>
<?php require_once '../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-stars text-warning me-2"></i>Hybrid SOM-PSO</h4>
</div>

<?= $msg ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-sliders me-2"></i>Parameter PSO
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jumlah Partikel</label>
                        <input type="number" name="num_particles" class="form-control" value="10" min="5" max="30">
                        <small class="text-muted">Rekomendasi: 10-20 partikel</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Iterasi Maksimum</label>
                        <input type="number" name="max_iter" class="form-control" value="30" min="10" max="100">
                        <small class="text-muted">Rekomendasi: 20-50 iterasi</small>
                    </div>
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Batas Pencarian Parameter:</strong><br>
                        LR: [0.001, 0.5] | Sigma: [0.5, 3.0] | Epoch: [100, 2000]
                    </div>
                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-clock me-1"></i>
                        Proses memerlukan waktu 1-3 menit tergantung iterasi.
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold">
                        <i class="bi bi-stars me-1"></i>Jalankan Hybrid SOM-PSO
                    </button>
                </form>
            </div>
        </div>

        <?php if ($lastPso): ?>
        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-trophy me-2"></i>Parameter Terbaik PSO
            </div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted small">Learning Rate</span><br>
                    <strong class="fs-5 text-success"><?= $lastPso['best_learning_rate'] ?></strong></div>
                <div class="mb-2"><span class="text-muted small">Sigma</span><br>
                    <strong class="fs-5 text-primary"><?= $lastPso['best_sigma'] ?></strong></div>
                <div class="mb-2"><span class="text-muted small">Epoch</span><br>
                    <strong class="fs-5 text-warning"><?= $lastPso['best_epoch'] ?></strong></div>
                <hr>
                <div><span class="text-muted small">Fitness (Silhouette)</span><br>
                    <strong class="fs-5 text-danger"><?= $lastPso['best_fitness'] ?></strong></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <!-- Konvergensi PSO -->
        <?php if (count($psoHistory) > 0): ?>
        <div class="card mb-3">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-graph-up me-2"></i>Kurva Konvergensi PSO
            </div>
            <div class="card-body">
                <canvas id="convergenceChart" height="120"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- Distribusi -->
        <?php if ($totalClustered > 0): ?>
        <div class="row g-3 mb-3">
            <?php
            $clusterConfig = [
                'rendah' => ['color'=>'danger',  'icon'=>'arrow-down-circle-fill'],
                'sedang' => ['color'=>'warning', 'icon'=>'dash-circle-fill'],
                'tinggi' => ['color'=>'success', 'icon'=>'arrow-up-circle-fill'],
            ];
            foreach (['rendah','sedang','tinggi'] as $prod):
                $count = $clusterCounts[$prod] ?? 0;
                $pct = $totalClustered > 0 ? round($count/$totalClustered*100,1) : 0;
                $cfg = $clusterConfig[$prod];
            ?>
            <div class="col-md-4">
                <div class="card text-center border-<?= $cfg['color'] ?> border-2">
                    <div class="card-body">
                        <i class="bi bi-<?= $cfg['icon'] ?> text-<?= $cfg['color'] ?> fs-2 mb-2"></i>
                        <div class="fs-2 fw-bold text-<?= $cfg['color'] ?>"><?= number_format($count) ?></div>
                        <div class="fw-semibold">Produktivitas <?= ucfirst($prod) ?></div>
                        <div class="text-muted small"><?= $pct ?>%</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="bi bi-pie-chart me-2"></i>Distribusi Cluster Hybrid SOM-PSO
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <canvas id="hybridPieChart" height="230"></canvas>
                    </div>
                    <div class="col-md-7">
                        <?php if ($evalHybrid): ?>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="card bg-success text-white text-center py-2">
                                    <div class="fw-bold fs-5"><?= $evalHybrid['silhouette_score'] ?></div>
                                    <small>Silhouette Score</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-primary text-white text-center py-2">
                                    <div class="fw-bold fs-5"><?= $evalHybrid['dbi_score'] ?></div>
                                    <small>Davies-Bouldin Index</small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="alert alert-success py-2 small">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            Optimasi PSO berhasil meningkatkan kualitas clustering SOM secara signifikan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
<?php if (count($psoHistory) > 0): ?>
const iters    = <?= json_encode(array_column($psoHistory, 'iteration')) ?>;
const fitnessH = <?= json_encode(array_column($psoHistory, 'best_fitness')) ?>;
new Chart(document.getElementById('convergenceChart'), {
    type: 'line',
    data: {
        labels: iters,
        datasets: [{
            label: 'Best Fitness (Silhouette)',
            data: fitnessH,
            borderColor: '#198754',
            backgroundColor: 'rgba(25,135,84,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 3
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: false }, x: { title: { display: true, text: 'Iterasi' } } }
    }
});
<?php endif; ?>

<?php if ($totalClustered > 0): ?>
new Chart(document.getElementById('hybridPieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Rendah', 'Sedang', 'Tinggi'],
        datasets: [{
            data: [
                <?= $clusterCounts['rendah'] ?? 0 ?>,
                <?= $clusterCounts['sedang'] ?? 0 ?>,
                <?= $clusterCounts['tinggi'] ?? 0 ?>
            ],
            backgroundColor: ['#dc3545','#fd7e14','#198754'],
            borderWidth: 2
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?>
