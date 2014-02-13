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

$options = getopt('y:r:') + ['y' => 2013, 'r' => 7.0];
$filter = function ($movie) use ($options) {
    return $movie['rating'] >= (float)($options['r']) && $movie['year'] == (int)($options['y']);
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
