<?php

session_start();

require_once "config/database.php";


// ==========================================
// TAMBAH ANTREAN
// ==========================================

if (isset($_POST['tambah_queue'])) {

    $dzikir = trim($_POST['dzikir']);
    $target = intval($_POST['target']);

    if ($dzikir !== "" && $target > 0) {

        $stmt = $conn->prepare("
            INSERT INTO queue_dzikir (dzikir, target)
            VALUES (?, ?)
        ");

        $stmt->bind_param("si", $dzikir, $target);

        $stmt->execute();

        $stmt->close();
    }

    header("Location: queue.php");
    exit;
}


// ==========================================
// PILIH ANTREAN TERDEPAN
// ==========================================

if (isset($_POST['pilih'])) {

    // Cari antrean paling depan
    $query = "
        SELECT *
        FROM queue_dzikir
        WHERE status = 'menunggu'
        ORDER BY id ASC
        LIMIT 1
    ";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {

        $data = $result->fetch_assoc();

        $_SESSION['dzikir'] = $data['dzikir'];
        $_SESSION['target'] = $data['target'];
        $_SESSION['queue_id'] = $data['id'];

        // Ubah status menjadi aktif
        $id = $data['id'];

        $stmt = $conn->prepare("
            UPDATE queue_dzikir
            SET status = 'aktif'
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $stmt->close();
    }

    header("Location: index.php");
    exit;
}


// ==========================================
// SELESAIKAN ANTREAN
// ==========================================

if (isset($_POST['selesai'])) {

    if (isset($_SESSION['queue_id'])) {

        $id = $_SESSION['queue_id'];

        $stmt = $conn->prepare("
            UPDATE queue_dzikir
            SET status = 'selesai'
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $stmt->close();

        // Hapus data sesi antrean aktif
        unset($_SESSION['queue_id']);
        unset($_SESSION['target']);
    }

    header("Location: queue.php");
    exit;
}


// ==========================================
// AMBIL ANTREAN
// ==========================================

$query = "
    SELECT *
    FROM queue_dzikir
    WHERE status = 'menunggu'
    ORDER BY id ASC
";

$result = $conn->query($query);

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Antrean Dzikir</title>

    <link
        rel="stylesheet"
        href="assets/style.css">

</head>

<body>

<div class="container">

    <h1>Antrean Dzikir</h1>

    <p class="subtitle">
        Queue — FIFO (First In, First Out)
    </p>


    <!-- =====================================
         FORM TAMBAH ANTREAN
    ====================================== -->

    <div class="card">

        <h2>Tambah Target Dzikir</h2>

        <form method="POST">

            <input
                type="text"
                name="dzikir"
                placeholder="Contoh: Tahmid"
                required
            >

            <br><br>

            <input
                type="number"
                name="target"
                placeholder="Target"
                min="1"
                required
            >

            <br><br>

            <button
                type="submit"
                name="tambah_queue"
                class="btn btn-primary">

                + Tambah Antrean

            </button>

        </form>

    </div>


    <!-- =====================================
         DAFTAR ANTREAN
    ====================================== -->

    <div class="card">

        <h2>Daftar Antrean</h2>

        <?php if ($result->num_rows > 0): ?>

            <?php

            $no = 1;
            $antrean_pertama = true;

            foreach ($result as $row):

            ?>

                <div style="
                    padding:15px;
                    margin-bottom:10px;
                    border:1px solid #ddd;
                    border-radius:8px;
                ">

                    <strong>

                        <?= $no++ ?>.

                        <?= htmlspecialchars($row['dzikir']) ?>

                    </strong>

                    <br>

                    Target:
                    <?= $row['target'] ?>

                    <br>

                    Status:
                    <?= $row['status'] ?>


                    <?php if ($antrean_pertama): ?>

                        <br><br>

                        <form method="POST">

                            <button
                                type="submit"
                                name="pilih"
                                class="btn btn-primary">

                                ▶ Pilih Antrean Ini

                            </button>

                        </form>

                        <?php

                        $antrean_pertama = false;

                        ?>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                Antrean masih kosong.
            </p>

        <?php endif; ?>

    </div>


    <!-- =====================================
         ANTREAN AKTIF
    ====================================== -->

    <?php if (isset($_SESSION['queue_id'])): ?>

        <div class="card">

            <h2>Dzikir Sedang Aktif</h2>

            <h3>
                <?= htmlspecialchars($_SESSION['dzikir']) ?>
            </h3>

            <p>
                Target:
                <?= $_SESSION['target'] ?>
            </p>

            <form method="POST">

                <button
                    type="submit"
                    name="selesai"
                    class="btn btn-primary">

                    ✓ Selesaikan Dzikir

                </button>

            </form>

        </div>

    <?php endif; ?>


    <!-- =====================================
         MENU
    ====================================== -->

    <div class="menu">

        <a href="index.php">
            ← Kembali ke Tasbih
        </a>

        <a href="riwayat.php">
            Riwayat
        </a>

        <a href="search.php">
            Cari Riwayat
        </a>

    </div>

</div>

</body>

</html>
