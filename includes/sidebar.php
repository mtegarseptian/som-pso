<?php
$baseUrl = '/som-pso';
?>
<div class="d-flex" id="wrapper">
    <div class="border-end modern-sidebar" id="sidebar-wrapper">
        <div class="sidebar-heading p-4 border-bottom border-secondary border-opacity-10">
            <div class="d-flex align-items-center">
                <div class="icon-brand shadow-sm me-3">
                    <i class="bi bi-cpu-fill text-success fs-4"></i>
                </div>
                <div>
                    <span class="fw-bolder fs-5 tracking-wide text-black" style="color: #000000 !important;">SOM-PSO</span>
                    <small class="d-block text-muted" style="font-size:0.75rem; font-weight:600;">AI PRODUCTIVITY</small>
                </div>
            </div>
        </div>
        <div class="list-group list-group-flush mt-3 px-2">
            <a href="<?= $baseUrl ?>/index.php" class="list-group-item list-group-item-action <?= ($currentPage=='index.php')?'active':'' ?>">
                <i class="bi bi-grid-1x2-fill me-3"></i> Dashboard
            </a>
            <a href="<?= $baseUrl ?>/pages/dataset.php" class="list-group-item list-group-item-action <?= ($currentPage=='dataset.php')?'active':'' ?>">
                <i class="bi bi-database-fill me-3"></i> Dataset Data
            </a>
            <a href="<?= $baseUrl ?>/pages/preprocessing.php" class="list-group-item list-group-item-action <?= ($currentPage=='preprocessing.php')?'active':'' ?>">
                <i class="bi bi-funnel-fill me-3"></i> Preprocessing
            </a>
            <div class="sidebar-divider">Mekanisme Clustering</div>
            <a href="<?= $baseUrl ?>/pages/clustering_som.php" class="list-group-item list-group-item-action <?= ($currentPage=='clustering_som.php')?'active':'' ?>">
                <i class="bi bi-diagram-3-fill me-3"></i> SOM Standar
            </a>
            <a href="<?= $baseUrl ?>/pages/clustering_hybrid.php" class="list-group-item list-group-item-action <?= ($currentPage=='clustering_hybrid.php')?'active':'' ?>">
                <i class="bi bi-stars text-warning me-3"></i> Hybrid SOM-PSO
            </a>
            <div class="sidebar-divider">Hasil Analisis</div>
            <a href="<?= $baseUrl ?>/pages/comparison.php" class="list-group-item list-group-item-action <?= ($currentPage=='comparison.php')?'active':'' ?>">
                <i class="bi bi-bar-chart-fill me-3"></i> Perbandingan
            </a>
            <a href="<?= $baseUrl ?>/pages/analysis.php" class="list-group-item list-group-item-action <?= ($currentPage=='analysis.php')?'active':'' ?>">
                <i class="bi bi-pie-chart-fill me-3"></i> Analisis Atribut
            </a>
        </div>
    </div>
    
    <div id="page-content-wrapper" class="w-100 bg-light-modern">
        <nav class="navbar navbar-expand-lg modern-navbar border-bottom px-3 py-3">
            <div class="container-fluid">
                <button class="btn btn-light shadow-sm rounded-circle d-flex align-items-center justify-content-center p-2" id="sidebarToggle" style="width:40px; height:40px;">
                    <i class="bi bi-list fs-5 text-dark"></i>
                </button>
                <div class="d-flex flex-column ms-3">
                    <span class="fw-bold text-dark fs-5">Sistem Analisis Produktivitas Pertanian</span>
                    <span class="text-muted small fw-medium">Kelompok 5 | Universitas Pakuan</span>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4 container-animate">