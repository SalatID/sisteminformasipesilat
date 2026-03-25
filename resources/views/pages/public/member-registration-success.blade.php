<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil - Sistem Informasi Pesilat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .success-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            text-align: center;
            padding: 60px 40px;
        }

        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 0.6s;
        }

        @keyframes bounce {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }

        .success-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .success-message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .member-id-box {
            background: #f8f9fa;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .member-id-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .member-id-value {
            font-size: 32px;
            font-weight: 700;
            color: #28a745;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: left;
            font-size: 14px;
            color: #01579b;
        }

        .info-box strong {
            display: block;
            margin-bottom: 5px;
        }

        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 40px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    

    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <h1 class="success-title">Pendaftaran Berhasil!</h1>

        <p class="success-message">
            Terima kasih telah mendaftar sebagai pesilat. Data Anda telah diterima dan sedang menunggu verifikasi dari admin.
        </p>
            @if(session('member_id'))
                <div class="member-id-box">
                    <div class="member-id-label">ID Member Anda</div>
                    <div class="member-id-value">{{ session('member_id', '-') }}</div>
                </div>        
            @endif

        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> Informasi Penting:</strong>
            <p class="mb-0">
                Admin akan memverifikasi data Anda dalam waktu 1x24 jam. Anda dapat menghubungi admin jika ada pertanyaan.
            </p>
        </div>

        <a href="{{ route('member.registration.create') }}" class="btn-home">
            <i class="fas fa-arrow-left"></i> Kembali ke Form Pendaftaran
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
