    <?php

session_start();

require_once "config/database.php";


// ==========================================
// INISIALISASI SESSION
// ==========================================

if (!isset($_SESSION['stack'])) {
    $_SESSION['stack'] = [];
}

if (!isset($_SESSION['jumlah'])) {
    $_SESSION['jumlah'] = 0;
}

if (!isset($_SESSION['dzikir'])) {
    $_SESSION['dzikir'] = "Subhanallah";
}

if (!isset($_SESSION['target_selesai'])) {
    $_SESSION['target_selesai'] = false;
}


$aksi = $_POST['aksi'] ?? "";


// ==========================================
// TAMBAH HITUNGAN
// ==========================================

if ($aksi === "tambah") {

    // Jangan izinkan tambah jika target sudah selesai
    if ($_SESSION['target_selesai'] === true) {

        $_SESSION['pesan'] =
            "Alhamdulillah, anda telah mencapai target dzikir!";

        header("Location: index.php");
        exit;
    }


    // Simpan kondisi sebelumnya ke STACK
    $_SESSION['stack'][] = $_SESSION['jumlah'];


    // Tambahkan hitungan
    $_SESSION['jumlah']++;


    // ======================================
    // CEK TARGET
    // ======================================

    if (
        isset($_SESSION['target']) &&
        $_SESSION['target'] > 0 &&
        $_SESSION['jumlah'] >= $_SESSION['target']
    ) {

        $dzikir = $_SESSION['dzikir'];
        $jumlah = $_SESSION['jumlah'];


        // ==================================
        // SIMPAN OTOMATIS KE RIWAYAT
        // ==================================

        $stmt = $conn->prepare("
            INSERT INTO riwayat (dzikir, jumlah)
            VALUES (?, ?)
        ");

        $stmt->bind_param(
            "si",
            $dzikir,
            $jumlah
        );

        $stmt->execute();
        $stmt->close();


        // ==================================
        // JIKA BERASAL DARI QUEUE
        // ==================================

        if (isset($_SESSION['queue_id'])) {

            $queue_id = $_SESSION['queue_id'];

            $stmt = $conn->prepare("
                UPDATE queue_dzikir
                SET status = 'selesai'
                WHERE id = ?
            ");

            $stmt->bind_param(
                "i",
                $queue_id
            );

            $stmt->execute();
            $stmt->close();


            // Hapus antrean aktif
            unset($_SESSION['queue_id']);
        }


        // ==================================
        // TANDAI TARGET SELESAI
        // ==================================

        $_SESSION['target_selesai'] = true;


        // Pesan popup
        $_SESSION['pesan'] =
            "Alhamdulillah, anda telah mencapai target dzikir!";
    }
}


// ==========================================
// UNDO
// ==========================================

elseif ($aksi === "undo") {

    // Undo hanya boleh jika target belum selesai
    if ($_SESSION['target_selesai'] === false) {

        if (!empty($_SESSION['stack'])) {

            $_SESSION['jumlah'] =
                array_pop($_SESSION['stack']);

        }
    }
}


// ==========================================
// RESET
// ==========================================

elseif ($aksi === "reset") {

    // Jika sudah ada hitungan,
    // simpan sebagai riwayat manual
    if ($_SESSION['jumlah'] > 0) {

        $dzikir = $_SESSION['dzikir'];
        $jumlah = $_SESSION['jumlah'];

        $stmt = $conn->prepare("
            INSERT INTO riwayat (dzikir, jumlah)
            VALUES (?, ?)
        ");

        $stmt->bind_param(
            "si",
            $dzikir,
            $jumlah
        );

        $stmt->execute();
        $stmt->close();
    }


    // Reset counter
    $_SESSION['jumlah'] = 0;

    // Kosongkan Stack
    $_SESSION['stack'] = [];

    // Target bisa dihitung kembali
    $_SESSION['target_selesai'] = false;
}


// ==========================================
// KEMBALI KE INDEX
// ==========================================

header("Location: index.php");
exit;

?>
