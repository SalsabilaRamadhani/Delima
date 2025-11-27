<?php
// ------- KONFIG DB -------
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'salsa_ff';

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) die('Gagal konek DB: '.$mysqli->connect_error);
$mysqli->set_charset('utf8mb4');

// ------- PARAMETER -------
$kategori = $_GET['kategori'] ?? 'semua';
$periode  = $_GET['periode']  ?? 'bulanan';
$tanggal  = $_GET['tanggal']  ?? date('Y-m-01');

// ------- UTIL -------
function period_end($periode, $start) {
  switch ($periode) {
    case 'harian':   return date('Y-m-d', strtotime($start.' +1 day -1 second'));
    case 'mingguan': return date('Y-m-d', strtotime($start.' +7 days -1 second'));
    default:         return date('Y-m-t', strtotime($start));
  }
}
function label_periode($periode, $start) {
  if ($periode==='harian')   return date('d M Y', strtotime($start));
  if ($periode==='mingguan') return date('d M Y', strtotime($start)).' – '.date('d M Y', strtotime($start.' +6 days'));
  return date('F Y', strtotime($start));
}
function build_ranges($periode, $start, $rows=7) {
  $ranges=[]; $cursor=$start;
  for($i=0;$i<$rows;$i++){
    $ranges[]=['start'=>date('Y-m-d',strtotime($cursor)),'end'=>period_end($periode,$cursor),'label'=>label_periode($periode,$cursor)];
    if($periode==='harian')$cursor=date('Y-m-d',strtotime($cursor.' +1 day'));
    elseif($periode==='mingguan')$cursor=date('Y-m-d',strtotime($cursor.' +7 days'));
    else $cursor=date('Y-m-01',strtotime($cursor.' +1 month'));
  }
  return $ranges;
}
function sc($s){return htmlspecialchars($s ?? '',ENT_QUOTES,'UTF-8');}

// ------- QUERY -------
function scalar($db,$sql,$params=[]){$stmt=$db->prepare($sql);if($params){$types=str_repeat('s',count($params));$stmt->bind_param($types,...$params);} $stmt->execute();$res=$stmt->get_result();$val=0;if($res&&($row=$res->fetch_row()))$val=(float)$row[0];$stmt->close();return $val;}
function rows($db,$sql,$params=[]){$stmt=$db->prepare($sql);if($params){$types=str_repeat('s',count($params));$stmt->bind_param($types,...$params);} $stmt->execute();$res=$stmt->get_result();$out=[];if($res){while($r=$res->fetch_assoc())$out[]=$r;}$stmt->close();return $out;}

// ------- HEADER -------
$title='LAPORAN';
$sub='Periode: '.label_periode($periode,$tanggal).' (anchor: '.date('Y-m-d',strtotime($tanggal)).')';

ob_start();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?= sc($title) ?></title>
<style>
  *{box-sizing:border-box}
  body{font-family:"Segoe UI",Arial,sans-serif;font-size:12px;color:#222;margin:30px;background:#fff}
  h1{font-size:18px;margin:0 0 6px 0;color:#800020}
  h2{font-size:13px;margin:0 0 14px 0;color:#444}
  table{border-collapse:collapse;width:100%;margin-top:10px}
  th,td{border:1px solid #d1d5db;padding:8px 10px;vertical-align:middle}
  thead th{background:#FFE9F0;text-align:left;color:#800020;font-weight:600}
  tfoot td{font-weight:bold;background:#f3f4f6}
  tr:nth-child(even){background:#fafafa}
  .muted{color:#6b7280}
  .mb16{margin-bottom:16px}
  .right{text-align:right}
  .center{text-align:center}
  .header-line{border-top:3px solid #800020;margin:10px 0 20px 0}
</style>
</head>
<body>
  <h1><?= sc($title) ?></h1>
  <div class="muted mb16"><?= sc($sub) ?> • Dicetak: <?= date('d/m/Y H:i') ?></div>
  <div class="header-line"></div>

<?php
// ------- ISI TABEL -------
if ($kategori==='produksi') {
  $start=$tanggal; $end=period_end($periode,$tanggal);
  $rows=rows($mysqli,"SELECT p.nama_produk,pr.jumlah_produksi,pr.jumlah_dikemas,pr.jumlah_reject,COALESCE(pr.tgl_produksi,j.tanggal) AS tanggal_produksi FROM produksi pr JOIN produk p ON p.id_produk=pr.id_produk LEFT JOIN jadwal j ON j.id_jadwal=pr.id_jadwal WHERE COALESCE(pr.tgl_produksi,j.tanggal) BETWEEN ? AND ? ORDER BY tanggal_produksi DESC, pr.id_produksi DESC",[$start,$end]);
  echo '<table><thead><tr><th>No.</th><th>Nama Produk</th><th class="right">Produksi (Kg)</th><th class="right">Dikemas (Kg)</th><th class="right">Reject (Kg)</th><th>Tanggal</th></tr></thead><tbody>';
  if(!$rows){echo'<tr><td colspan="6" class="center muted">Tidak ada data.</td></tr>';}
  else{$i=1;foreach($rows as $r){echo'<tr>'.
    '<td class="center">'.$i++.'.</td>'.
    '<td>'.sc($r['nama_produk']).'</td>'.
    '<td class="right">'.number_format($r['jumlah_produksi']).'</td>'.
    '<td class="right">'.number_format($r['jumlah_dikemas']).'</td>'.
    '<td class="right">'.number_format($r['jumlah_reject']).'</td>'.
    '<td>'.sc(date('d-m-Y',strtotime($r['tanggal_produksi']))).'</td>'.
  '</tr>';}}
  echo '</tbody></table>';
}
else {
  echo '<div class="muted">Kategori lain dapat diaktifkan sesuai kebutuhan laporan.</div>';
}
?>
</body>
</html>
<?php
$html = ob_get_clean();

// ------- OUTPUT PDF -------
$autoload = __DIR__.'/vendor/autoload.php';
if (file_exists($autoload)) require_once $autoload;
if (class_exists('\\Mpdf\\Mpdf')) {
  try {
    $mpdf = new \Mpdf\Mpdf(['format'=>'A4-L']);
    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_'.date('Ymd_His').'.pdf','I');
    exit;
  } catch(Throwable $e){}
}
echo $html;
echo "<script>window.onload=function(){window.print();}</script>";
