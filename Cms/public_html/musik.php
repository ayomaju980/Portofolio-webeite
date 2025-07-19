<?php
// Path folder dan file
$musikDir = './musik/';
$configFile = './musik.json';

// Baca config lama
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [
    'enabled' => false,
    'file' => '',
    'volume' => 0.5
];

// Upload musik
if (isset($_POST['upload'])) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $name = basename($_FILES['file']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === 'mp3') {
            move_uploaded_file($_FILES['file']['tmp_name'], $musikDir . $name);
            $uploadSuccess = "File <strong>$name</strong> berhasil diupload.";
        } else {
            $uploadError = "Hanya file MP3 yang diizinkan.";
        }
    }
}

// Simpan pengaturan
if (isset($_POST['save'])) {
    $config['enabled'] = isset($_POST['enabled']);
    $config['file'] = $_POST['file'];
    $volume = floatval($_POST['volume']);
    $config['volume'] = min(max($volume, 0), 1);
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    $saveSuccess = "Pengaturan berhasil disimpan.";
}

// List musik
$files = array_values(array_filter(scandir($musikDir), function($f) use ($musikDir) {
    return is_file($musikDir . $f) && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'mp3';
}));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin Musik Latar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #f8f9fa;
}
h2 {
    font-weight: 600;
}
.card-header {
    background: #821131;
    color: #fff;
    font-weight: 500;
}
footer {
    margin-top: 50px;
    font-size: 0.9rem;
    color: #888;
    text-align: center;
}
audio {
    width: 100%;
    margin-top: 10px;
}
</style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4 text-center">🎵 Pengaturan Musik Latar Website</h2>

    <?php if(!empty($uploadSuccess)): ?>
        <div class="alert alert-success"><?= $uploadSuccess ?></div>
    <?php endif; ?>
    <?php if(!empty($uploadError)): ?>
        <div class="alert alert-danger"><?= $uploadError ?></div>
    <?php endif; ?>
    <?php if(!empty($saveSuccess)): ?>
        <div class="alert alert-success"><?= $saveSuccess ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header">Upload Musik Baru (.mp3)</div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-8">
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="upload" class="btn btn-primary w-100">📤 Upload</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header">Pengaturan Musik Aktif</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="enabled" id="enabled" <?= $config['enabled'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enabled">Aktifkan Musik Latar</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pilih Musik</label>
                    <select name="file" class="form-select" id="musicSelect">
                        <?php foreach($files as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>" <?= $f === $config['file'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <audio id="audioPreview" controls src="<?= !empty($config['file']) ? './musik/' . htmlspecialchars($config['file']) : '' ?>"></audio>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Volume Default (0–1)</label>
                    <input type="number" step="0.1" min="0" max="1" name="volume" class="form-control" value="<?= htmlspecialchars($config['volume']) ?>">
                    <small class="text-muted">Misal: 0.2 untuk suara pelan, 1.0 untuk maksimal.</small>
                </div>
                <div class="col-12">
                    <button type="submit" name="save" class="btn btn-success">💾 Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="mt-5">
        &copy; <?= date('Y') ?> Admin Musik Latar
    </footer>
</div>

<script>
const select = document.getElementById('musicSelect');
const audio = document.getElementById('audioPreview');
select.addEventListener('change', () => {
    audio.src = './musik/' + select.value;
    audio.play();
});
</script>
</body>
</html>
