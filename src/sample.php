<?php
require  "vendor/autoload.php";

$download_url = "https://package.sentsss.com/1654674450345.apk";
$save_as = "C:\Users\浩然\PhpstormProjects\RangeDownload\sss.apk";

$range_download = new RangeDownload\Download($download_url, $save_as, ['PartSize' => 100]);
$range_download->performdownloading();
;