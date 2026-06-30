<?php
require_once '../includes/header.php';
require_once '../config/database.php';
$conn = getConnection();

// Analisis atribut per cluster produktivitas (berdasarkan hybrid results)
$attrs = ['N','P','K','temperature','humidity','ph','rainfall'];
$attrLabels = ['Nitrogen (N)','Phosphorus (P)','Potassium (K)','Temperature','Humidity','pH','Rainfall'];
$productivity = ['rendah','sedang','tinggi'];

$stats = [];

// ===============================
// Ambil statistik (mean & std) per atribut per cluster
// ===============================
foreach ($productivity as $prod) {
    foreach ($attrs as $attr) {
        $sql = "SELECT 
                    AVG(cd.$attr) as avg_val, 
                    STDDEV(cd.$attr) as std_val 
                FROM hybrid_results hr 
                JOIN crop_data cd ON cd.id = hr.data_id 
                WHERE hr.productivity_label = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $prod);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        $stats[$prod][$attr] = [
            'avg' => $row['avg_val'] ?? 0,
            'std' => $row['std_val'] ?? 0,
        ];
    }
}

// ===============================
// Normalisasi data untuk Radar Chart
// ===============================
$normalized = [];

foreach ($attrs as $attr) {

    $values = [];

    foreach ($productivity as $prod) {
        $values[] = floatval($stats[$prod][$attr]['avg']);
    }

    $min = min($values);
    $max = max($values);

    foreach ($productivity as $prod) {

        $value = floatval($stats[$prod][$attr]['avg']);

        if ($max == $min) {
            $normalized[$prod][] = 0;
        } else {
            $normalized[$prod][] = round(
                ($value - $min) / ($max - $min),
                3
            );
        }
    }
}

// Distribusi crop per cluster
$cropDist = [];
foreach ($productivity as $prod) {
    $stmt = $conn->prepare("SELECT cd.label, COUNT(*) as c FROM hybrid_results hr 
        JOIN crop_data cd ON cd.id = hr.data_id 
        WHERE hr.productivity_label = ? GROUP BY cd.label ORDER BY c DESC LIMIT 5");
    $stmt->bind_param("s", $prod);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $cropDist[$prod][] = $row;
    $stmt->close();
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
            <div style="height:320px; position:relative; overflow:hidden;">
                <canvas id="radarChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-bar-chart-steps me-2"></i>Rata-rata Nitrogen per Produktivitas
            </div>
            <div style="height:320px; position:relative; overflow:hidden;">
                <canvas id="nitrogenChart"></canvas>
            </div>
            <div class="alert alert-info mt-3 py-2 small mb-0">
                <i class="bi bi-lightbulb me-1"></i>
                Cluster produktivitas <strong>sedang</strong> memiliki median Nitrogen tertinggi, menunjukkan bahwa produktivitas tidak hanya ditentukan oleh satu atribut.
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
function initAnalysisCharts() {
    console.log("Mencoba merender grafik...");

    const statsData = {
        rendah: <?= json_encode(array_values($normalized['rendah'] ?? [0,0,0,0,0,0,0])) ?>,
        sedang: <?= json_encode(array_values($normalized['sedang'] ?? [0,0,0,0,0,0,0])) ?>,
        tinggi: <?= json_encode(array_values($normalized['tinggi'] ?? [0,0,0,0,0,0,0])) ?>
    };

    const nitrogenData = [
        <?= round($stats['rendah']['N']['avg'] ?? 0, 2) ?>,
        <?= round($stats['sedang']['N']['avg'] ?? 0, 2) ?>,
        <?= round($stats['tinggi']['N']['avg'] ?? 0, 2) ?>
    ];

    console.log("Data Nitrogen:", nitrogenData);

    // 1. Radar
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: <?= json_encode($attrLabels) ?>,
            datasets: [
                { label: 'Rendah', data: statsData.rendah, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)' },
                { label: 'Sedang', data: statsData.sedang, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.1)' },
                { label: 'Tinggi', data: statsData.tinggi, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // 2. Bar Nitrogen
    const nitroCtx = document.getElementById('nitrogenChart').getContext('2d');
    new Chart(nitroCtx, {
        type: 'bar',
        data: {
            labels: ['Rendah', 'Sedang', 'Tinggi'],
            datasets: [{
                label: 'Mean Nitrogen',
                data: nitrogenData,
                backgroundColor: ['#dc3545', '#fd7e14', '#198754']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

document.addEventListener("DOMContentLoaded", initAnalysisCharts);
</script>

<?php require_once '../includes/footer.php'; ?>