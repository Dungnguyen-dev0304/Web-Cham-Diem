<?php
// 1. CẤU HÌNH KẾT NỐI DOCKER
$config = [
    'mienbac' => [
        'host' => '127.0.0.1', 
        'port' => '3311', 
        'dbname' => 'db_mienbac',
        'user' => 'root',
        'pass' => '123456' 
    ],
    'miennam' => [
        'host' => '127.0.0.1', 
        'port' => '3322', 
        'dbname' => 'db_miennam',
        'user' => 'root',
        'pass' => '123456' 
    ]
];

$resultData = null;
$message = "";
$nodeName = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sbd']) && trim($_POST['sbd']) !== '') {
    $sbd_input = trim($_POST['sbd']);
    $sbd_number = intval($sbd_input); 

    // 2. QUERY ROUTER (PHÂN LUỒNG)
    $targetNode = "";
    if ($sbd_number >= 1 && $sbd_number <= 500) {
        $targetNode = 'mienbac';
        $nodeName = "Miền Bắc";
    } elseif ($sbd_number >= 501 && $sbd_number <= 1000) {
        $targetNode = 'miennam';
        $nodeName = "Miền Nam";
    } else {
        $message = "<div class='alert error'><i class='fa-solid fa-circle-exclamation'></i> SBD không hợp lệ! Vui lòng nhập từ 0001 đến 1000.</div>";
    }

    if ($targetNode !== "") {
        $dbConf = $config[$targetNode];
        
        // 3. XỬ LÝ LỖI (FAULT TOLERANCE)
        try {
            $options = [
                PDO::ATTR_TIMEOUT => 2, 
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];
            
            $dsn = "mysql:host={$dbConf['host']};port={$dbConf['port']};dbname={$dbConf['dbname']};charset=utf8";
            $conn = new PDO($dsn, $dbConf['user'], $dbConf['pass'], $options);

            $sbd_formatted = str_pad($sbd_number, 4, '0', STR_PAD_LEFT); 
            
            $stmt = $conn->prepare("SELECT * FROM diemthi WHERE sbd = :sbd");
            $stmt->execute(['sbd' => $sbd_formatted]);
            
            $resultData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultData) {
                $message = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Đã kết nối thành công trạm <b>{$nodeName}</b>! Tìm thấy thí sinh có số báo danh {$resultData['sbd']}.</div>";
            } else {
                $message = "<div class='alert warning'><i class='fa-solid fa-magnifying-glass'></i> Không tìm thấy dữ liệu cho SBD: <b>{$sbd_formatted}</b> tại trạm {$nodeName}.</div>";
            }

        } catch (PDOException $e) {
            $message = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Trạm dữ liệu <b>{$nodeName}</b> hiện đang bảo trì hoặc mất kết nối. Xin lỗi vì sự bất tiện!</div>";
        }
    }
}

function formatScore($score) {
    if (!isset($score) || $score === '') return '<span style="color: #cbd5e1;">-</span>';
    return "<span class='score-badge'>" . htmlspecialchars($score) . "</span>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu Điểm thi Quốc gia </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-color: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: var(--text-main);
            margin: 0; 
            padding: 20px; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container { 
            width: 100%;
            max-width: 1000px; 
            background: #ffffff; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); 
        }

        .header-title { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        
        .header-title h2 {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px 0;
            letter-spacing: -0.5px;
        }

        .header-title p {
            color: var(--text-muted);
            margin: 0;
            font-size: 15px;
        }

        /* Form Search Modern */
        .search-wrapper {
            max-width: 600px;
            margin: 0 auto 30px auto;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #f8fafc;
        }

        .search-input:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
        }

        .btn-submit {
            position: absolute;
            right: 6px;
            top: 6px;
            bottom: 6px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 40px;
            padding: 0 25px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Alerts */
        .alert { 
            padding: 16px 20px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            font-size: 15px; 
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.4s ease-out;
        }
        .success { background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .error { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .warning { background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            animation: fadeIn 0.5s ease-out;
        }

        .result-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 15px; 
            background: white;
        }
        
        .result-table th { 
            background: linear-gradient(to right, #1e293b, #334155); 
            color: #ffffff; 
            font-weight: 600; 
            padding: 16px 12px;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        .result-table td { 
            padding: 16px 12px; 
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }

        .result-table tr:last-child td { border-bottom: none; }
        .result-table tr:hover { background-color: #f8fafc; }
        
        .sbd-highlight { 
            font-weight: 700; 
            color: var(--primary); 
            font-size: 16px;
        }

        .score-badge {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-title">
        <h2>Hệ Thống Tra Cứu điểm thi</h2>
        <p>Kiến trúc 2 Node (Miền Bắc - Miền Nam) chạy trên nền tảng Docker</p>
    </div>

    <form method="POST" action="">
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="sbd" name="sbd" class="search-input" placeholder="Nhập Số Báo Danh (VD: 15, 0502...)" required autocomplete="off">
            <button type="submit" class="btn-submit">Tra cứu</button>
        </div>
    </form>

    <?php echo $message; ?>

    <?php if ($resultData): ?>
        <div class="table-wrapper">
            <table class="result-table">
                <tr>
                    <th>SBD</th>
                    <th>Họ và Tên</th>
                    <th>Toán</th>
                    <th>Ngữ Văn</th>
                    <th>Ngoại Ngữ</th>
                    <th>Vật Lí</th>
                    <th>Hóa Học</th>
                    <th>Sinh Học</th>
                    <th>Lịch Sử</th>
                    <th>Địa Lí</th>
                    <th>GDCD</th>
                    <th>Mã NN</th>
                </tr>
                <tr>
                    <td class="sbd-highlight"><?php echo formatScore($resultData['sbd']); ?></td>
                    <td style="font-weight: 600; color: #0f172a;">
                    <?php echo isset($resultData['ho_ten']) && $resultData['ho_ten'] !== '' ? htmlspecialchars($resultData['ho_ten']) : '<span style="color: #cbd5e1;">-</span>'; ?>
                    </td>
                    <td><?php echo formatScore($resultData['toan']); ?></td>
                    <td><?php echo formatScore($resultData['ngu_van']); ?></td>
                    <td><?php echo formatScore($resultData['ngoai_ngu']); ?></td>
                    <td><?php echo formatScore($resultData['vat_li']); ?></td>
                    <td><?php echo formatScore($resultData['hoa_hoc']); ?></td>
                    <td><?php echo formatScore($resultData['sinh_hoc']); ?></td>
                    <td><?php echo formatScore($resultData['lich_su']); ?></td>
                    <td><?php echo formatScore($resultData['dia_li']); ?></td>
                    <td><?php echo formatScore($resultData['gdcd']); ?></td>
                    <td><?php echo formatScore($resultData['ma_ngoai_ngu']); ?></td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>