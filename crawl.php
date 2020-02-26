<?php


/*
	Hardwork Beats Talent When Talent Doesn't Work Hard....................................
*/
// Crawl Engine: A class created to help in the simplification of scraping Websites using Regex
// Helper Class Includes Find Params
class FindParams {
	
	private $index_of_search;
	private $tags = array();
	private $attributes = array();
	
	public static $class_name = "FindParams";
	
	function __construct() {}
	
	public function set_search_index($index = "") {
		if($index == "") {return false;}
		$this->index_of_search = $index;
	}

	public function get_search_index() {
		return $this->index_of_search;
	}
	
	public function set_tag($tag = "") {
		if($tag == "") {return false;}
		$this->tags[] = $tag;
	}
	
	public function set_attribute($attribute = array()) {
		if(empty($attribute)) {return false;}
		foreach($attribute as $key => $value) {$this->attributes[$key] = $value;}
	}
	
	public function get_tags() {
		return $this->tags;
	}
	
	public function get_attributes() {
		return $this->attributes;
	}
}



class CrawlEngine {
	
	public $input_fields = array(); 
	public $populated_fields = array(); 
	public static $headers = array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.8) Gecko/20061025 Firefox/1.5.0.8","origin:http://www.google.com/bot.html","accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9", "accept-language:en-US,en;q=0.5","accept-encoding:gzip, deflate","connection:keep-alive","upgrade-insecure-requests:1","keep-alive:300","accept-charset:ISO-8859-1,utf-8;q=0.7,*;q=0.7");
		
	public $login_details = array();
	public $login_url;
	public $content_url;
	public $landing_url;
	public $get_page_content;
	private $hostUrl;
	
	
	function __construct($landing_url = "", $login_url = "", $content_url = "") {
		if($login_url == "" || $landing_url == "") {
			return false;
		}
		// $page_content = ;
		$this->resolve_post_fields(self::get_page_content($landing_url));
		$this->login_url = $login_url;
		$this->landing_url = $landing_url;
		$this->content_url = $content_url;
	}
	
	
	public static function get_page_content($url) {
		$cookie_file_path = dirname(__FILE__) . "\cookie.txt";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		
		//set the cookie the site has for certain features, this is optional
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
		curl_setopt($ch, CURLOPT_COOKIE, "cookiename=0");
		curl_setopt($ch, CURLOPT_USERAGENT,
			"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:66.0) Gecko/20100101 Firefox/66.0");
		curl_setopt($ch, CURLOPT_ENCODING,'gzip, deflate');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, "http://www.google.com/bot.html");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		curl_setopt($ch, CURLOPT_HTTPHEADER, self::$headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_POST, 1);
		$content = curl_exec($ch);
		curl_close($ch);
		return $content;
	}
	
	public static function in_assoc_array_key ($key, $array) {
		foreach($array as $key_pair => $value_pair) {
			if($key_pair == $key) {return true;}
		}
		return false;
	}
	
	private function resolve_post_fields($page_content) {
		// Post fields are resolved irrespective of which form they are
		// So it is assumed that there is only one form on the Page
		
		$reg_1 = '/<input.+?name="(.*?)"/m';
		$reg_2 = '/<input.+?name=\'(.*?)\'/m';


		$reg_3 = '/<input.+?value="(.*?)".+?name="(.*?)"/m';
		$reg_4 = '/<input.+?value=\'(.*?)\'.+?name=\'(.*?)\'/m';
		$reg_5 = '/<input.+?name=\'(.*?)\'.+?value=\'(.*?)\'/m';
		$reg_6 = '/<input.+?name="(.*?)".+?value="(.*?)"/m';
		
		preg_match_all($reg_1, $page_content, $matches_1);
		preg_match_all($reg_2, $page_content, $matches_2);


		preg_match_all($reg_3, $page_content, $matches_3);
		preg_match_all($reg_4, $page_content, $matches_4);
		preg_match_all($reg_5, $page_content, $matches_5);
		preg_match_all($reg_6, $page_content, $matches_6);
		
		
		
		foreach($matches_1[1] as $mat) {
			if(!in_array($mat, $this->input_fields)) {				
				$this->input_fields[] = $mat;
			}			
		}
		foreach($matches_2[1] as $mat) {
			if(!in_array($mat, $this->input_fields)) {				
				$this->input_fields[] = $mat;
			}			
		}


		$i = 0;
		foreach($matches_3[2] as $mat) {
			if($mat != "" && self::in_assoc_array_key($matches_3[1][$i], $this->populated_fields) == false) {	
				if($matches_3[1][$i] != "") {
					if(in_array($matches_3[1][$i], $this->input_fields)) { unset($this->input_fields[array_search($matches_3[1][$i], $this->input_fields)]); }
					$this->populated_fields[$matches_3[1][$i]] = $mat;
				}
			}
			$i++;
		}

		$i = 0;
		foreach($matches_4[2] as $mat) {
			if($mat != "" && self::in_assoc_array_key($matches_4[1][$i], $this->populated_fields) == false) {	
				if($matches_4[1][$i] != "") {
					if(in_array($matches_4[1][$i], $this->input_fields)) { unset($this->input_fields[array_search($matches_4[1][$i], $this->input_fields)]); }
					$this->populated_fields[$matches_4[1][$i]] = $mat;
				}
			}
			$i++;
		}

		$i = 0;
		foreach($matches_5[2] as $mat) {
			if($mat != "" && self::in_assoc_array_key($matches_5[1][$i], $this->populated_fields) == false) {	
				if($matches_5[1][$i] != "") {
					if(in_array($matches_5[1][$i], $this->input_fields)) { unset($this->input_fields[array_search($matches_5[1][$i], $this->input_fields)]); }
					$this->populated_fields[$matches_5[1][$i]] = $mat;
				}
			}
			$i++;
		}

		$i = 0;
		foreach($matches_6[2] as $mat) {
			if($mat != "" && self::in_assoc_array_key($matches_6[1][$i], $this->populated_fields) == false) {	
				if($matches_6[1][$i] != "") {
					if(in_array($matches_6[1][$i], $this->input_fields)) { unset($this->input_fields[array_search($matches_6[1][$i], $this->input_fields)]); }
					$this->populated_fields[$matches_6[1][$i]] = $mat;
				}
			}
			$i++;
		}
	}
		
		
	public function add_login_details($key = "", $value = "") {
		if($key != "") {
			if(in_array($key, $this->input_fields)) {				
				$this->login_details[$key] = $value;
				return true;
			}
		}
		return false;		
	}
	
	public function access_page() {
		$post_string = "";
		$i = 0;
		
		foreach($this->login_details as $key => $value) {
			if($i == 0) {
				$post_string .= $key . "=" . $value;
			} else {
				$post_string.= "&{$key}={$value}";
			}
			$i++;
		}		
		foreach($this->populated_fields as $key => $value) {
				$post_string.= "&{$key}={$value}";
		}
		
		$cookie_file_path = dirname(__FILE__) . "\cookie.txt";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_URL, $this->login_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		
		//set the cookie the site has for certain features, this is optional
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
		curl_setopt($ch, CURLOPT_COOKIE, "cookiename=0");
		curl_setopt($ch, CURLOPT_USERAGENT,
			"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:66.0) Gecko/20100101 Firefox/66.0");
		curl_setopt($ch, CURLOPT_ENCODING,'gzip, deflate');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, $this->landing_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		curl_setopt($ch, CURLOPT_HTTPHEADER, self::$headers);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		curl_exec($ch);
		// exit;

		
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
		curl_setopt($ch, CURLOPT_URL, $this->content_url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_POST, 1);
		$this->get_page_content = curl_exec($ch);
		curl_close($ch);
		
	}
	
	
	public function get_info($params = array()) {
		$this->access_page();
		$regs = array();
		$reply = array();
		
		foreach($params as $param) {
			if(!is_a($param, FindParams::$class_name)) {
				continue;
			}
			$tags = $param->get_tags();
			$tag_string= "";
			for($i=0; $i< sizeof($tags); $i++) {
				if($i == (sizeof($tags) - 1)) {
					$tag_string.= "<{$tags[$i]}.*?";	
				} else {
					$tag_string.= "<{$tags[$i]}>.*?";	
				}
			}
			
			$attributes = $param->get_attributes();
			$att_string = "";
			$size = sizeof($attributes);
			$i = 0;
			foreach($attributes as $att_key => $att_value) {
				if($i == ($size -1)) {
					$att_string.= ".*?{$att_key}.*?=\"{$att_value}\".*?>(.*?)<";					
				} else{					
					$att_string.= ".*?{$att_key}.*?=\"{$att_value}\"";
				}
				$i++;
			}
			$reg = "/{$tag_string}{$att_string}/m";
			preg_match_all($reg, $this->get_page_content, $matches);
			$index = ($param->get_search_index() - 1);
			
			if(is_array($matches[1])) {
				$reply[] = $matches[1][$index];
				echo $matches[1][$index];
			} else {
				$reply[] = $matches[1];
				echo $matches[1];
			}
		}
		return $reply;
	}
}

?>