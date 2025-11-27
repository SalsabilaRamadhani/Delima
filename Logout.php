<?php
session_start();

// Jika tombol logout diklik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    session_unset();
    session_destroy();
    header("Location: Login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Logout Confirmation</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
  <form action="Logout.php" method="POST" class="w-80 p-6 border border-gray-300 shadow-md bg-white rounded-lg">
    <h2 class="font-semibold text-black text-lg mb-3">Konfirmasi Logout</h2>
    <p class="text-gray-700 mb-6 text-sm leading-relaxed">
      Apakah Anda yakin ingin keluar dari sistem?
    </p>
    <div class="flex justify-end space-x-3">
      <button
        type="submit"
        name="confirm_logout"
        value="1"
        class="text-white text-sm font-medium px-4 py-2 rounded transition-all"
        style="background-color:#800020;"
        onmouseover="this.style.backgroundColor='#a3323b';"
        onmouseout="this.style.backgroundColor='#800020';"
      >
        Keluar
      </button>
      <button
        type="button"
        onclick="history.back()"
        class="border border-gray-400 text-gray-700 text-sm font-normal px-4 py-2 rounded hover:bg-gray-100 transition-all"
      >
        Batal
      </button>
    </div>
  </form>
</body>
</html>
