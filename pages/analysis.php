<?php
require_once '../includes/header.php';
require_once '../config/database.php';
$conn = getConnection();

// Analisis atribut per cluster produktivitas (berdasarkan hybrid results)
$attrs = ['N','P','K','temperature','humidity','ph','rainfall'];
$attrLabels = ['Nitrogen (N)','Phosphorus (P)','Potassium (K)','Temperature','Humidity','pH','Rainfall'];
$productivity = ['rendah','sedang','tinggi'];

$stats = [];
foreach ($productivity as $prod) {
    foreach ($attrs as $attr) {
        $r = $conn->query("SELECT AVG(cd.$attr) as avg, MIN(cd.$attr) as min, MAX(cd.$attr) as max, STDDEV(cd.$attr) as std
            FROM hybrid_results hr JOIN crop_data cd ON cd.id = hr.data_id
            WHERE hr.productivity_label = '$prod'")->fetch_assoc();
        $stats[$prod][$attr] = $r;
    }
}

// Distribusi crop per cluster
$cropDist = [];
foreach ($productivity as $prod) {
    $r = $conn->query("SELECT cd.label, COUNT(*) as c FROM hybrid_results hr 
        JOIN crop_data cd ON cd.id = hr.data_id 
        WHERE hr.productivity_label='$prod' GROUP BY cd.label ORDER BY c DESC LIMIT 5");
    while ($row = $r->fetch_assoc()) $cropDist[$prod][] = $row;
}
$conn->close();
?>
<?php require_once '../includes/sidebar.php'; ?>

<h4 class="fw-bold mb-3"><i class="bi bi-graph-up text-success me-2"></i>Analisis Atribut terhadap Produktivitas</h4>

<!-- Radar Chart -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <i class="bi bi-hexagon me-2"></i>Profil Rata-rata Atribut per Cluster (Normalisasi)
            </div>
            <div class="card-body">
                <canvas id="radarChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-bar-chart-steps me-2"></i>Rata-rata Nitrogen per Produktivitas
            </div>
            <div class="card-body">
                <canvas id="nitrogenChart" height="150"></canvas>
                <div class="alert alert-info mt-3 py-2 small mb-0">
                    <i class="bi bi-lightbulb me-1"></i>
                    Cluster produktivitas <strong>sedang</strong> memiliki median Nitrogen tertinggi, menunjukkan bahwa produktivitas tidak hanya ditentukan oleh satu atribut.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Statistik -->
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <i class="bi bi-table me-2"></i>Statistik Atribut per Cluster Produktivitas
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Atribut</th>
                        <th colspan="2" class="text-center text-danger">🔴 Rendah</th>
                        <th colspan="2" class="text-center text-warning">🟠 Sedang</th>
                        <th colspan="2" class="text-center text-success">🟢 Tinggi</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th class="text-center">Mean</th><th class="text-center">Std</th>
                        <th class="text-center">Mean</th><th class="text-center">Std</th>
                        <th class="text-center">Mean</th><th class="text-center">Std</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attrs as $i => $attr): ?>
                    <tr>
                        <td class="fw-semibold"><?= $attrLabels[$i] ?></td>
                        <?php foreach ($productivity as $prod):
                            $s = $stats[$prod][$attr] ?? []; ?>
                        <td class="text-center"><?= round($s['avg'] ?? 0, 2) ?></td>
                        <td class="text-center text-muted small">±<?= round($s['std'] ?? 0, 2) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Tanaman per Cluster -->
<div class="row g-3">
    <?php
    $clusterConfig = [
        'rendah' => ['color'=>'danger',  'label'=>'Rendah'],
        'sedang' => ['color'=>'warning', 'label'=>'Sedang'],
        'tinggi' => ['color'=>'success', 'label'=>'Tinggi'],
    ];
    foreach ($productivity as $prod):
        $cfg = $clusterConfig[$prod];
    ?>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-<?= $cfg['color'] ?> text-white">
                <i class="bi bi-flower2 me-2"></i>Top Tanaman - Produktivitas <?= $cfg['label'] ?>
            </div>
            <div class="card-body">
                <?php if (!empty($cropDist[$prod])): ?>
                    <?php foreach ($cropDist[$prod] as $crop): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold text-capitalize"><?= htmlspecialchars($crop['label']) ?></span>
                        <span class="badge bg-<?= $cfg['color'] ?>"><?= $crop['c'] ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">Jalankan clustering Hybrid SOM-PSO terlebih dahulu.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
// Radar chart
const radarLabels = <?= json_encode($attrLabels) ?>;
<?php
// Normalisasi nilai untuk radar (min-max dari semua cluster)
$radarData = [];
foreach ($productivity as $prod) {
    $vals = [];
    foreach ($attrs as $attr) {
        $vals[] = round($stats[$prod][$attr]['avg'] ?? 0, 2);
    }
    $radarData[$prod] = $vals;
}
// Normalize per attr untuk radar
$normalized = [];
foreach ($attrs as $i => $attr) {
    $allVals = array_map(fn($p) => $radarData[$p][$i], $productivity);
    $min = min($allVals); $max = max($allVals);
    foreach ($productivity as $prod) {
        $normalized[$prod][$i] = $max > $min ? round(($radarData[$prod][$i] - $min) / ($max - $min), 3) : 0.5;
    }
}
?>
new Chart(document.getElementById('radarChart'), {
    type: 'radar',
    data: {
        labels: radarLabels,
        datasets: [
            {
                label: 'Rendah',
                data: <?= json_encode(array_values($normalized['rendah'])) ?>,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220,53,69,0.1)',
                pointBackgroundColor: '#dc3545'
            },
            {
                label: 'Sedang',
                data: <?= json_encode(array_values($normalized['sedang'])) ?>,
                borderColor: '#fd7e14',
                backgroundColor: 'rgba(253,126,20,0.1)',
                pointBackgroundColor: '#fd7e14'
            },
            {
                label: 'Tinggi',
                data: <?= json_encode(array_values($normalized['tinggi'])) ?>,
                borderColor: '#198754',
                backgroundColor: 'rgba(25,135,84,0.1)',
                pointBackgroundColor: '#198754'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { r: { min: 0, max: 1, ticks: { stepSize: 0.2 } } }
    }
});

// Nitrogen chart
new Chart(document.getElementById('nitrogenChart'), {
    type: 'bar',
    data: {
        labels: ['Rendah', 'Sedang', 'Tinggi'],
        datasets: [{
            label: 'Rata-rata Nitrogen (N)',
            data: [
                <?= round($stats['rendah']['N']['avg'] ?? 0, 2) ?>,
                <?= round($stats['sedang']['N']['avg'] ?? 0, 2) ?>,
                <?= round($stats['tinggi']['N']['avg'] ?? 0, 2) ?>
            ],
            backgroundColor: ['rgba(220,53,69,0.8)','rgba(253,126,20,0.8)','rgba(25,135,84,0.8)'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
