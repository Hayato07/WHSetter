<?php

require_once __DIR__ . "/vendor/autoload.php";

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

$dom = HtmlDomParser::file_get_html($target_real_file);

// img width height追加処理
$imgs = $dom->findMulti("img");
$sources = $dom->findMulti("picture > source");
foreach ($sources as $source) {
    $imgs[] = $source;
}

foreach ($imgs as $img) {
    // srcsetは同じアスペクト比であることが基本なので、srcset部分の考慮はしない
    // sourceタグの場合には、srcsetを読み込む
    $src = ($img->tagName === "source") 
            ? $img->getAttribute('srcset')
            : $img->getAttribute('src');

    if(empty($src)){
        var_dump("srcが設定されていないsourceまたは、imgがあります");
        continue;
    }

    // todo https, httpへの対応
    if(strpos($src, '.svg') === false) {
        $img_info = getimagesize(realpath($target_real_dir . $src));
        $width = $img_info[0] ?? false;
        $height = $img_info[1] ?? false;
    } else {
        $svg = new SimpleXMLElement(realpath($target_real_dir . $src), 0, true);
        $svg_attributes = $svg->attributes();
        $width = $svg_attributes['width'] ?? false;
        $height = $svg_attributes['height'] ?? false;
    }

    if($width === false || $height === false) {
        var_dump(realpath($target_real_dir . $src));
        continue;
    }


    $img_width = $img->getAttribute('width');
    if (is_null($img_width) || $img_width === "") {
        $img->setAttribute('width', round($width));
    }

    $img_height = $img->getAttribute('height');
    if (is_null($img_height) || $img_height === "") {
        $img->setAttribute('height', round($height));
    }
}

$dom->save($output_real_file);
echo "success \n";

function add_trailing_slash($string)
{
    return rtrim($string, "/") . '/';
}
