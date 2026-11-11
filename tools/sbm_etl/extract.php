<?php
// Ekstrak teks per halaman dari PDF ke tools/sbm_etl/out/<nama>/pNNN.txt  (jalankan: php extract.php "<pdf>" <nama>)
require __DIR__ . '/../../vendor/autoload.php';
[$_, $pdf, $name] = $argv;
$dir = __DIR__ . '/out/' . $name;
@mkdir($dir, 0777, true);
$doc = (new Smalot\PdfParser\Parser())->parseFile($pdf);
foreach ($doc->getPages() as $i => $page) {
    file_put_contents(sprintf('%s/p%03d.txt', $dir, $i + 1), $page->getText());
}
echo count($doc->getPages()), " halaman\n";
