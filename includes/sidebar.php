<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="bg-dark border-end" id="sidebar-wrapper">
        <div class="sidebar-heading text-white p-3 border-bottom border-secondary">
            <i class="bi bi-flower1 text-success fs-4 me-2"></i>
            <span class="fw-bold">SOM-PSO</span>
            <small class="d-block text-muted" style="font-size:11px">Analisis Produktivitas</small>
        </div>
        <div class="list-group list-group-flush">
            <a href="/som-pso/index.php" class="list-group-item list-group-item-action bg-dark text-white <?= ($currentPage=='index.php')?'active':'' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a href="/som-pso/pages/dataset.php" class="list-group-item list-group-item-action bg-dark text-white <?= ($currentPage=='dataset.php')?'active':'' ?>">
                <i class="bi bi-table me-2"></i> Dataset
            </a>
            <a href="/som-pso/pages/preprocessing.php" class="list-group-item list-group-item-action bg-dark text-white <?= ($currentPage=='preprocessing.php')?'active':'' ?>">
                <i class="bi bi-funnel me-2"></i> Preprocessing
            </a>
            <div class="sidebar-divider text-muted px-3 py-1" style="font-size:11px">CLUSTERING</div>
            <a href="/som-pso/pages/clustering_som.php" class="list-group-item list-group-item-action bg-dark text-white <?= ($currentPage=='clustering_som.php')?'active':'' ?>">
                <i class="bi bi-diagram-3 me-2"></i> SOM Standar
            </a>
            <a href="/som-pso/pages/clustering_hybrid.php" class="list-group-item list-group-item-action bg-dark text-white <?= ($currentPage=='clustering_hybrid.php')?'active':'' ?>">
                <i class="bi bi-stars me-2"></i> Hybrid SOM-PSO
            </a>
            <div class="sidebar-divider text-muted px-3 py-1" style="font-size:11px">EVALUASI</div>
            <a href="/som-pso/pages/comparison.php" class="list-group-item list-group-item-action bg-dark text-white <?= ($currentPage=='comparison.php')?'active':'' ?>">
                <i class="bi bi-bar-chart-line me-2"></i> Perbandingan
            </a>
            <a href="/som-pso/pages/analysis.php" class="list-group-item list-group-item-action bg-dark text-white <?= ($currentPage=='analysis.php')?'active':'' ?>">
                <i class="bi bi-graph-up me-2"></i> Analisis Atribut
            </a>
        </div>
    </div>
    <!-- Page Content -->
    <div id="page-content-wrapper" class="w-100">
        <nav class="navbar navbar-expand-lg navbar-dark bg-success border-bottom">
            <div class="container-fluid">
                <button class="btn btn-sm btn-outline-light" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand ms-3 fw-bold">
                    Sistem Analisis Produktivitas Pertanian Berbasis Hybrid SOM-PSO
                </span>
                <span class="text-light small">Kelompok 5 | Universitas Pakuan</span>
            </div>
        </nav>
        <div class="container-fluid p-4">
