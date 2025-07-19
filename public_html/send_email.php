<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = htmlspecialchars($_POST['name']);
    $email   = htmlspecialchars($_POST['email']);
    $subject = htmlspecialchars($_POST['subject']); // Subject dari form
    $message = htmlspecialchars($_POST['message']);

    $to = "hi@aksanazachri.my.id";

    // Pesan teks
    $text = "Nama: $name\nEmail: $email\n\nPesan:\n$message";

    // Cek apakah ada file diupload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $file_tmp   = $_FILES['file']['tmp_name'];
        $file_name  = $_FILES['file']['name'];
        $file_type  = $_FILES['file']['type'];
        $file_data  = file_get_contents($file_tmp);
        $file_base64 = chunk_split(base64_encode($file_data));

        // Boundary untuk multipart
        $boundary = md5(uniqid());

        // Header email
        $headers  = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

        // Body Email
        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= "$text\r\n";

        // Lampiran
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: $file_type; name=\"$file_name\"\r\n";
        $body .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= "$file_base64\r\n";
        $body .= "--$boundary--";

        // Kirim email dengan lampiran
        if (mail($to, $subject, $body, $headers)) {
            echo "<script>alert('Email berhasil dikirim dengan lampiran!'); window.location.href='index.html';</script>";
        } else {
            echo "<script>alert('Gagal mengirim email.'); window.location.href='index.html';</script>";
        }
    } else {
        // Jika tidak ada file, kirim email biasa
        $headers  = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        if (mail($to, $subject, $text, $headers)) {
            echo "<script>alert('Email berhasil dikirim!'); window.location.href='index.html';</script>";
        } else {
            echo "<script>alert('Gagal mengirim email.'); window.location.href='index.html';</script>";
        }
    }
} else {
    echo "Invalid request.";
}
?>
