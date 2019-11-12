<?php
set_time_limit(0);
$url = 'https://lenta.ru/rss';
$news = simplexml_load_file($url);
if ($news !== false) {
    $count = 0;
    foreach ($news->channel->item as $items) {
        $count++;
        echo trim($items->title) . PHP_EOL;
        echo trim($items->link) . PHP_EOL;
        echo trim($items->description) . PHP_EOL;
        if ($count > 4) {
            unset($news);
            break;
        }
    }
} else {
    echo 'Новостная лента не доступна';
}