<?php
$data = $_POST;

// Upload foto profil jika ada
if ($_FILES['profile_image']['error'] === 0) {
    $filename = "profile_" . time() . "_" . $_FILES['profile_image']['name'];
    move_uploaded_file($_FILES['profile_image']['tmp_name'], "img/" . $filename);
    $data['profile'] = $filename;
}

// Upload gambar proyek
foreach ($data['projects'] as $i => $project) {
    $inputName = "project_image_$i";
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === 0) {
        $filename = "project_" . $i . "_" . time() . "_" . $_FILES[$inputName]['name'];
        move_uploaded_file($_FILES[$inputName]['tmp_name'], "img/" . $filename);
        $data['projects'][$i]['image'] = $filename;
    }
}

// Simpan ke data.json
file_put_contents("data.json", json_encode($data, JSON_PRETTY_PRINT));
header("Location: admin.php");
exit;
?>
