<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOM-PSO | Analisis Produktivitas Pertanian</title>
    <link rel="stylesheet" href="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css)">
    <link rel="stylesheet" href="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css)">
    <link rel="stylesheet" href="[cdn.datatables.net](https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css)">
    <link rel="stylesheet" href="/som-pso/assets/css/custom.css">
</head>
<body>
