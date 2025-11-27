<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'auth.php';
include 'koneksi.php';

/* Skema:
   produk(id_produk, nama_produk, ...)
   distribusi(id_distribusi, id_produk, jumlah_pesanan, tanggal_pesanan, status_pengiriman, nama_distributor, alamat_distributor)
*/

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
  header("Location: ../Index.php?page=distribusi");
  exit;
}

/* =================== HANDLE FORM ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  try {
    if (!isset($pdo) || !($pdo instanceof PDO)) throw new Exception('Koneksi $pdo tidak tersedia.');

    if ($action === 'tambah') {
      $nama   = trim($_POST['nama_distributor'] ?? '');
      $alamat = trim($_POST['alamat_distributor'] ?? '');
      $tgl    = $_POST['tgl_pesanan'] ?? date('Y-m-d');
      $status = $_POST['status_pengiriman'] ?? 'Diproses';
      $details = $_POST['detail'] ?? [];

      if ($nama === '' || $alamat === '') throw new Exception('Nama & Alamat distributor wajib diisi.');
      if (!is_array($details) || count($details) === 0) throw new Exception('Tambahkan minimal 1 produk.');

      $stmt = $pdo->prepare("
        INSERT INTO distribusi
          (nama_distributor, alamat_distributor, id_produk, jumlah_pesanan, tanggal_pesanan, status_pengiriman)
        VALUES (?,?,?,?,?,?)
      ");

      $ok = 0;
      foreach ($details as $row) {
        $id_produk = (int)($row['id_produk'] ?? 0);
        $jumlah    = (int)($row['jumlah'] ?? 0);
        if ($id_produk > 0 && $jumlah > 0) {
          $stmt->execute([$nama, $alamat, $id_produk, $jumlah, $tgl, $status]);
          $ok++;
        }
      }
      if ($ok === 0) throw new Exception('Detail produk belum lengkap.');
      $_SESSION['notif'] = ['pesan' => 'Pesanan berhasil ditambahkan!', 'tipe' => 'sukses'];

    } elseif ($action === 'edit_grup') {
      $old_nama   = $_POST['key_nama_old'] ?? '';
      $old_alamat = $_POST['key_alamat_old'] ?? '';
      $old_tgl    = $_POST['key_tanggal_old'] ?? '';
      $old_status = $_POST['key_status_old'] ?? '';

      $nama   = trim($_POST['nama_distributor'] ?? '');
      $alamat = trim($_POST['alamat_distributor'] ?? '');
      $tgl    = $_POST['tgl_pesanan'] ?? date('Y-m-d');
      $status = $_POST['status_pengiriman'] ?? 'Diproses';
      $details = $_POST['detail'] ?? [];

      if ($nama === '' || $alamat === '') throw new Exception('Nama & Alamat wajib diisi.');
      if (!is_array($details) || count($details) === 0) throw new Exception('Tambahkan minimal 1 produk.');

      $pdo->beginTransaction();
      $pdo->prepare("DELETE FROM distribusi WHERE nama_distributor=? AND alamat_distributor=? AND tanggal_pesanan=? AND status_pengiriman=?")
          ->execute([$old_nama, $old_alamat, $old_tgl, $old_status]);

      $stmtIns = $pdo->prepare("
        INSERT INTO distribusi
          (nama_distributor, alamat_distributor, id_produk, jumlah_pesanan, tanggal_pesanan, status_pengiriman)
        VALUES (?,?,?,?,?,?)
      ");
      $inserted = 0;
      foreach ($details as $row) {
        $id_produk = (int)($row['id_produk'] ?? 0);
        $jumlah    = (int)($row['jumlah'] ?? 0);
        if ($id_produk > 0 && $jumlah > 0) {
          $stmtIns->execute([$nama, $alamat, $id_produk, $jumlah, $tgl, $status]);
          $inserted++;
        }
      }
      if ($inserted === 0) throw new Exception('Detail produk tidak valid.');

      $pdo->commit();
      $_SESSION['notif'] = ['pesan' => 'Grup berhasil diperbarui!', 'tipe' => 'sukses'];

    } elseif ($action === 'hapus_grup') {
      $old_nama   = $_POST['key_nama_old'] ?? '';
      $old_alamat = $_POST['key_alamat_old'] ?? '';
      $old_tgl    = $_POST['key_tanggal_old'] ?? '';
      $old_status = $_POST['key_status_old'] ?? '';

      $pdo->prepare("DELETE FROM distribusi WHERE nama_distributor=? AND alamat_distributor=? AND tanggal_pesanan=? AND status_pengiriman=?")
          ->execute([$old_nama, $old_alamat, $old_tgl, $old_status]);

      $_SESSION['notif'] = ['pesan' => 'Grup berhasil dihapus.', 'tipe' => 'sukses'];
    }
  } catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['notif'] = ['pesan' => 'Kesalahan: '.$e->getMessage(), 'tipe' => 'error'];
  }
  header("Location: Index.php?page=distribusi");
  exit;
}

/* =================== AMBIL DATA ==================== */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  die('<div style="padding:12px;color:#b91c1c;background:#fee2e2;border:1px solid #fecaca;border-radius:6px;">Koneksi database tidak ditemukan.</div>');
}

$daftar_distribusi = $pdo->query("
  SELECT d.*, p.nama_produk
  FROM distribusi d
  JOIN produk p ON d.id_produk = p.id_produk
  ORDER BY d.tanggal_pesanan DESC, d.nama_distributor ASC
")->fetchAll(PDO::FETCH_ASSOC);

$produk_options = $pdo->query("SELECT id_produk, nama_produk FROM produk ORDER BY nama_produk")->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="flex-1 bg-gray-100">
  <section class="p-6 overflow-x-auto">
    <?php if (isset($_SESSION['notif'])): ?>
      <div class="mb-4 p-4 rounded-md text-white font-bold <?= $_SESSION['notif']['tipe']==='sukses'?'bg-green-600':'bg-red-600'; ?>">
        <?= htmlspecialchars($_SESSION['notif']['pesan']); ?>
      </div>
      <?php unset($_SESSION['notif']); ?>
    <?php endif; ?>

    <button id="btnTambah" class="mb-4 inline-flex items-center bg-[#800020] hover:bg-[#f7b5c6] text-white text-sm font-medium px-4 py-2 rounded" type="button">
      <i class="fas fa-plus"></i>&nbsp;Input Pesanan
    </button>

    <table class="w-full border border-gray-300 text-sm bg-white">
      <thead>
        <tr style="background-color:#FFCCD8" class="text-black text-left">
          <th class="border px-3 py-2">No.</th>
          <th class="border px-3 py-2">Distributor</th>
          <th class="border px-3 py-2">Alamat</th>
          <th class="border px-3 py-2">Tanggal</th>
          <th class="border px-3 py-2">Produk</th>
          <th class="border px-3 py-2">Jumlah (kg)</th>
          <th class="border px-3 py-2">Status</th>
          <th class="border px-3 py-2">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (!$daftar_distribusi) {
          echo '<tr><td colspan="8" class="text-center text-gray-500 py-4">Belum ada data distribusi.</td></tr>';
        } else {
          $groups = [];
          foreach ($daftar_distribusi as $r) {
            $key = $r['nama_distributor'].'|'.$r['alamat_distributor'].'|'.$r['tanggal_pesanan'].'|'.$r['status_pengiriman'];
            $groups[$key]['header'] = ['nama_distributor'=>$r['nama_distributor'],'alamat_distributor'=>$r['alamat_distributor'],'tanggal_pesanan'=>$r['tanggal_pesanan'],'status_pengiriman'=>$r['status_pengiriman']];
            $groups[$key]['rows'][] = $r;
          }
          $no = 1;
          foreach ($groups as $g) {
            $rowspan = count($g['rows']);
            $items = array_map(fn($r)=>['id_produk'=>(int)$r['id_produk'],'jumlah'=>(int)$r['jumlah_pesanan']],$g['rows']);
            $items_json = htmlspecialchars(json_encode($items), ENT_QUOTES);
            foreach ($g['rows'] as $i=>$r) {
              echo '<tr>';
              if ($i==0) {
                echo "<td rowspan='$rowspan' class='border px-3 py-2 align-top'>$no.</td>";
                echo "<td rowspan='$rowspan' class='border px-3 py-2 align-top'>".htmlspecialchars($g['header']['nama_distributor'])."</td>";
                echo "<td rowspan='$rowspan' class='border px-3 py-2 align-top'>".htmlspecialchars($g['header']['alamat_distributor'])."</td>";
                echo "<td rowspan='$rowspan' class='border px-3 py-2 align-top'>".htmlspecialchars($g['header']['tanggal_pesanan'])."</td>";
              }
              echo "<td class='border px-3 py-2'>".htmlspecialchars($r['nama_produk'])."</td>";
              echo "<td class='border px-3 py-2'>".(int)$r['jumlah_pesanan']."</td>";
              if ($i==0) {
                echo "<td rowspan='$rowspan' class='border px-3 py-2 align-top'>".htmlspecialchars($g['header']['status_pengiriman'])."</td>";
                echo "<td rowspan='$rowspan' class='border px-3 py-2 align-top'>
                      <button class='btnEditGrup text-white text-xs px-3 py-1 rounded' style='background-color:#FFCCD8;color:black;' data-nama-old='".htmlspecialchars($g['header']['nama_distributor'],ENT_QUOTES)."' data-alamat-old='".htmlspecialchars($g['header']['alamat_distributor'],ENT_QUOTES)."' data-tanggal-old='".htmlspecialchars($g['header']['tanggal_pesanan'],ENT_QUOTES)."' data-status-old='".htmlspecialchars($g['header']['status_pengiriman'],ENT_QUOTES)."' data-items='".$items_json."'>Edit</button>
                      <button class='btnHapusGrup bg-red-700 text-white text-xs px-3 py-1 rounded ml-2' data-nama-old='".htmlspecialchars($g['header']['nama_distributor'],ENT_QUOTES)."' data-alamat-old='".htmlspecialchars($g['header']['alamat_distributor'],ENT_QUOTES)."' data-tanggal-old='".htmlspecialchars($g['header']['tanggal_pesanan'],ENT_QUOTES)."' data-status-old='".htmlspecialchars($g['header']['status_pengiriman'],ENT_QUOTES)."'>Hapus</button>
                      </td>";
              }
              echo '</tr>';
            }
            $no++;
          }
        }
        ?>
      </tbody>
    </table>
  </section>

  <!-- MODAL FORM -->
  <div id="modalForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <form method="POST" id="formDistribusi" class="bg-white p-6 rounded w-[700px] shadow relative">
      <input type="hidden" name="action" id="formAction" value="tambah">
      <input type="hidden" id="key_nama_old" name="key_nama_old">
      <input type="hidden" id="key_alamat_old" name="key_alamat_old">
      <input type="hidden" id="key_tanggal_old" name="key_tanggal_old">
      <input type="hidden" id="key_status_old" name="key_status_old">

      <button type="button" class="btnClose absolute top-2 right-3 text-gray-600 hover:text-black text-xl font-bold">&times;</button>
      <h2 id="formTitle" class="text-lg font-semibold mb-4">Tambah Pesanan</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div><label class="block text-sm">Nama Distributor</label>
          <input type="text" name="nama_distributor" id="formNama" class="w-full border px-3 py-2 rounded" required>
        </div>
        <div><label class="block text-sm">Tanggal Pesanan</label>
          <input type="date" name="tgl_pesanan" id="formTanggal" class="w-full border px-3 py-2 rounded" value="<?=date('Y-m-d')?>">
        </div>
        <div class="md:col-span-2"><label class="block text-sm">Alamat Distributor</label>
          <input type="text" name="alamat_distributor" id="formAlamat" class="w-full border px-3 py-2 rounded" required>
        </div>
        <div><label class="block text-sm">Status Pengiriman</label>
          <select name="status_pengiriman" id="formStatus" class="w-full border px-3 py-2 rounded">
            <option value="">-- Pilih Status --</option>
            <option value="Diproses">Diproses</option>
            <option value="Dikirim">Dikirim</option>
            <option value="Selesai">Selesai</option>
          </select>
        </div>
      </div>

      <div class="mt-4">
        <div class="text-sm font-semibold mb-2">Detail Produk</div>
        <div id="produkListMulti"></div>
        <button type="button" id="btnAddRow" class="mt-3 bg-[#FFCCD8] hover:bg-[#f7b5c6] text-black text-xs px-3 py-1 rounded">+ Tambah Produk</button>
      </div>

      <div class="mt-5">
        <button type="submit" class="w-full bg-[#FFCCD8] hover:bg-[#f7b5c6] text-black py-2 rounded">Simpan</button>
      </div>
    </form>
  </div>

  <!-- MODAL HAPUS -->
  <div id="modalHapusGrup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="w-[340px] border border-gray-300 shadow-md p-6 bg-white rounded relative">
      <button type="button" id="btnCloseHapusGrup" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
      <h2 class="font-semibold mb-3 text-lg">Hapus Semua Item di Grup?</h2>
      <p class="text-gray-700 mb-5 text-sm">Seluruh item dengan header yang sama akan dihapus.</p>
      <form method="POST" class="flex justify-end space-x-3">
        <input type="hidden" name="action" value="hapus_grup">
        <input type="hidden" id="hg_nama" name="key_nama_old">
        <input type="hidden" id="hg_alamat" name="key_alamat_old">
        <input type="hidden" id="hg_tanggal" name="key_tanggal_old">
        <input type="hidden" id="hg_status" name="key_status_old">
        <button type="button" id="btnCancelHapusGrup" class="border border-gray-300 text-gray-900 text-sm px-4 py-2 rounded hover:bg-gray-100">Batal</button>
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded">Ya, Hapus Grup</button>
      </form>
    </div>
  </div>
</main>

<script>
const modal=document.getElementById('modalForm'),hapus=document.getElementById('modalHapusGrup');
const openM=()=>modal.classList.remove('hidden'),closeM=()=>modal.classList.add('hidden');
const closeH=()=>hapus.classList.add('hidden');
document.addEventListener('click',e=>{if(e.target.classList.contains('btnClose'))closeM();if(e.target===modal)closeM();if(e.target===hapus)closeH();});
document.getElementById('btnCloseHapusGrup').onclick=closeH;document.getElementById('btnCancelHapusGrup').onclick=closeH;

const produkList=document.getElementById('produkListMulti');const produkOptions=`<?php foreach($produk_options as $p){echo '<option value="'.$p['id_produk'].'">'.htmlspecialchars($p['nama_produk']).'</option>';}?>`;
let rowCount=0;
function addRow(p='',j=''){rowCount++;const id=rowCount;const r=document.createElement('div');r.className='produk-row border border-gray-200 rounded p-3 mb-2';r.innerHTML=`
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
<div><label class="text-sm">Produk</label><select name="detail[${id}][id_produk]" class="w-full border px-3 py-2 rounded"><option value="">-- Pilih Produk --</option>${produkOptions}</select></div>
<div><label class="text-sm">Jumlah (kg)</label><input type="number" name="detail[${id}][jumlah]" min="1" class="w-full border px-3 py-2 rounded"></div>
<div class="text-right md:text-left"><button type="button" class="bg-red-600 text-white text-xs px-3 py-1 rounded btnRemoveRow">Hapus</button></div></div>`;
produkList.appendChild(r);if(p)r.querySelector('select').value=p;if(j)r.querySelector('input').value=j;}
function resetDetail(){produkList.innerHTML='';rowCount=0;}
document.addEventListener('click',e=>{if(e.target.closest('#btnAddRow')){e.preventDefault();addRow();}if(e.target.classList.contains('btnRemoveRow')){e.preventDefault();e.target.closest('.produk-row').remove();}});
document.getElementById('btnTambah').onclick=()=>{document.getElementById('formAction').value='tambah';document.getElementById('formTitle').textContent='Tambah Pesanan';
['key_nama_old','key_alamat_old','key_tanggal_old','key_status_old'].forEach(i=>document.getElementById(i).value='');
document.getElementById('formNama').value='';document.getElementById('formAlamat').value='';document.getElementById('formTanggal').value='<?=date('Y-m-d')?>';document.getElementById('formStatus').value='';
resetDetail();addRow();openM();};
document.querySelectorAll('.btnEditGrup').forEach(b=>b.onclick=()=>{const n=b.dataset.namaOld,a=b.dataset.alamatOld,t=b.dataset.tanggalOld,s=b.dataset.statusOld,it=JSON.parse(b.dataset.items||'[]');
document.getElementById('formAction').value='edit_grup';document.getElementById('formTitle').textContent='Edit Grup';
document.getElementById('formNama').value=n;document.getElementById('formAlamat').value=a;document.getElementById('formTanggal').value=t;document.getElementById('formStatus').value=s;
document.getElementById('key_nama_old').value=n;document.getElementById('key_alamat_old').value=a;document.getElementById('key_tanggal_old').value=t;document.getElementById('key_status_old').value=s;
resetDetail();it.length?it.forEach(x=>addRow(x.id_produk,x.jumlah)):addRow();openM();});
document.querySelectorAll('.btnHapusGrup').forEach(b=>b.onclick=()=>{document.getElementById('hg_nama').value=b.dataset.namaOld;document.getElementById('hg_alamat').value=b.dataset.alamatOld;document.getElementById('hg_tanggal').value=b.dataset.tanggalOld;document.getElementById('hg_status').value=b.dataset.statusOld;hapus.classList.remove('hidden');});
</script>
