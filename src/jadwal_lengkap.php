<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'auth.php';
include 'koneksi.php';

// Ambil semua jadwal, join ke admin
$jadwalList = $pdo->query("
  SELECT j.*, a.username 
  FROM jadwal j 
  LEFT JOIN admin a ON j.id_admin = a.id_admin 
  ORDER BY tanggal DESC, waktu_mulai DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="max-w-5xl mx-auto mt-8 p-6 bg-white rounded-lg shadow">
  <h2 class="text-2xl font-bold mb-6 text-blue-800">Jadwal Keseluruhan</h2>
  <div class="overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-700">
      <thead class="text-xs text-gray-700 uppercase bg-gray-100">
        <tr>
          <th class="py-3 px-4">Tanggal</th>
          <th class="py-3 px-4">Waktu</th>
          <th class="py-3 px-4">Jenis Kegiatan</th>
          <th class="py-3 px-4">Admin</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($jadwalList)): ?>
          <tr>
            <td colspan="4" class="text-center text-gray-500 py-8">
              <i class="fas fa-calendar-times fa-2x mb-2 text-gray-400"></i>
              <div>Belum ada jadwal.</div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($jadwalList as $jadwal): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-3 px-4"><?php echo htmlspecialchars(date('d M Y', strtotime($jadwal['tanggal']))); ?></td>
              <td class="py-3 px-4"><?php echo htmlspecialchars(date('H:i', strtotime($jadwal['waktu_mulai']))) . ' - ' . htmlspecialchars(date('H:i', strtotime($jadwal['waktu_selesai']))); ?></td>
              <td class="py-3 px-4"><?php echo htmlspecialchars($jadwal['jenis_kegiatan']); ?></td>
              <td class="py-3 px-4"><?php echo htmlspecialchars($jadwal['username'] ?? '-'); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
