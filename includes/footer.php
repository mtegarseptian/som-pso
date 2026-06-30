</div></div></div><script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script src="/som-pso/assets/js/charts.js"></script>

<script>
    $('#sidebarToggle').on('click', function() {
        $('#wrapper').toggleClass('toggled');
    });

    $(document).ready(function() {
        // Inisialisasi DataTables
        if ($('.datatable').length) {
            $('.datatable').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json' },
                pageLength: 25
            });
        }
        
        // PENTING: Memanggil fungsi rendering jika ada di halaman tersebut
        if (typeof renderCharts === 'function') {
            renderCharts();
        }
    });
</script>
</body>
</html>