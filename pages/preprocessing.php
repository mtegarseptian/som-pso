<?php
require_once '../includes/header.php';
require_once '../config/database.php';
$conn = getConnection();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil semua data
    $result = $conn->query("SELECT * FROM crop_data");
    $allData = $result->fetch_all(MYSQLI_ASSOC);

    if (count($allData) === 0) {
        $msg = "<div class='alert alert-warning'>Belum ada data. Import dataset terlebih dahulu.</div>";
    } else {
        $attrs = ['N','P','K','temperature','humidity','ph','rainfall'];
        $mins = []; $maxs = [];
        foreach ($attrs as $a) {
            $mins[$a] = min(array_column($allData, $a));
            $maxs[$a] = max(array_column($allData, $a));
        }

        $conn->query("TRUNCATE TABLE crop_data_normalized");
        $stmt = $conn->prepare("INSERT INTO crop_data_normalized (original_id,N_norm,P_norm,K_norm,temperature_norm,humidity_norm,ph_norm,rainfall_norm) VALUES (?,?,?,?,?,?,?,?)");
        foreach ($allData as $row) {
            $norms = [];
            foreach ($attrs as $a) {
                $range = $maxs[$a] - $mins[$a];
                $norms[] = $range > 0 ? ($row[$a] - $mins[$a]) / $range : 0;
            }
            $stmt->bind_param("iddddddd", $row['id'], ...$norms);
            $stmt->execute();
        }
        $msg = "<div class='alert alert-success'><i class='bi bi-check-circle me-2'></i>Normalisasi berhasil! <strong>" . count($allData) . "</strong> data dinormalisasi.</div>";
    }
}

$normCount = $conn->query("SELECT COUNT(*) as c FROM crop_data_normalized")->fetch_assoc()['c'] ?? 0;
$normRows  = $conn->query("SELECT cd.N, cd.P, cd.K, cd.temperature, cd.humidity, cd.ph, cd.rainfall, cd.label,
    cdn.N_norm, cdn.P_norm, cdn.K_norm, cdn.temperature_norm, cdn.humidity_norm, cdn.ph_norm, cdn.rainfall_norm
    FROM crop_data_normalized cdn 
    JOIN crop_data cd ON cd.id = cdn.original_id 
    LIMIT 50");
$conn->close();
?>
<?php require_once '../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-funnel text-success me-2"></i>Preprocessing Data</h4>
    <form method="POST">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-play-fill me-1"></i>Jalankan Normalisasi Min-Max
        </button>
    </form>
</div>

<?= $msg ?>

<!-- Penjelasan -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-book me-2"></i>Tentang Normalisasi Min-Max
            </div>
            <div class="card-body">
                <p>Normalisasi Min-Max mengubah nilai setiap atribut ke rentang <strong>[0, 1]</strong> menggunakan rumus:</p>
                <div class="bg-light rounded p-3 text-center mb-3">
                    <code class="fs-5">X' = (X - X<sub>min</sub>) / (X<sub>max</sub> - X<sub>min</sub>)</code>
                </div>
                <p class="mb-0 text-muted small">Normalisasi diperlukan karena SOM menggunakan perhitungan jarak Euclidean. Atribut dengan skala besar (seperti Rainfall: ~200-300) akan mendominasi atribut skala kecil (seperti pH: ~4-9) tanpa normalisasi.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body d-flex flex-column justify-content-center text-center">
                <i class="bi bi-check-circle-fill fs-1 mb-2"></i>
                <div class="fs-2 fw-bold"><?= number_format($normCount) ?></div>
                <div>Data Ternormalisasi</div>
                <div class="mt-2 opacity-75 small">Tidak ada missing value &amp; duplikasi</div>
            </div>
        </div>
    </div>
</div>

<!-- Min-Max per Atribut -->
<?php if ($normCount > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <i class="bi bi-arrow-left-right me-2"></i>Perbandingan Sebelum &amp; Sesudah Normalisasi (50 baris pertama)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover datatable mb-0" style="font-size:0.78rem">
                <thead>
                    <tr class="table-success">
                        <th colspan="8" class="text-center">Data Asli</th>
                        <th colspan="7" class="text-center">Data Ternormalisasi</th>
                    </tr>
                    <tr>
                        <th>N</th><th>P</th><th>K</th><th>Temp</th><th>Hum</th><th>pH</th><th>Rain</th><th>Label</th>
                        <th>N'</th><th>P'</th><th>K'</th><th>Temp'</th><th>Hum'</th><th>pH'</th><th>Rain'</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $normRows->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['N'] ?></td>
                        <td><?= $row['P'] ?></td>
                        <td><?= $row['K'] ?></td>
                        <td><?= round($row['temperature'],1) ?></td>
                        <td><?= round($row['humidity'],1) ?></td>
                        <td><?= round($row['ph'],2) ?></td>
                        <td><?= round($row['rainfall'],1) ?></td>
                        <td><span class="badge bg-success"><?= $row['label'] ?></span></td>
                        <td class="text-primary"><?= round($row['N_norm'],4) ?></td>
                        <td class="text-primary"><?= round($row['P_norm'],4) ?></td>
                        <td class="text-primary"><?= round($row['K_norm'],4) ?></td>
                        <td class="text-primary"><?= round($row['temperature_norm'],4) ?></td>
                        <td class="text-primary"><?= round($row['humidity_norm'],4) ?></td>
                        <td class="text-primary"><?= round($row['ph_norm'],4) ?></td>
                        <td class="text-primary"><?= round($row['rainfall_norm'],4) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
