<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Ijin Keluar Kantor</title>
    <style>
    body {
        font-family: Arial, sans-serif;
    }

    .permission-form {
        width: 20%;
        margin: auto;
    }

    .header {
        text-align: center;
        margin-bottom: 10px;
    }

    .pt {
        font-style: italic;
        font-weight: bold;

    }

    .jenis {
        text-decoration: underline;
    }

    .content {
        line-height: 1.6;
    }

    .footer {
        text-align: center;
        margin-top: 20px;
    }

    .form-section {
        margin-bottom: 20px;
    }
    </style>
</head>

<body>

    <div class="permission-form">
        <div class="header">
            <p class="pt">pt.sahabat abadi sejahtera</p>
            <h1 class="jenis">FORM IJIN KELUAR KANTOR</h1>
        </div>

        <div class="form-section">
            <h2>Data Personal</h2>
            <label for="name">Nama:</label>
            <input type="text" id="name" name="name" required><br>

            <label for="department">Departemen:</label>
            <input type="text" id="department" name="department" required><br>
        </div>

        <div class="form-section">
            <h2>Detail Ijin</h2>
            <label for="reason">Alasan Ijin:</label>
            <textarea id="reason" name="reason" rows="4" required></textarea><br>

            <label for="duration">Durasi Ijin:</label>
            <input type="text" id="duration" name="duration" required><br>
        </div>

        <div class="footer">
            <p>Formulir ini harus disetujui oleh atasan sebelum meninggalkan kantor.</p>
            <p>Terima kasih.</p>
        </div>
    </div>
</body>

</html>