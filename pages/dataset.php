<?php
require_once '../includes/header.php';
require_once '../config/database.php';
$conn = getConnection();

// Handle import CSV
$importMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if ($file) {
        // 1. Matikan proteksi foreign key sementara
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        
        // 2. Kosongkan semua tabel riwayat yang bergantung pada dataset lama
        $conn->query("TRUNCATE TABLE crop_data_normalized");
        $conn->query("TRUNCATE TABLE som_results");
        $conn->query("TRUNCATE TABLE hybrid_results");
        
        // 3. Kosongkan tabel utama
        $conn->query("TRUNCATE TABLE crop_data");
        
        // 4. Nyalakan kembali proteksi foreign key
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        // 5. Proses baca dan masukkan data CSV (BAGIAN INI JANGAN DIHAPUS)
        $f = fopen($file, 'r');
        fgetcsv($f); // skip header
        $count = 0;
        while (($row = fgetcsv($f)) !== false) {
            if (count($row) < 8) continue;
            $stmt = $conn->prepare("INSERT INTO crop_data (N,P,K,temperature,humidity,ph,rainfall,label) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ddddddds", $row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7]);
            $stmt->execute();
            $count++;
        }
        fclose($f);
        $importMsg = "<div class='alert alert-success'><i class='bi bi-check-circle me-2'></i>Berhasil mengimpor <strong>$count</strong> data!</div>";
    }
}

// Statistik deskriptif
$stats = [];
foreach (['N','P','K','temperature','humidity','ph','rainfall'] as $col) {
    $r = $conn->query("SELECT MIN($col) as min, MAX($col) as max, AVG($col) as avg, 
                       STDDEV($col) as std FROM crop_data")->fetch_assoc();
    $stats[$col] = $r;
}
$totalData = $conn->query("SELECT COUNT(*) as c FROM crop_data")->fetch_assoc()['c'] ?? 0;
$totalLabel = $conn->query("SELECT COUNT(DISTINCT label) as c FROM crop_data")->fetch_assoc()['c'] ?? 0;

// Ambil 100 data untuk tabel
$rows = $conn->query("SELECT * FROM crop_data LIMIT 100");
$conn->close();
?>
<?php require_once '../includes/sidebar.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-table text-success me-2"></i>Dataset Crop Recommendation</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class="bi bi-upload me-1"></i>Import CSV
    </button>
</div>

<?= $importMsg ?>

<!-- Info cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center border-start border-success border-4">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-success"><?= number_format($totalData) ?></div>
                <div class="text-muted small">Total Data</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-start border-primary border-4">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-primary">7</div>
                <div class="text-muted small">Atribut Numerik</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-start border-warning border-4">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-warning"><?= $totalLabel ?></div>
                <div class="text-muted small">Jenis Tanaman</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center border-start border-danger border-4">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-danger">0</div>
                <div class="text-muted small">Missing Value</div>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Deskriptif -->
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <i class="bi bi-calculator me-2"></i>Statistik Deskriptif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Atribut</th>
                        <th>Min</th>
                        <th>Max</th>
                        <th>Mean</th>
                        <th>Std Dev</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $attrLabels = ['N'=>'Nitrogen (N)','P'=>'Phosphorus (P)','K'=>'Potassium (K)',
                        'temperature'=>'Temperature (°C)','humidity'=>'Humidity (%)','ph'=>'pH Tanah','rainfall'=>'Rainfall (mm)'];
                    foreach ($stats as $col => $s): ?>
                    <tr>
                        <td class="fw-semibold"><?= $attrLabels[$col] ?></td>
                        <td><?= round($s['min'] ?? 0, 2) ?></td>
                        <td><?= round($s['max'] ?? 0, 2) ?></td>
                        <td><?= round($s['avg'] ?? 0, 2) ?></td>
                        <td><?= round($s['std'] ?? 0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tabel Data -->
<div class="card">
    <div class="card-header bg-success text-white">
        <i class="bi bi-grid me-2"></i>Preview Data (100 baris pertama)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover datatable mb-0" style="font-size:0.82rem">
                <thead>
                    <tr>
                        <th>No</th><th>N</th><th>P</th><th>K</th>
                        <th>Temp (°C)</th><th>Humidity (%)</th><th>pH</th>
                        <th>Rainfall (mm)</th><th>Label</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; while ($row = $rows->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $row['N'] ?></td>
                        <td><?= $row['P'] ?></td>
                        <td><?= $row['K'] ?></td>
                        <td><?= round($row['temperature'],2) ?></td>
                        <td><?= round($row['humidity'],2) ?></td>
                        <td><?= round($row['ph'],2) ?></td>
                        <td><?= round($row['rainfall'],2) ?></td>
                        <td><span class="badge bg-success"><?= htmlspecialchars($row['label']) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Dataset CSV</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Format: <code>N,P,K,temperature,humidity,ph,rainfall,label</code>
                    </div>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i>Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
