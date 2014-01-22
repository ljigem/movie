<?php
require 'vendor/autoload.php';

use Goutte\Client;

$client = new Client();

use Predis\Client as RedisClient;
use Predis\Profile\ServerProfile;

$redis = function ($db = 0) {
    return new RedisClient(['database' => $db], ['profile' => ServerProfile::get('2.8')]);
};
$redis0 = $redis();
$redis13 = $redis(13);

$begin = 2013;
$end = 1900;

for ($year = $begin; $year >= $end; $year--) {
    $start = 1;
    $highVotes = true;
    while ($highVotes) {
        print "$year: $start start...\n";

        $query = array(
            'at' => 0,
            'sort' => 'num_votes,desc',
            'start' => "$start",
            'title_type' => 'feature',
            'year' => "$year,$year"
        );
        $uri = 'http://www.imdb.com/search/title'.'?'.http_build_query($query);

        $movies = [];
        $crawler = $client->request('GET', $uri);
        $crawler->filter('tr.detailed')->each(function ($node) use (&$movies, $year) {
            $link = $node->filter('td.title > a');
            $uri = $link->link()->getUri();
            preg_match_all('|title/(\w*)|', $uri, $number);
            $number = $number[1][0];
            $title = trim($link->text());
            $rating = trim($node->filter('span.rating-rating > span.value')->text());
            $votes = str_replace(',', '', trim($node->filter('td.sort_col')->text()));
            $movies[$number] = compact('uri', 'number', 'title', 'rating', 'votes', 'year');
        });
        foreach ($movies as $number => $movie) {
            $redis13->hmset('IMDb:'.$number, $movie);
        }
        $redis0->hmset('IMDb:Crawler', compact('year', 'start'));

        print "$year: $start ok!\n";

        $highVotes = end($movies)['votes'] > 5000;
        $start += 50;
        unset($movies);
        unset($crawler);
    }
}
