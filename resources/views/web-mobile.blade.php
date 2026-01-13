@extends('web-index')

@section('content')
    <!-- Hero Section - Mobile Optimized -->
    <section class="hero-section bg-primary text-white py-4" style="min-height: 100vh; background: linear-gradient(135deg, #ff2121 0%, #a10000 100%);">
        <div class="container px-3">
            <div class="row align-items-center min-vh-100">
                <div class="col-12 text-center">
                    <div class="mb-4">
                        <img src="{{ asset('assets/dist/img/logo-smi.png') }}" alt="Logo SMI" class="img-fluid mb-3" style="max-width: 150px; opacity: 0.95;">
                    </div>
                    <h1 class="h2 fw-bold mb-3">{{env('APP_NAME')}}</h1>
                    <p class="mb-3" style="font-size: 0.95rem;">Sistem Manajemen Informasi Pesilat yang Modern dan Terintegrasi</p>
                    <p class="small mb-4" style="font-size: 0.85rem;">Platform digital untuk mengelola data pesilat, absensi, dan pelaporan di PPS Satria Muda Indonesia Komwil Jakarta Barat</p>
                    <div class="d-grid gap-2 mx-auto" style="max-width: 300px;">
                        <a href="/login" class="btn btn-light btn-lg py-3 rounded-pill">
                            <i class="fas fa-sign-in-alt me-2"></i>Masuk Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section - Mobile Optimized -->
    <section id="features" class="py-4 bg-light">
        <div class="container px-3">
            <div class="row mb-3">
                <div class="col-12 text-center">
                    <h2 class="h3 fw-bold mb-2">Fitur Unggulan</h2>
                    <p class="text-muted small">Kelola data pesilat dengan mudah</p>
                </div>
            </div>

            <div class="row g-3">
                <!-- Feature 1 -->
                <div class="col-12">
                    <div class="card shadow-sm border-0 hover-card-mobile">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon-mobile bg-primary bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                                    <i class="fas fa-user-ninja"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2" style="font-size: 1rem;">Manajemen Data Pesilat</h5>
                                    <p class="text-muted mb-0 small">Kelola informasi lengkap pesilat termasuk profil, riwayat latihan, dan prestasi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-12">
                    <div class="card shadow-sm border-0 hover-card-mobile">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon-mobile bg-success bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2" style="font-size: 1rem;">Sistem Absensi Digital</h5>
                                    <p class="text-muted mb-0 small">Catat kehadiran pesilat secara real-time dengan sistem yang akurat</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-12">
                    <div class="card shadow-sm border-0 hover-card-mobile">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon-mobile bg-info bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2" style="font-size: 1rem;">Laporan Kehadiran</h5>
                                    <p class="text-muted mb-0 small">Generate laporan kehadiran lengkap dengan statistik dan analisis</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="col-12">
                    <div class="card shadow-sm border-0 hover-card-mobile">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon-mobile bg-warning bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2" style="font-size: 1rem;">Keamanan Data</h5>
                                    <p class="text-muted mb-0 small">Data pesilat tersimpan aman dengan enkripsi dan sistem keamanan berlapis</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="col-12">
                    <div class="card shadow-sm border-0 hover-card-mobile">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon-mobile bg-danger bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2" style="font-size: 1rem;">Mobile Friendly</h5>
                                    <p class="text-muted mb-0 small">Akses sistem dari smartphone dengan tampilan yang optimal</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 6 -->
                <div class="col-12">
                    <div class="card shadow-sm border-0 hover-card-mobile">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon-mobile bg-secondary bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-2" style="font-size: 1rem;">Multi User Management</h5>
                                    <p class="text-muted mb-0 small">Kelola hak akses untuk pelatih, admin, dan pesilat dengan fleksibel</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section - Mobile Optimized -->
    <section class="py-4 bg-white">
        <div class="container px-3">
            <div class="row">
                <div class="col-12 text-center mb-4">
                    <img src="{{ asset('assets/dist/img/logo-smi.png') }}" alt="Logo SMI" class="img-fluid mb-3" style="max-width: 120px;">
                    <h5 class="fw-bold mb-2">PPS Satria Muda Indonesia</h5>
                    <p class="text-muted small">Komwil Jakarta Barat</p>
                </div>
                <div class="col-12">
                    <h2 class="h4 fw-bold mb-3">Tentang Sistem Kami</h2>
                    <p class="mb-3 small">Sistem Informasi Pesilat adalah platform digital yang dirancang khusus untuk memudahkan pengelolaan informasi dan administrasi pesilat di PPS Satria Muda Indonesia Komwil Jakarta Barat</p>
                    <p class="mb-3 small fw-bold">Sistem ini membantu dalam:</p>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Mencatat dan mengelola data pesilat
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Memantau kehadiran setiap sesi latihan
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Menghasilkan laporan yang akurat
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Meningkatkan efisiensi administrasi
                        </li>
                    </ul>
                    <div class="alert alert-light border mt-3 p-3">
                        <p class="small text-center mb-0 fst-italic">"Musuh Tidak Dicari, Bertemu Dihindari, Sekali Dimulai Titik Mati Baru Berhenti, Bela Diri Untuk Bela Bangsa"</p>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Footer -->
    @include('partials.web-footer')
    <!-- End Footer -->

    <!-- Mobile Optimized Styles -->
    <style>
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Mobile card hover effect - subtle */
        .hover-card-mobile {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-card-mobile:active {
            transform: scale(0.98);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15)!important;
        }

        /* Ensure text is readable on mobile */
        body {
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Optimize buttons for touch */
        .btn {
            min-height: 44px;
            touch-action: manipulation;
        }

        /* Improve card spacing on mobile */
        @media (max-width: 576px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            h1, .h1 {
                font-size: 1.75rem;
            }
            
            h2, .h2 {
                font-size: 1.5rem;
            }
            
            h3, .h3 {
                font-size: 1.25rem;
            }
            
            .display-3 {
                font-size: 2.5rem;
            }
            
            .display-4 {
                font-size: 2rem;
            }
            
            .display-5 {
                font-size: 1.75rem;
            }
        }

        /* Fast tap - remove delay on mobile */
        a, button, .btn {
            -webkit-tap-highlight-color: rgba(0,0,0,0.1);
        }

        /* Optimize images for mobile */
        img {
            max-width: 100%;
            height: auto;
        }

        /* Prevent text size adjustment on orientation change */
        html {
            -webkit-text-size-adjust: 100%;
        }

        /* Improve scrolling performance */
        * {
            -webkit-overflow-scrolling: touch;
        }
    </style>
@endsection
