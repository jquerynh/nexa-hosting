<?php
$subjek = $_POST['subjek'];
$pesan = $_POST['pesan'];
$sender = $_POST['sender'];

// Anti spam: cek duplikat pesan dan subjek via file antispam.txt
$signature = md5($subjek . $pesan);
$antispamFile = 'antispam.txt';

// Baca riwayat pengiriman dari file
$history = [];
if(file_exists($antispamFile)) {
    $history = unserialize(file_get_contents($antispamFile));
    if(!is_array($history)) $history = [];
}

// Bersihkan data lama (lebih dari 1 jam)
foreach($history as $sig => $timestamp) {
    if(time() - $timestamp > 3600) {
        unset($history[$sig]);
    }
}

// Cek apakah signature sudah ada (duplikat)
if(isset($history[$signature])) {
    die("Anti-spam: Pesan dengan subjek dan isi yang sama persis tidak boleh dikirim dua kali dalam satu jam.");
}

// Simpan signature baru
$history[$signature] = time();
file_put_contents($antispamFile, serialize($history));

include 'ndra/data.php';
$sender = 'From: '.$nik.'<'.$sender.'>';
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= ''.$sender.'' . "\r\n";

$read = file_get_contents('ndra/data.json');
$json = json_decode($read,true);

for($i=0;$i<=count($json) - 1;$i++)
{
    mail($json[$i]['email'], $subjek, $pesan, $headers);
}
include 'data.php';

// Fungsi untuk mengirim request ke API
function sendToApi($url, $subjek, $pesan, $sender) {
    $data = "subjek=".$subjek."&pesan=".$pesan."&sender=".$sender;
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $url);
    curl_setopt($ch2, CURLOPT_POST, 1);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch2, CURLOPT_HEADER, 0);
    curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, 0);
    $result = curl_exec($ch2);
    curl_close($ch2);
    return $result;
}

// Kirim ke semua API
sendToApi("https://resscloud.rabbit-dsn.biz.id/G/apiii.php", $subjek, $pesan, $sender);
?>
