<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'auth.php';
include 'koneksi.php';

if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
  header("Location: ../Index.php?page=pekerja"); exit;
}

/* ===================== DB ADAPTER (PDO / mysqli) ===================== */
$DB_MODE = null;
if (isset($pdo) && $pdo instanceof PDO) {
  $DB_MODE = 'pdo';
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} elseif (isset($conn) && $conn instanceof mysqli) {
  $DB_MODE = 'mysqli';
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $conn->set_charset('utf8mb4');
} else {
  die('Koneksi database tidak ditemukan. Pastikan koneksi.php mendefinisikan $pdo (PDO) atau $conn (mysqli).');
}

function db_fetch_all($sql, $params = []) {
  global $DB_MODE, $pdo, $conn;
  if ($DB_MODE === 'pdo') {
    if (empty($params)) return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $st = $pdo->prepare($sql); $st->execute($params); return $st->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $st = $conn->prepare($sql);
    if (!empty($params)) {
      $types = str_repeat('s', count($params));
      $st->bind_param($types, ...array_values($params));
    }
    $st->execute(); $res = $st->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
  }
}
function db_fetch($sql, $params = []) { $r = db_fetch_all($sql, $params); return $r[0] ?? null; }
function db_exec($sql, $params = []) {
  global $DB_MODE, $pdo, $conn;
  if ($DB_MODE === 'pdo') {
    if (empty($params)) return $pdo->exec($sql);
    $st = $pdo->prepare($sql); $st->execute($params); return $st->rowCount();
  } else {
    $st = $conn->prepare($sql);
    if (!empty($params)) {
      $types = str_repeat('s', count($params));
      $st->bind_param($types, ...array_values($params));
    }
    $st->execute(); return $st->affected_rows;
  }
}
function db_begin(){ global $DB_MODE,$pdo,$conn; $DB_MODE==='pdo' ? $pdo->beginTransaction() : $conn->begin_transaction(); }
function db_commit(){ global $DB_MODE,$pdo,$conn; $DB_MODE==='pdo' ? $pdo->commit() : $conn->commit(); }
function db_rollback(){ global $DB_MODE,$pdo,$conn; $DB_MODE==='pdo' ? $pdo->rollBack() : $conn->rollback(); }

/* ===================== KONFIG ===================== */
const TARIF_PER_KG = 2500;
const MIN_AMBIL_KG = 20;

/* ===================== DATA STOK ===================== */
$sql_stok = "
  SELECT s.id_stok, s.jumlah_stok, s.status_stok, p.nama_produk, pr.tgl_produksi
  FROM stok s
  JOIN produk p ON s.id_produk = p.id_produk
  LEFT JOIN produksi pr ON s.id_produksi = pr.id_produksi
  WHERE s.status_stok IN ('Siap dikemas','Siap dipacking')
    AND s.jumlah_stok > 0
  ORDER BY COALESCE(pr.tgl_produksi,'0000-00-00') DESC, s.id_stok DESC
";
try {
  $stok_list = db_fetch_all($sql_stok);
} catch (Throwable $e) {
  $_SESSION['notif'] = ['pesan' => 'Error ambil stok: '.$e->getMessage(), 'tipe' => 'error'];
  $stok_list = [];
}

/* ===================== HELPER ===================== */
function updateStatusPekerjaGeneric($id_pekerja) {
  $row = db_fetch("SELECT COUNT(*) AS c FROM riwayat_gaji WHERE id_pekerja = ? AND keterangan = 'Belum Dibayar'", [$id_pekerja]);
  $new_status = ((int)($row['c'] ?? 0) === 0) ? 'Dibayar' : 'Belum Dibayar';
  db_exec("UPDATE pekerja_lepas SET status_pembayaran = ? WHERE id_pekerja = ?", [$new_status, $id_pekerja]);
}

/* ===================== PROSES POST ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    switch ($action) {
      case 'tambah_pekerja':
        db_exec(
          "INSERT INTO pekerja_lepas (nama_pekerja, kontak, alamat, status_pembayaran, id_admin)
           VALUES (?, ?, ?, 'Belum Dibayar', 1)",
          [trim($_POST['nama_pekerja'] ?? ''), trim($_POST['kontak'] ?? ''), trim($_POST['alamat'] ?? '')]
        );
        $_SESSION['notif'] = ['pesan' => 'Data pekerja berhasil ditambahkan!', 'tipe' => 'sukses'];
        break;

      case 'edit_pekerja':
        db_exec(
          "UPDATE pekerja_lepas SET nama_pekerja = ?, kontak = ?, alamat = ? WHERE id_pekerja = ?",
          [trim($_POST['nama_pekerja'] ?? ''), trim($_POST['kontak'] ?? ''), trim($_POST['alamat'] ?? ''), (int)($_POST['id_pekerja_edit'] ?? 0)]
        );
        $_SESSION['notif'] = ['pesan' => 'Data pekerja berhasil diperbarui!', 'tipe' => 'sukses'];
        break;

      case 'ambil_stok_pekerja':
        $id_pekerja = (int)($_POST['id_pekerja_ambil'] ?? 0);
        $id_stok    = (int)($_POST['id_stok_ambil'] ?? 0);
        $jumlah_kg  = (int)($_POST['jumlah_kg'] ?? 0);
        $tanggal    = date('Y-m-d');

        if ($id_pekerja <= 0 || $id_stok <= 0) throw new Exception("Data tidak lengkap.");
        if ($jumlah_kg < MIN_AMBIL_KG) throw new Exception("Minimal ambil ".MIN_AMBIL_KG." kg!");

        db_begin();

        $stok = db_fetch("SELECT * FROM stok WHERE id_stok = ? FOR UPDATE", [$id_stok]);
        if (!$stok) throw new Exception("Stok tidak ditemukan.");
        if ((int)$stok['jumlah_stok'] < $jumlah_kg) throw new Exception("Tidak cukup stok tersedia.");

        $gaji = $jumlah_kg * TARIF_PER_KG;

        db_exec(
          "INSERT INTO pengambilan_stok_pekerja (id_pekerja, id_stok, tanggal_ambil, jumlah_kg, total_gaji, status)
           VALUES (?, ?, ?, ?, ?, 'Sedang dikerjakan')",
          [$id_pekerja, $id_stok, $tanggal, $jumlah_kg, $gaji]
        );

        db_exec(
          "INSERT INTO riwayat_gaji (id_pekerja, tanggal, berat_barang_kg, tarif_per_kg, total_gaji, keterangan)
           VALUES (?, ?, ?, ?, ?, 'Belum Dibayar')",
          [$id_pekerja, $tanggal, $jumlah_kg, TARIF_PER_KG, $gaji]
        );

        db_exec("UPDATE stok SET jumlah_stok = GREATEST(jumlah_stok - ?, 0) WHERE id_stok = ?", [$jumlah_kg, $id_stok]);

        updateStatusPekerjaGeneric($id_pekerja);
        db_commit();

        $_SESSION['notif'] = ['pesan' => 'Pengambilan stok & riwayat gaji tersimpan!', 'tipe' => 'sukses'];
        break;

      case 'hapus_pekerja':
        db_exec("DELETE FROM pekerja_lepas WHERE id_pekerja = ?", [(int)($_POST['id_pekerja_hapus'] ?? 0)]);
        $_SESSION['notif'] = ['pesan' => 'Data pekerja berhasil dihapus.', 'tipe' => 'sukses'];
        break;
    }
  } catch (Throwable $e) {
    try { db_rollback(); } catch (Throwable $x) {}
    $_SESSION['notif'] = ['pesan' => 'Error: '.$e->getMessage(), 'tipe' => 'error'];
  }
  $search_query = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
  header("Location: Index.php?page=pekerja" . $search_query);
  exit;
}

/* ===================== DATA UTAMA ===================== */
$search_term = $_GET['search'] ?? '';
$sql_pekerja = "SELECT pl.*,
    (SELECT COALESCE(SUM(rg.total_gaji),0) FROM riwayat_gaji rg WHERE rg.id_pekerja = pl.id_pekerja AND rg.keterangan = 'Dibayar') as total_dibayar,
    (SELECT COALESCE(SUM(rg.total_gaji),0) FROM riwayat_gaji rg WHERE rg.id_pekerja = pl.id_pekerja AND rg.keterangan = 'Belum Dibayar') as total_belum_dibayar
  FROM pekerja_lepas pl";
$params = [];
if (!empty($search_term)) { $sql_pekerja .= " WHERE pl.nama_pekerja LIKE ?"; $params[] = "%".$search_term."%"; }
$sql_pekerja .= " ORDER BY pl.nama_pekerja ASC";

try {
  $pekerja_list = db_fetch_all($sql_pekerja, $params);
  $sum_rows = db_fetch_all("SELECT keterangan, SUM(total_gaji) AS total_per_keterangan FROM riwayat_gaji GROUP BY keterangan");
  $summary = ['Dibayar'=>0,'Belum Dibayar'=>0];
  foreach ($sum_rows as $r) { $summary[$r['keterangan']] = (int)($r['total_per_keterangan'] ?? 0); }
  $summary_dibayar = $summary['Dibayar'] ?? 0;
  $summary_belum_dibayar = $summary['Belum Dibayar'] ?? 0;
} catch (Throwable $e) {
  $_SESSION['notif'] = ['pesan' => 'Error ambil data pekerja: '.$e->getMessage(), 'tipe' => 'error'];
  $pekerja_list = []; $summary_dibayar = 0; $summary_belum_dibayar = 0;
}
?>

<main class="flex-1 bg-gray-100">
  <section class="p-6 overflow-x-auto">
    <?php if (isset($_SESSION['notif'])): ?>
      <div class="mb-4 p-4 rounded-md text-white font-bold <?php echo $_SESSION['notif']['tipe'] === 'sukses' ? 'bg-green-500' : 'bg-red-500'; ?>">
        <?php echo htmlspecialchars($_SESSION['notif']['pesan']); ?>
      </div>
    <?php unset($_SESSION['notif']); endif; ?>

    <div class="flex flex-col md:flex-row md:items-center md:space-x-4 mb-4">
      <button id="btnTambahPekerja" class="flex-shrink-0 inline-flex items-center gap-2 bg-[#800020] text-white text-sm font-normal px-4 py-2 rounded shadow-sm hover:shadow-md transition-shadow mb-2 md:mb-0" type="button"><i class="fas fa-plus"></i> Tambah Pekerja</button>
      <form action="Index.php" method="GET" class="flex flex-1 max-w-md">
        <input type="hidden" name="page" value="pekerja">
        <input type="text" name="search" placeholder="Cari nama pekerja..." class="flex-grow border border-gray-300 rounded-l px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800020]" value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit" class="bg-[#800020] text-white px-6 py-2 rounded-r shadow-sm hover:shadow-md transition-shadow">Cari</button>
      </form>
    </div>

    <table class="w-full border border-gray-300 text-sm bg-white text-left">
      <thead class="bg-[#FFE9F0] text-xs text-gray-900">
        <tr>
          <th class="border border-gray-300 px-3 py-2 w-12">No.</th>
          <th class="border border-gray-300 px-3 py-2 w-40">Nama</th>
          <th class="border border-gray-300 px-3 py-2 w-32">Kontak</th>
          <th class="border border-gray-300 px-3 py-2 w-40">Total Dibayar</th>
          <th class="border border-gray-300 px-3 py-2 w-40">Total Belum Dibayar</th>
          <th class="border border-gray-300 px-3 py-2 w-40">Status Pekerja</th>
          <th class="border border-gray-300 px-3 py-2 w-52">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pekerja_list)): ?>
          <tr><td colspan="7" class="border border-gray-300 px-3 py-4 text-center text-gray-500">Data pekerja tidak ditemukan.</td></tr>
        <?php else: foreach ($pekerja_list as $i => $pekerja): ?>
          <tr>
            <td class="border border-gray-300 px-3 py-2"><?php echo $i + 1; ?>.</td>
            <td class="border border-gray-300 px-3 py-2"><?php echo htmlspecialchars($pekerja['nama_pekerja']); ?></td>
            <td class="border border-gray-300 px-3 py-2"><?php echo htmlspecialchars($pekerja['kontak']); ?></td>
            <td class="border border-gray-300 px-3 py-2 text-green-700">Rp. <?php echo number_format((int)($pekerja['total_dibayar'] ?? 0), 0, ',', '.'); ?></td>
            <td class="border border-gray-300 px-3 py-2 text-red-700">Rp. <?php echo number_format((int)($pekerja['total_belum_dibayar'] ?? 0), 0, ',', '.'); ?></td>
            <td class="border border-gray-300 px-3 py-2">
              <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo ($pekerja['status_pembayaran'] ?? '') === 'Dibayar' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                <?php echo htmlspecialchars($pekerja['status_pembayaran'] ?? 'Belum Dibayar'); ?>
              </span>
            </td>
            <td class="border border-gray-300 px-3 py-2 space-x-1 flex items-center justify-center">
              <button class="btnAmbilStok bg-yellow-600 text-white text-xs px-2 py-1 rounded inline-flex items-center gap-1"
                data-id-pekerja="<?php echo (int)$pekerja['id_pekerja']; ?>"
                data-nama-pekerja="<?php echo htmlspecialchars($pekerja['nama_pekerja']); ?>">
                <i class="fas fa-box-open"></i> Ambil Stok
              </button>
              <a href="Index.php?page=riwayat_gaji&id_pekerja=<?php echo (int)$pekerja['id_pekerja']; ?>" class="btnHistory bg-gray-500 text-white text-xs px-3 py-1 rounded inline-block">Riwayat</a>
              <button class="btnEdit bg-[#800020] text-white text-xs px-3 py-1 rounded"
                data-id-pekerja="<?php echo (int)$pekerja['id_pekerja']; ?>"
                data-nama="<?php echo htmlspecialchars($pekerja['nama_pekerja']); ?>"
                data-kontak="<?php echo htmlspecialchars($pekerja['kontak']); ?>"
                data-alamat="<?php echo htmlspecialchars($pekerja['alamat']); ?>">
                Edit
              </button>
              <button class="btnHapus bg-red-700 text-white text-xs px-3 py-1 rounded" data-id-pekerja="<?php echo (int)$pekerja['id_pekerja']; ?>">Hapus</button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <table class="w-full max-w-sm border border-gray-300 text-sm mt-4 bg-white">
      <thead class="bg-[#FFE9F0] text-gray-900 text-xs">
        <tr><th class="border border-gray-300 px-3 py-1 text-center" colspan="2">Ringkasan Finansial</th></tr>
      </thead>
      <tbody>
        <tr><td class="border border-gray-300 px-3 py-1 font-medium">Total Gaji (Status: Dibayar)</td><td class="border border-gray-300 px-3 py-1">Rp. <?php echo number_format((int)$summary_dibayar, 0, ',', '.'); ?></td></tr>
        <tr><td class="border border-gray-300 px-3 py-1 font-medium">Total Gaji (Status: Belum Dibayar)</td><td class="border border-gray-300 px-3 py-1">Rp. <?php echo number_format((int)$summary_belum_dibayar, 0, ',', '.'); ?></td></tr>
        <tr class="bg-gray-50"><td class="border border-gray-300 px-3 py-1 font-bold">Total Pekerja</td><td class="border border-gray-300 px-3 py-1 font-bold"><?php echo count($pekerja_list); ?> Orang</td></tr>
      </tbody>
    </table>
  </section>

  <!-- MODAL TAMBAH -->
  <div id="modalTambah" class="fixed inset-0 bg-black bg-opacity-50 flex hidden items-center justify-center z-50">
    <form action="" method="POST" class="bg-white p-6 shadow-md rounded w-80 relative">
      <button type="button" class="btnClose absolute top-2 right-2 text-gray-600 hover:text-gray-900 text-xl font-bold">&times;</button>
      <h2 class="text-black font-semibold text-lg mb-4">Tambah Pekerja Baru</h2>
      <input type="hidden" name="action" value="tambah_pekerja">
      <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Nama</label><input type="text" name="nama_pekerja" class="w-full px-3 py-2 border border-gray-300 rounded" required /></div>
      <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Kontak</label><input type="text" name="kontak" class="w-full px-3 py-2 border border-gray-300 rounded" required /></div>
      <div class="mb-6"><label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label><textarea name="alamat" class="w-full px-3 py-2 border border-gray-300 rounded" required></textarea></div>
      <button type="submit" class="w-full bg-[#800020] text-white py-2 rounded">Simpan</button>
    </form>
  </div>

  <!-- MODAL EDIT -->
  <div id="modalEdit" class="fixed inset-0 bg-black bg-opacity-50 flex hidden items-center justify-center z-50">
    <form action="" method="POST" class="bg-white p-6 shadow-md rounded w-80 relative">
      <button type="button" class="btnClose absolute top-2 right-2 text-gray-600 hover:text-gray-900 text-xl font-bold">&times;</button>
      <h2 class="text-black font-semibold text-lg mb-4">Edit Data Pekerja</h2>
      <input type="hidden" name="action" value="edit_pekerja">
      <input type="hidden" name="id_pekerja_edit" id="id_pekerja_edit">
      <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Nama</label><input type="text" name="nama_pekerja" id="nama_pekerja_edit" class="w-full px-3 py-2 border border-gray-300 rounded" required /></div>
      <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Kontak</label><input type="text" name="kontak" id="kontak_edit" class="w-full px-3 py-2 border border-gray-300 rounded" required /></div>
      <div class="mb-6"><label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label><textarea name="alamat" id="alamat_edit" class="w-full px-3 py-2 border border-gray-300 rounded" required></textarea></div>
      <button type="submit" class="w-full bg-[#800020] text-white py-2 rounded">Simpan Perubahan</button>
    </form>
  </div>

  <!-- MODAL HAPUS -->
  <div id="modalHapus" class="fixed inset-0 bg-black bg-opacity-50 flex hidden items-center justify-center z-50">
    <form action="" method="POST" class="bg-white p-6 shadow-md rounded w-80 relative text-center">
      <input type="hidden" name="action" value="hapus_pekerja">
      <input type="hidden" name="id_pekerja_hapus" id="id_pekerja_hapus">
      <p class="text-gray-700 mb-4">Yakin ingin menghapus data pekerja ini?</p>
      <div class="flex justify-center gap-3">
        <button type="button" class="btnClose bg-gray-400 text-white px-4 py-2 rounded">Batal</button>
        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Hapus</button>
      </div>
    </form>
  </div>

  <!-- MODAL AMBIL STOK -->
  <div id="modalAmbil" class="fixed inset-0 bg-black bg-opacity-50 flex hidden items-center justify-center z-50">
    <form action="" method="POST" class="bg-white p-6 shadow-md rounded w-96 relative">
      <button type="button" class="btnClose absolute top-2 right-2 text-gray-600 hover:text-gray-900 text-xl font-bold">&times;</button>
      <h2 class="text-black font-semibold text-lg mb-4">Ambil Stok untuk <span id="nama_pekerja_label"></span></h2>
      <input type="hidden" name="action" value="ambil_stok_pekerja">
      <input type="hidden" name="id_pekerja_ambil" id="id_pekerja_ambil">
      <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Pilih Stok</label>
        <select name="id_stok_ambil" class="w-full border border-gray-300 rounded px-3 py-2" required>
          <option value="">-- Pilih stok --</option>
          <?php foreach ($stok_list as $s): ?>
            <option value="<?php echo (int)$s['id_stok']; ?>"><?php echo htmlspecialchars($s['nama_produk'])." - ".$s['jumlah_stok']." kg (".$s['status_stok'].")"; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (kg)</label><input type="number" name="jumlah_kg" min="<?php echo MIN_AMBIL_KG; ?>" class="w-full border border-gray-300 rounded px-3 py-2" required></div>
      <button type="submit" class="w-full bg-[#800020] text-white py-2 rounded">Ambil</button>
    </form>
  </div>
</main>

<script>
document.querySelectorAll('#btnTambahPekerja, .btnEdit, .btnHapus, .btnAmbilStok, .btnClose').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const id = btn.dataset.idPekerja;
    const modalTambah=document.getElementById('modalTambah');
    const modalEdit=document.getElementById('modalEdit');
    const modalHapus=document.getElementById('modalHapus');
    const modalAmbil=document.getElementById('modalAmbil');
    if(btn.id==='btnTambahPekerja'){modalTambah.classList.remove('hidden');}
    if(btn.classList.contains('btnEdit')){
      modalEdit.classList.remove('hidden');
      document.getElementById('id_pekerja_edit').value=id;
      document.getElementById('nama_pekerja_edit').value=btn.dataset.nama;
      document.getElementById('kontak_edit').value=btn.dataset.kontak;
      document.getElementById('alamat_edit').value=btn.dataset.alamat;
    }
    if(btn.classList.contains('btnHapus')){modalHapus.classList.remove('hidden');document.getElementById('id_pekerja_hapus').value=id;}
    if(btn.classList.contains('btnAmbilStok')){modalAmbil.classList.remove('hidden');document.getElementById('id_pekerja_ambil').value=id;document.getElementById('nama_pekerja_label').textContent=btn.dataset.namaPekerja;}
    if(btn.classList.contains('btnClose')){modalTambah.classList.add('hidden');modalEdit.classList.add('hidden');modalHapus.classList.add('hidden');modalAmbil.classList.add('hidden');}
  });
});
</script>
