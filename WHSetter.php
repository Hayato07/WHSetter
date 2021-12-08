<?php

require_once __DIR__ . "/vendor/autoload.php";

use WHSetter\Util;
// use PHPHtmlParser\Dom;
use voku\helper\HtmlDomParser;

if (count($argv) < 2) {
    echo "require at least 2 arguments. \n";
    return;
}

// 対象のHTMLファイルまでのpath
$target_file = $argv[1];
$target_real_file = realpath($target_file);

if (!$target_real_file || !file_exists($target_real_file)) {
    echo "specified target file does not exists. \n";
    return;
}

$target_real_dir = add_trailing_slash(pathinfo($argv[1])["dirname"]);


// 対象の出力ディレクトリまでのpath
// todo ディレクトリなら、ファイルパスに変換する
$output_dir = $argv[2] ?? $target_real_dir;
if (!file_exists($output_dir)) {
    mkdir($output_dir, '0777', TRUE);
    chmod($output_dir, 0777);
}
$output_real_path = realpath($output_dir);

$output_real_file = add_trailing_slash($output_real_path) . "outputs2." . pathinfo($argv[1])["extension"];

// $dom = new Dom;
// $dom->loadFromFile($target_real_file);
$dom = HtmlDomParser::file_get_html($target_real_file);

// img width height追加処理
$imgs = $dom->findMulti("img");
$sources = $dom->findMulti("picture > source");
foreach ($sources as $source) {
    $imgs[] = $source;
}

foreach ($imgs as $img) {
    // srcsetは同じアスペクト比であることが基本なので、srcset部分の考慮はしない
    $src = $img->getAttribute('src');

    // todo https, httpへの対応
    $img_info = getimagesize(realpath($target_real_dir . $src));
    $width = $img_info[0];
    $height = $img_info[1];

    $img_width = $img->getAttribute('width');
    if (is_null($img_width) || $img_width === "") {
        $img->setAttribute('width', $width);
    }

    $img_height = $img->getAttribute('height');
    if (is_null($img_height) || $img_height === "") {
        $img->setAttribute('height', $height);
    }
}

$dom->save($output_real_file);
echo "success \n";

function add_trailing_slash($string)
{
    return rtrim($string, "/") . '/';
}
