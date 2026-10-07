<?php
$dir = __DIR__ . '/public/uploads/videos';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Check some direct fashion / styling / model video links
$testCandidates = [
    'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
    'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4',
    'https://archive.org/download/RunwayFashionShow/RunwayFashionShow.mp4',
    'https://archive.org/download/fashion_show_2020/fashion_show_2020.mp4',
    'https://archive.org/download/FashionShow_201604/FashionShow.mp4',
    'https://archive.org/download/FashionWalk/FashionWalk.mp4',
    'https://archive.org/download/ModelPhotoshoot/ModelPhotoshoot.mp4',
    'https://archive.org/download/TailoringSuit/TailoringSuit.mp4',
    'https://raw.githubusercontent.com/intel-iot-devkit/sample-videos/master/face-demographics-walking-and-pause.mp4',
    'https://raw.githubusercontent.com/opencv/opencv/master/samples/data/vtest.avi',
    'https://github.com/intel-iot-devkit/sample-videos/raw/master/classroom.mp4',
    'https://github.com/intel-iot-devkit/sample-videos/raw/master/store-aisle-detection.mp4',
];

foreach ($testCandidates as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $len = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    curl_close($ch);
    echo "$code ($type, $len bytes) -> $url\n";
}
