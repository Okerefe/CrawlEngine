<?php
require_once('crawl.php');


// Test for CrawlEngine's Login and Crawl Feature........................................................

$engine = new CrawlEngine("http://crawlengine.atwebpages.com/", "http://crawlengine.atwebpages.com/", "http://crawlengine.atwebpages.com/");

$engine->add_login_details('username', 'example@gmail.com');
$engine->add_login_details('password', 'examplepassword');



$param_1 = new FindParams();
$param_1->set_tag("h3");
$param_1->set_attribute(['id' => 'full_name']);
$param_1->set_attribute(['class' => 'name']);
$param_1->set_search_index(1);

$param_2 = new FindParams();
$param_2->set_tag("h3");
$param_2->set_attribute(['id' => 'prof']);
$param_2->set_attribute(['class' => 'name']);
$param_2->set_search_index(1);


$searches = array();
$searches[] = $param_1;
$searches[] = $param_2;

// // print_r($searches);
$reply = $engine->get_info($searches);
print_r($reply);


// Test for CrawlEngine's Static Crawl Feature........................................................

$param_static = new FindParams();
$param_static->set_tag("p");
$param_static->set_attribute(['id' => 'function']);
$param_static->set_search_index(1);

$param_static_2 = new FindParams();
$param_static_2->set_tag("p");
$param_static_2->set_attribute(['id' => 'about_us']);
$param_static_2->set_search_index(1);




$static_params = array();
$static_params[] = $param_static;
$static_params[] = $param_static_2;

$ans = CrawlEngine::get_offline_info($static_params, "http://crawlengine.atwebpages.com/");
print_r($ans);

exit;


?>