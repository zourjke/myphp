<?php

// 设置数据库文件路径（绝对路径）
$SQLitePath = './ubtvSQLite.db';

// 连接到SQLite数据库
$pdo = new PDO("sqlite:$SQLitePath");

// 创建表
$pdo->exec("CREATE TABLE IF NOT EXISTS ublive (
    id INTEGER NOT NULL,
	shichang INTEGER NOT NULL,
	shicha INTEGER NOT NULL,
	time INTEGER NOT NULL,
    url TEXT NOT NULL
)");

// GET ID
$id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($id)) {
    http_response_code(400);
    exit("错误：缺少参数 'id'");
}

if ($id == 'txt') {
$ublive_list2 = './ublive.list2.txt';
  if (empty($ublive_list2)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo file_get_contents($ublive_list2 );
    exit;
  }
$ublive_list = file_get_contents('./ublive.list.txt');
$serverUrl = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"];
$newfile = str_replace('http://foxtv.zone.id/ublive.php', $serverUrl, $ublive_list);
file_put_contents($ublive_list2,$newfile);
header('Content-Type: text/plain; charset=utf-8');
echo $newfile;
exit;
}




// 查询id是否存在
$stmt = $pdo->prepare("SELECT 1 FROM ublive WHERE id = ?");
$stmt->execute([$id]);
// 不存在就干
if (empty($stmt->fetchColumn())) {
//echo intval(time())."\n";
$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'http://foxtv.zone.id/ublive.php?id='.$id,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => [
    'User-Agent: okhttp/5.0.0-alpha.14',
    'Accept-Encoding: deflate',
  ],
]);
$response = curl_exec($curl);
curl_close($curl);
//echo $response;
$tiao_zhuan_de_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL); // 跳转后的地址

preg_match('/#EXT-X-MEDIA-SEQUENCE:(\d+)/', $response, $matches);
$m3u_shijian = intval($matches[1]); // M3U 里面的时间
$m3u_shijian = intval($m3u_shijian+3);

preg_match('/#EXT-X-TARGETDURATION:(\d+)/', $response, $matches);
$m3u_shichang = intval($matches[1]); // M3U 里面的时长

$xainzaideshijian = intval(time()); // 现在的时间

$shicha = intval($xainzaideshijian/$m3u_shichang-$m3u_shijian); // 时差
$timestamp = intval($xainzaideshijian/$m3u_shichang-$shicha); // timestamp = M3U 里面的时间

//echo $m3u_shijian." M3U 里面的时间\n";
//echo $m3u_shijian." M3U 里面的时间加8\n";
//echo $m3u_shichang." M3U 里面的时长\n";
//echo $xainzaideshijian." 现在的时间\n";
//echo $shicha." 时差\n";
//echo $timestamp." timestamp 应等于M3U时间\n";

// 写入数据库
$stmt = $pdo->prepare("INSERT INTO ublive (id, shichang, shicha, time, url) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$id, $m3u_shichang, $shicha, $xainzaideshijian, $tiao_zhuan_de_url]);
}





// 查询数据库 time 对比时间差
$stmt = $pdo->prepare("SELECT * FROM ublive WHERE id = :id");
$stmt->execute([':id' => $id]);
$ublive = $stmt->fetch(PDO::FETCH_ASSOC);
$m3u_shichang = $ublive['shichang'];
$shicha = $ublive['shicha'];
$pdo_time = $ublive['time'];
$url = $ublive['url'];
$shi_jian_cha = intval(time()) - $pdo_time;
//echo $ublive;

// 数据库存入的时间大于 1小时 重新获取数据
if ($shi_jian_cha > 1800) {
$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => [
    'User-Agent: okhttp/5.0.0-alpha.14',
    'Accept-Encoding: deflate',
  ],
]);
$response = curl_exec($curl);
curl_close($curl);

preg_match('/#EXT-X-MEDIA-SEQUENCE:(\d+)/', $response, $matches);
$m3u_shijian = intval($matches[1]); // M3U 里面的时间
$m3u_shijian = intval($m3u_shijian+3);

preg_match('/#EXT-X-TARGETDURATION:(\d+)/', $response, $matches);
$m3u_shichang = intval($matches[1]); // M3U 里面的时长

$xainzaideshijian = intval(time()); // 现在的时间

$shicha = intval($xainzaideshijian/$m3u_shichang-$m3u_shijian); // 时差
$timestamp = intval($xainzaideshijian/$m3u_shichang-$shicha); // timestamp = M3U 里面的时间

//echo $m3u_shijian." M3U 里面的时间\n";
//echo $m3u_shijian." M3U 里面的时间加8\n";
//echo $m3u_shichang." M3U 里面的时长\n";
//echo $xainzaideshijian." 现在的时间\n";
//echo $shicha." 时差\n";
//echo $timestamp." timestamp 应等于M3U时间\n";

// 更新数据
$stmt = $pdo->prepare("UPDATE ublive SET shichang = ?, shicha = ?, time = ? WHERE id = ?");
$stmt->execute([$m3u_shichang, $shicha, $xainzaideshijian, $id]);
}




$xainzaideshijian = intval(time()); // 现在的时间
$timestamp = intval($xainzaideshijian/$m3u_shichang-$shicha); // timestamp = M3U 里面的时间
$url_1 = substr($url, 0, -5);

//header('Content-Type: text/plain;charset=UTF-8'); 
header('Content-Type: application/vnd.apple.mpegurl;charset=UTF-8'); 

$m3u8 = "#EXTM3U"."\r\n";
$m3u8.= "#EXT-X-VERSION:3\n";
$m3u8.= "#EXT-X-TARGETDURATION:".$m3u_shichang."\n";
$m3u8.= "#EXT-X-MEDIA-SEQUENCE:".$timestamp."\n";
$m3u8.= "#EXT-X-INDEPENDENT-SEGMENTS\n";
for ($i=0; $i<9; $i++) {
$m3u8.= "#EXTINF:".$m3u_shichang.",\n";
$m3u8.= $url_1.$timestamp.".ts\n";
$timestamp = $timestamp+1;
}
echo $m3u8;


?>