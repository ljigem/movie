<?php
require 'vendor/autoload.php';

use Guzzle\Http\Client;

$client = new Client('http://yify-torrents.com/');

use Predis\Client as RedisClient;
use Predis\Profile\ServerProfile;

$redis = function ($db = 0) {
    return new RedisClient(['database' => $db], ['profile' => ServerProfile::get('2.8')]);
};
$redis0 = $redis();
$redis12 = $redis(12);

$set = 1;
$limit = 50;
$movieCount = 10000;
do {
    print "$set start...\n";
    $query = array(
        'quality' => '1080p',
        'limit' => $limit,
        'set' => $set,
        'sort' => 'year',
        'order' => 'desc',
    );
    $uri = '/api/list.json'.'?'.http_build_query($query);
    $results = $client->get($uri)->send()->json();
    if (isset($results['status']) && $results['status'] == 'fail') {
        break;
    }
    $movies = $results['MovieList'];
    foreach ($movies as $movie) {
        $id = $movie['MovieID'];
        $title = $movie['MovieTitleClean'];
        $year = $movie['MovieYear'];
        $quality = $movie['Quality'];
        $number = $movie['ImdbCode'];
        $uri = $movie['ImdbLink'];
        $torrent = $movie['TorrentUrl'];
        $hash = $movie['TorrentHash'];
        $magnet = $movie['TorrentMagnetUrl'];
        $new = compact('id', 'title', 'year', 'quality', 'number', 'uri', 'torrent', 'hash', 'magnet');
        $redis12->hmset('YIFY:'.$id, $new);
    }
    $redis0->set('YIFY:set', $set);
    print "$set ok!\n";
    $set++;
} while (true);
