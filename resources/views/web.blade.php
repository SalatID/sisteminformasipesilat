@extends('web-index')

@section('content')
    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5" style="min-height: 100vh; background: linear-gradient(135deg, #ff2121 0%, #a10000 100%);">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold mb-4">{{env('APP_NAME')}}</h1>
                    <p class="lead mb-4">Sistem Manajemen Informasi Pesilat yang Modern dan Terintegrasi</p>
                    <p class="fs-5 mb-5">Platform digital yang dibuat untuk mengelola data pesilat, absensi, dan pelaporan secara efisien dan akurat di PPS Satria Muda Indonesia Komwil Jakarta Barat</p>
                    <div class="d-flex gap-3">
                        <a href="/login" class="btn btn-light btn-lg px-4 py-3">
                            <i class="fas fa-sign-in-alt me-2"></i> Masuk Sekarang
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="text-center">
                        <img src="{{ asset('assets/dist/img/logo-smi.png') }}" alt="Logo SMI" class="img-fluid" style="max-width: 400px; opacity: 0.75;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container py-5">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-4 fw-bold mb-3">Fitur</h2>
                    <p class="lead text-muted">Mengelola data pesilat dengan mudah dan efisien</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-md-6 col-lg-4 p-2">
                    <div class="card h-100 shadow-sm border-0 hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon bg-primary bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="fas fa-user-ninja fa-2x"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Manajemen Data Pesilat</h4>
                            <p class="text-muted">Kelola informasi lengkap pesilat termasuk profil, riwayat latihan, prestasi, dan perkembangan kemampuan secara terorganisir</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-md-6 col-lg-4 p-2">
                    <div class="card h-100 shadow-sm border-0 hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon bg-success bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="fas fa-clipboard-check fa-2x"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Sistem Absensi Digital</h4>
                            <p class="text-muted">Catat kehadiran pesilat secara real-time dengan sistem absensi digital yang akurat dan mudah digunakan</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-md-6 col-lg-4 p-2">
                    <div class="card h-100 shadow-sm border-0 hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon bg-info bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="fas fa-chart-bar fa-2x"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Laporan Kehadiran</h4>
                            <p class="text-muted">Generate laporan kehadiran lengkap dengan statistik dan analisis untuk monitoring perkembangan pesilat</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="col-md-6 col-lg-4 p-2">
                    <div class="card h-100 shadow-sm border-0 hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon bg-warning bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="fas fa-shield-alt fa-2x"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Keamanan Data</h4>
                            <p class="text-muted">Data pesilat tersimpan dengan aman menggunakan enkripsi dan sistem keamanan berlapis</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="col-md-6 col-lg-4 p-2">
                    <div class="card h-100 shadow-sm border-0 hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon bg-danger bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="fas fa-mobile-alt fa-2x"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Responsif & Mobile Friendly</h4>
                            <p class="text-muted">Akses sistem dari berbagai perangkat - desktop, tablet, atau smartphone dengan tampilan yang optimal</p>
                        </div>
                    </div>
                </div>

                <!-- Feature 6 -->
                <div class="col-md-6 col-lg-4 p-2">
                    <div class="card h-100 shadow-sm border-0 hover-card">
                        <div class="card-body text-center p-5">
                            <div class="feature-icon bg-secondary bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Multi User Management</h4>
                            <p class="text-muted">Kelola hak akses untuk pelatih, admin, dan pesilat dengan role management yang fleksibel</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-5 bg-white">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-5 fw-bold mb-4">Tentang Sistem Kami</h2>
                    <p class="lead mb-4">Sistem Informasi Pesilat adalah platform digital yang dirancang khusus untuk memudahkan pengelolaan informasi dan administrasi pesilat di PPS Satria Muda Indonesia Komwil Jakarta Barat</p>
                    <p class="mb-4">Dengan fitur-fitur lengkap dan interface yang user-friendly, sistem ini membantu pelatih dan administrator dalam:</p>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Mencatat dan mengelola data pesilat</strong> dengan rapi dan terorganisir
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Memantau kehadiran</strong> setiap pesilat dalam setiap sesi latihan
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Menghasilkan laporan</strong> yang akurat untuk evaluasi dan perencanaan
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Meningkatkan efisiensi</strong> administrasi perguruan silat
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <div class="text-center">
                                <img src="{{ asset('assets/dist/img/logo-smi.png') }}" alt="Logo SMI" class="img-fluid" style="max-width: 400px; opacity: 0.75;">
                            </div>
                            <h4 class="text-center fw-bold mb-3">PPS Satria Muda Indonesia Komwil Jakarta Barat</h4>
                            <p class="text-center text-muted">Musuh Tidak Dicari, Bertemu Dihindari, Sekali Dimulai Titik Mati Baru Berhenti, Bela Diri Untuk Bela Bangsa</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    @include('partials.web-footer')
    <!-- End Footer -->

    <style>
        .hover-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hover-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
        }
    </style>
@endsection
