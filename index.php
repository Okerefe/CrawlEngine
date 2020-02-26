<?php
require_once('crawl.php');
ini_set('max_execution_time', '30000');
// $engine = new CrawlEngine("https://www.scalablepath.com/login", "https://www.scalablepath.com/login");
$engine = new CrawlEngine("https://example.org/login/", "https://example.org/login/", "https://example.org/profile/");

// 



$engine->add_login_details('uid', 'example@gmail.com');
$engine->add_login_details('pwd', 'testpassword');



$param_1 = new FindParams();
$param_1->set_tag("td");
$param_1->set_attribute(['align' => 'left']);
$param_1->set_attribute(['valign' => 'bottom']);
$param_1->set_search_index(1);

$param_2 = new FindParams();
$param_2->set_tag("td");
$param_2->set_attribute(['align' => 'left']);
$param_2->set_attribute(['valign' => 'bottom']);
$param_2->set_search_index(2);

$param_3 = new FindParams();
$param_3->set_tag("td");
$param_3->set_attribute(['align' => 'left']);
$param_3->set_attribute(['valign' => 'bottom']);
$param_3->set_search_index(3);

$searches = array();
$searches[] = $param_1;
$searches[] = $param_2;
$searches[] = $param_3;

// print_r($searches);
$reply = $engine->get_info($searches);
print_r($reply);

// print_r($engine->input_fields);
// print_r($engine->populated_fields);

// echo $engine->get_login();
exit;


?>