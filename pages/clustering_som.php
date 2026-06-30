<?php
require_once '../includes/header.php';
require_once '../config/database.php';
require_once '../core/SOM.php';
$conn = getConnection();

$msg = '';
$somParam = $conn->query("SELECT * FROM som_parameters ORDER BY id DESC LIMIT 1")->fetch_assoc();
$clusterCounts = []; $evalResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lr    = floatval($_POST['learning_rate'] ?? 0.5);
    $sigma = floatval($_POST['sigma'] ?? 1.0);
    $epoch = intval($_POST['epoch'] ?? 100);
    $gridX = intval($_POST['grid_x'] ?? 5);
    $gridY = intval($_POST['grid_y'] ?? 5);

    // Ambil data ternormalisasi
    $result = $conn->query("SELECT N_norm,P_norm,K_norm,temperature_norm,humidity_norm,ph_norm,rainfall_norm,original_id FROM crop_data_normalized");
    $data = []; $ids = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = array_values(array_slice($row, 0, 7));
        $ids[]  = $row['original_id'];
    }

    if (count($data) < 10) {
        $msg = "<div class='alert alert-warning'>Lakukan preprocessing terlebih dahulu!</div>";
    } else {
        $som = new SOM($gridX, $gridY, $lr, $sigma, $epoch);
        $som->train($data);
        $labels = $som->predict($data);

        // Map ke produktivitas
        $productMap = $som->clusterToProductivity($labels, $data);

        $conn->query("TRUNCATE TABLE som_results");
        $stmt = $conn->prepare("INSERT INTO som_results (data_id, cluster_id, productivity_label) VALUES (?,?,?)");
        foreach ($labels as $i => $cluster) {
            $prod = $productMap[$cluster] ?? 'sedang';
            $stmt->bind_param("iis", $ids[$i], $cluster, $prod);
            $stmt->execute();
        }

        // Hitung evaluasi pada subset
        $subset = array_slice($data, 0, 300);
        $subLabels = array_slice($labels, 0, 300);
        $sil = $som->silhouetteScore($subset, $subLabels);
        $dbi = $som->daviesBouldinIndex($subset, $subLabels);

        // Update parameter
        $conn->query("UPDATE som_parameters SET silhouette_score=$sil, dbi_score=$dbi WHERE id=" . ($somParam['id'] ?? 1));

        // Hitung distribusi produktivitas
        $r = $conn->query("SELECT productivity_label, COUNT(*) as c FROM som_results GROUP BY productivity_label");
        while ($row = $r->fetch_assoc()) {
            $clusterCounts[$row['productivity_label']] = $row['c'];
        }

        // Update evaluasi
        $low = $clusterCounts['rendah'] ?? 0;
        $med = $clusterCounts['sedang'] ?? 0;
        $hi  = $clusterCounts['tinggi'] ?? 0;
        $conn->query("DELETE FROM evaluation_results WHERE method='SOM Standar'");
        $conn->query("INSERT INTO evaluation_results (method,silhouette_score,dbi_score,cluster_low,cluster_medium,cluster_high) VALUES ('SOM Standar',$sil,$dbi,$low,$med,$hi)");

        $evalResult = ['silhouette' => round($sil, 4), 'dbi' => round($dbi, 4)];
        $msg = "<div class='alert alert-success'><i class='bi bi-check-circle me-2'></i>Clustering SOM selesai! Silhouette: <strong>{$evalResult['silhouette']}</strong> | DBI: <strong>{$evalResult['dbi']}</strong></div>";
    }
}

// Ambil data distribusi
$distResult = $conn->query("SELECT productivity_label, COUNT(*) as c FROM som_results GROUP BY productivity_label");
while ($row = $distResult->fetch_assoc()) {
    $clusterCounts[$row['productivity_label']] = $row['c'];
}
$evalSOM = $conn->query("SELECT * FROM evaluation_results WHERE method='SOM Standar' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$totalClustered = $conn->query("SELECT COUNT(*) as c FROM som_results")->fetch_assoc()['c'] ?? 0;
$conn->close();
?>
<?php require_once '../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-diagram-3 text-success me-2"></i>Clustering SOM Standar</h4>
</div>

<?= $msg ?>

<div class="row g-3">
    <!-- Form Parameter -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-sliders me-2"></i>Parameter SOM
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Learning Rate</label>
                        <input type="number" name="learning_rate" class="form-control" 
                            value="<?= $somParam['learning_rate'] ?? 0.5 ?>" step="0.01" min="0.001" max="1.0" required>
                        <small class="text-muted">Laju pembelajaran (0.001 - 1.0)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sigma (Radius Neighborhood)</label>
                        <input type="number" name="sigma" class="form-control" 
                            value="<?= $somParam['sigma'] ?? 1.0 ?>" step="0.1" min="0.1" max="5.0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Epoch</label>
                        <input type="number" name="epoch" class="form-control" 
                            value="<?= $somParam['epoch'] ?? 100 ?>" min="10" max="5000" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Grid X</label>
                            <input type="number" name="grid_x" class="form-control" value="<?= $somParam['grid_x'] ?? 5 ?>" min="2" max="10">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Grid Y</label>
                            <input type="number" name="grid_y" class="form-control" value="<?= $somParam['grid_y'] ?? 5 ?>" min="2" max="10">
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 py-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Proses clustering memerlukan waktu beberapa detik.
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-play-fill me-1"></i>Jalankan SOM
                    </button>
                </form>
            </div>
        </div>

        <?php if ($evalSOM): ?>
        <div class="card mt-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-bar-chart me-2"></i>Hasil Evaluasi
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Silhouette Score</span>
                    <span class="badge bg-primary fs-6"><?= $evalSOM['silhouette_score'] ?></span>
                </div>
                <div class="progress mb-3" style="height:8px">
                    <div class="progress-bar bg-primary" style="width:<?= min(100, $evalSOM['silhouette_score']*100) ?>%"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Davies-Bouldin Index</span>
                    <span class="badge bg-warning text-dark fs-6"><?= $evalSOM['dbi_score'] ?></span>
                </div>
                <small class="text-muted">Semakin rendah DBI = semakin baik</small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Hasil Clustering -->
    <div class="col-lg-8">
        <?php if ($totalClustered > 0): ?>
        <div class="row g-3 mb-3">
            <?php
            $clusterConfig = [
                'rendah'  => ['color'=>'danger',  'icon'=>'arrow-down-circle-fill', 'desc'=>'Lahan dengan kondisi kurang optimal'],
                'sedang'  => ['color'=>'warning',  'icon'=>'dash-circle-fill',       'desc'=>'Lahan dengan kondisi cukup baik'],
                'tinggi'  => ['color'=>'success', 'icon'=>'arrow-up-circle-fill',   'desc'=>'Lahan dengan kondisi sangat optimal'],
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
                        <div class="fw-semibold text-capitalize">Produktivitas <?= ucfirst($prod) ?></div>
                        <div class="text-muted small"><?= $pct ?>% dari total data</div>
                        <div class="progress mt-2" style="height:6px">
                            <div class="progress-bar bg-<?= $cfg['color'] ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <small class="text-muted" style="font-size:0.72rem"><?= $cfg['desc'] ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-pie-chart me-2"></i>Distribusi Cluster Produktivitas
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="somPieChart" height="250"></canvas>
                    </div>
                    <div class="col-md-6 d-flex flex-column justify-content-center">
                        <h6 class="fw-bold mb-3">Interpretasi Cluster SOM Standar</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><span class="badge bg-danger me-2">Rendah</span><?= $clusterCounts['rendah'] ?? 0 ?> data — kondisi lahan kurang optimal</li>
                            <li class="mb-2"><span class="badge bg-warning text-dark me-2">Sedang</span><?= $clusterCounts['sedang'] ?? 0 ?> data — kondisi lahan cukup baik</li>
                            <li class="mb-2"><span class="badge bg-success me-2">Tinggi</span><?= $clusterCounts['tinggi'] ?? 0 ?> data — kondisi lahan sangat optimal</li>
                        </ul>
                        <div class="alert alert-warning py-2 small mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Silhouette Score masih rendah karena parameter belum dioptimalkan.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        new Chart(document.getElementById('somPieChart'), {
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
        </script>

        <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-diagram-3 text-muted fs-1 mb-3"></i>
                <p class="text-muted">Belum ada hasil clustering. Atur parameter dan jalankan SOM.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
