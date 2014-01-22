<?php
require 'vendor/autoload.php';

use Predis\Client as RedisClient;
use Predis\Profile\ServerProfile;

$redis = function ($db = 0) {
    return new RedisClient(['database' => $db], ['profile' => ServerProfile::get('2.8')]);
};
$redis0 = $redis();
$redis13 = $redis(13);
$redis12 = $redis(12);

$filter = function ($movie) {
    //return $movie['rating'] >= 7.0 && $movie['year'] == 2012 && $movie['votes'] >= 10000;
    return $movie['rating'] >= 7.0 && $movie['year'] == 2012;
};
$output = 'torrent';

$exist = 0;
$next = 0;
do {
    $results = $redis12->scan($next, ['count' => 100, 'match' => 'YIFY:tt*']);
    $next = $results[0];
    $keys = $results[1];
    foreach ($keys as $key) {
        $movie = $redis13->hgetall(str_replace('YIFY', 'IMDb', $key));
        if ($movie && $filter($movie)) {
            print $redis12->hget($key, $output)."\n";
        }
    }
} while ($next !== 0);
