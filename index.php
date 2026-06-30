<?php
require_once 'includes/header.php';
require_once 'config/database.php';

$conn = getConnection();

// Ambil statistik
$totalData = $conn->query("SELECT COUNT(*) as total FROM crop_data")->fetch_assoc()['total'] ?? 0;
$totalLabel = $conn->query("SELECT COUNT(DISTINCT label) as total FROM crop_data")->fetch_assoc()['total'] ?? 22;

$evalSOM    = $conn->query("SELECT * FROM evaluation_results WHERE method='SOM Standar' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$evalHybrid = $conn->query("SELECT * FROM evaluation_results WHERE method='Hybrid SOM-PSO' ORDER BY id DESC LIMIT 1")->fetch_assoc();

$psoResult  = $conn->query("SELECT * FROM pso_results ORDER BY id DESC LIMIT 1")->fetch_assoc();

// Distribusi label crop
$labelDist = $conn->query("SELECT label, COUNT(*) as jumlah FROM crop_data GROUP BY label ORDER BY jumlah DESC");
$labelData = [];
while ($row = $labelDist->fetch_assoc()) {
    $labelData[] = $row;
}

$conn->close();
?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-4 fw-bold"><?= number_format($totalData) ?></div>
                    <div class="small opacity-75">Total Data</div>
                </div>
                <i class="bi bi-database-fill fs-1 opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-4 fw-bold">7</div>
                    <div class="small opacity-75">Jumlah Atribut</div>
                </div>
                <i class="bi bi-list-columns fs-1 opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background:linear-gradient(135deg,#fd7e14,#e55a00)">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-4 fw-bold"><?= $evalHybrid['silhouette_score'] ?? '0.2623' ?></div>
                    <div class="small opacity-75">Silhouette Score (Hybrid)</div>
                </div>
                <i class="bi bi-graph-up fs-1 opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card" style="background:linear-gradient(135deg,#6f42c1,#4a2d8c)">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fs-4 fw-bold"><?= $evalHybrid['dbi_score'] ?? '1.3998' ?></div>
                    <div class="small opacity-75">DBI Score (Hybrid)</div>
                </div>
                <i class="bi bi-bar-chart fs-1 opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Perbandingan Evaluasi -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <i class="bi bi-bar-chart-line me-2"></i>Perbandingan Evaluasi
            </div>
            <div class="card-body">
                <canvas id="evalChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Parameter PSO Terbaik -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-stars me-2"></i>Parameter Optimal (PSO)
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-speedometer text-success me-2"></i>Learning Rate</td>
                            <td><span class="badge bg-success fs-6"><?= $psoResult['best_learning_rate'] ?? '0.0116' ?></span></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-broadcast text-primary me-2"></i>Sigma (Radius)</td>
                            <td><span class="badge bg-primary fs-6"><?= $psoResult['best_sigma'] ?? '2.4497' ?></span></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-arrow-repeat text-warning me-2"></i>Epoch</td>
                            <td><span class="badge bg-warning text-dark fs-6"><?= $psoResult['best_epoch'] ?? '1964' ?></span></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-trophy text-danger me-2"></i>Fitness Value</td>
                            <td><span class="badge bg-danger fs-6"><?= $psoResult['best_fitness'] ?? '0.2623' ?></span></td>
                        </tr>
                    </tbody>
                </table>
                <div class="alert alert-success mt-2 mb-0 py-2">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Peningkatan:</strong> Silhouette +73.7% | DBI -35.2% dari SOM standar
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Distribusi Tanaman -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-flower2 me-2"></i>Distribusi Jenis Tanaman dalam Dataset
            </div>
            <div class="card-body h-100">
                <canvas id="cropChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- Info Sistem -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <i class="bi bi-info-circle me-2"></i>Info Sistem
            </div>
            <div class="card-body">
                <div class="algorithm-step">
                    <div class="fw-bold text-success"><i class="bi bi-1-circle me-1"></i> Input Data</div>
                    <small>Crop Recommendation Dataset (2200 data, 7 atribut)</small>
                </div>
                <div class="algorithm-step">
                    <div class="fw-bold text-primary"><i class="bi bi-2-circle me-1"></i> Preprocessing</div>
                    <small>Normalisasi Min-Max semua atribut numerik</small>
                </div>
                <div class="algorithm-step">
                    <div class="fw-bold text-warning"><i class="bi bi-3-circle me-1"></i> SOM Standar</div>
                    <small>Clustering awal sebagai baseline perbandingan</small>
                </div>
                <div class="algorithm-step">
                    <div class="fw-bold text-danger"><i class="bi bi-4-circle me-1"></i> Optimasi PSO</div>
                    <small>Cari parameter SOM terbaik secara otomatis</small>
                </div>
                <div class="algorithm-step">
                    <div class="fw-bold" style="color:#6f42c1"><i class="bi bi-5-circle me-1"></i> Hybrid SOM-PSO</div>
                    <small>Clustering final dengan parameter optimal</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function renderCharts() {
    const labelNames = <?= json_encode(array_column($labelData, 'label')) ?>;
    const labelCounts = <?= json_encode(array_column($labelData, 'jumlah')) ?>;

    // Chart Evaluasi
    new Chart(document.getElementById('evalChart'), {
        type: 'bar',
        data: {
            labels: ['Silhouette Score', 'Davies-Bouldin Index'],
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
                    backgroundColor: 'rgba(25,135,84,0.85)',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Chart Distribusi Tanaman
    const colors = labelNames.map((_, i) => `hsl(${(i * 360 / labelNames.length)}, 65%, 55%)`);
        new Chart(document.getElementById('cropChart'), {
    type: 'bar',
    data: {
        labels: labelNames,
        datasets: [{
            label: 'Jumlah Data',
            data: labelCounts,
            backgroundColor: colors,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Wajib false agar mengikuti tinggi CSS
        plugins: { 
            legend: { display: false } 
        },
        scales: { 
            x: { 
                ticks: { 
                    maxRotation: 60, // Miringkan lebih tajam agar tidak numpuk
                    minRotation: 60,
                    font: { size: 11 } 
                } 
            },
            y: { 
                beginAtZero: true,
                grid: { drawBorder: false }
            } 
        }
    }
});
}
</script>

<?php require_once 'includes/footer.php'; ?>
