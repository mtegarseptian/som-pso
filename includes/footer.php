        </div><!-- end container-fluid -->
    </div><!-- end page-content-wrapper -->
</div><!-- end wrapper -->

<script src="[code.jquery.com](https://code.jquery.com/jquery-3.7.1.min.js)"></script>
<script src="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js)"></script>
<script src="[cdn.datatables.net](https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js)"></script>
<script src="[cdn.datatables.net](https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js)"></script>
<script src="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js)"></script>
<script src="/som-pso/assets/js/charts.js"></script>
<script>
    $('#sidebarToggle').on('click', function() {
        $('#wrapper').toggleClass('toggled');
    });
    $(document).ready(function() {
        if ($('.datatable').length) {
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                },
                pageLength: 25
            });
        }
    });
</script>
</body>
</html>
