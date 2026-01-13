<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '') | {{env('APP_NAME')}}</title>
    @include('partials.style')
    @yield('style')
</head>

<body class="hold-transition sidebar-mini sidebar-collapse">
    <div class="wrapper">

        <!-- Preloader -->
        <div class="preloader flex-column justify-content-center align-items-center">
            {{-- <img class="animation__shake" src="{{ asset('assets') }}/dist/img/logo.png" alt="DealDuluLogo"
                height="60" width="60"> --}}
        </div>

        <!-- Navbar -->
        @include('partials.navbar')
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="index3.html" class="brand-link">
                {{-- <img src="{{ asset('assets') }}/dist/img/logo.png" alt="SIP Logo"
                    class="brand-image img-circle elevation-3" style="opacity: .8"> --}}
                <span class="brand-text font-weight-light">SIP</span>
            </a>

            <!-- Sidebar -->
            @include('partials.sidebar')
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            @include('partials.header_content')
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    @if (session()->has('error'))
                        <div class="alert alert-{{ session()->get('error') ? 'danger' : 'success' }} alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-{{ session()->get('error') ? 'ban' : 'check' }}"></i>
                                {{ session()->get('error') ? 'Error' : 'Sucess' }}</h5>
                            {{ session()->get('message') }}
                        </div>
                    @endif
                    @yield('content')
                </div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        <footer class="main-footer">
            <strong>Copyright &copy; 2023 <a href="#">Satria Muda Indonesia Komwil Jakarta Barat</a>.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Version</b> 1.1.0
            </div>
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="{{ asset('assets') }}/plugins/jquery/jquery.min.js"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('assets') }}/plugins/jquery-ui/jquery-ui.min.js"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('assets') }}/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- ChartJS -->
    <script src="{{ asset('assets') }}/plugins/chart.js/Chart.min.js"></script>
    <!-- Sparkline -->
    <script src="{{ asset('assets') }}/plugins/sparklines/sparkline.js"></script>
    <!-- JQVMap -->
    <script src="{{ asset('assets') }}/plugins/jqvmap/jquery.vmap.min.js"></script>
    {{-- <script src="{{ asset('assets') }}/plugins/jqvmap/maps/jquery.vmap.usa.js"></script> --}}
    <!-- jQuery Knob Chart -->
    <script src="{{ asset('assets') }}/plugins/jquery-knob/jquery.knob.min.js"></script>
    <!-- daterangepicker -->
    <script src="{{ asset('assets') }}/plugins/moment/moment.min.js"></script>
    <script src="{{ asset('assets') }}/plugins/daterangepicker/daterangepicker.js"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="{{ asset('assets') }}/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Summernote -->
    <script src="{{ asset('assets') }}/plugins/summernote/summernote-bs4.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="{{ asset('assets') }}/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('assets') }}/dist/js/adminlte.js"></script>
    <!-- AdminLTE for demo purposes -->
    {{-- <script src="{{asset("assets")}}/dist/js/demo.js"></script>     --}}
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="{{ asset('assets') }}/dist/js/pages/dashboard.js"></script>
    <script src="{{ asset('assets') }}/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="{{ asset('assets') }}/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="{{ asset('assets') }}/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="{{ asset('assets') }}/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <script src="{{ asset('assets') }}/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
    <script src="{{ asset('assets') }}/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('assets') }}/plugins/sweetalert2/sweetalert2.min.js"></script>
    <!-- Select2 -->
    <script src="{{ asset('assets') }}/plugins/select2/js/select2.full.min.js"></script>
    <script src="{{ asset('assets') }}/agora/agora.js"></script>
    <script src="{{ asset('assets') }}/agora/index.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/29.2.0/classic/ckeditor.js"></script>
    <script>
        $('.select2').select2()

        function delete_confirmation(e, event) {
            event.preventDefault()
            Swal.fire({
                title: 'Are you sure want to?',
                text: "Delete this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: $(e).attr('href'),
                        data: {
                            _token: $('input[name="_token"]').val()
                        },
                        method: 'DELETE',
                        success: function(data) {
                            if (data.error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Sorry',
                                    text: data.message,
                                })
                                return
                            }
                            location.reload()
                        }
                    })
                }
            })
        }

        function appendParams(field, value) {
            url = ""
            if (countParams() == 0) {
                url += "?"
            } else {
                url += "&"
            }
            if (isParamExist(field)) {
                var url = new URL(window.location.href);
                var search_params = url.searchParams;

                // new value of "id" is set to "101"
                search_params.set(field, value);
                // change the search property of the main url
                url.search = search_params.toString();

                // the new url string
                var new_url = url.toString();
                window.location.href = new_url;
            } else {
                url += field + "=" + value
                window.location.href = window.location + url
            }
        }

        function getParams(name) {
            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            return urlParams.get(name)
        }

        function countParams() {
            var url = window.location.href;
            var matches = url.match(/[a-z\d]+=[a-z\d]+/gi);
            var count = matches ? matches.length : 0;
            console.log(count)
            return count
        }

        function isParamExist(field) {
            var url = window.location.href;
            if (url.indexOf('?' + field + '=') != -1)
                return true;
            else if (url.indexOf('&' + field + '=') != -1)
                return true;
            return false
        }

        function sortBy(e) {
            value = $('select[name="sort_by"]').find(":selected").val()
            if(value!=''){
              sort_type = $('input[name="sort_type"]:checked').val();
              appendParams("sort_by", value + ":" + sort_type)
            }
        }

        function perPage(e) {
            value = $(e).val()
            appendParams("per_page", value)
        }
    </script>
    @yield('script')
</body>

</html>
