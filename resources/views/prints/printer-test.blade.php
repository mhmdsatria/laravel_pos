<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
    @page {
        size: {
                {
                $paper==='receipt'? '80mm 75mm': '210mm 90mm'
            }
        }

        ;

        margin: {
                {
                $paper==='receipt'? '3mm': '8mm'
            }
        }
    }

    html,
    body {
        margin: 0;
        padding: 0
    }

    body {
        font-family: Arial, sans-serif;
        color: #111;
        font-size: 12px
    }

    .box {
        border: 1px solid #111;
        padding: 16px;
        text-align: center
    }
    </style>
</head>

<body>
    <div class="box">
        <h2>{{ $title }}</h2>
        <p>TOKO BANGUNAN 39</p>
        <p>{{ now()->format('d M Y H:i:s') }}</p>
        <p>{{ $printerName }}</p><strong>Printer berhasil terhubung.</strong>
    </div>
</body>

</html>