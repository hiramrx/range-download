<?php
require  "vendor/autoload.php";

$download_url = "https://package.sentsss.com/1654674450345.apk";
$save_as = "/Users/hiramx/range-download/sss.apk";

$range_download = new RangeDownload\Download($download_url, $save_as, ['PartSize' => 52428800]);
$range_download->performdownloading();