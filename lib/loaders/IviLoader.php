<?php
include_once(__DIR__."/AbstractLoader.php");
include_once(__DIR__.'/../defines.php');
include_once(__DIR__.'/../lib.php');
include_once(__DIR__.'/../simple_html_dom.php');

class IviLoader extends AbstractLoader {

    private $result;
    private $listId;
    private $from;
    private $to;
    
    function __construct($listId, $from = 0, $to = 99) {
        $this->result = array();
        $this->listId = $listId;
        $this->from = $from;
        $this->to = $to;
    }
    
    function getIviCallback($response, $info) {
        //some code
        $msg = $info['http_code'] . " :: " . $info['url'] . " fetched in " . $info['total_time'];
        if ($info['http_code'] != 200) {
            $this->logger->warning($msg);
            return;
        }
        $this->logger->info($msg);

        $json = json_decode($response, true);
        if (array_key_exists("result", $json)) {
            foreach($json["result"] as $row) {
                $movie = array();
                $movie["link"] = "http://www.ivi.ru/watch/" . $row["id"];
                $movie["title_approx"] = $row["orig_title"]?$row["orig_title"]:$row["title"];
                $movie["title"] = $row["title"];
                $movie["year"] = $row["year"];
                $movie["size"] = 0;
                $movie['quality'] = "WEB";
                $movie['translateQuality'] = "ЛИЦЕНЗИЯ";
                $movie['type'] = 1;
                $movie['seed'] = $movie['leech'] = 0;
                $costLink = "https://api.ivi.ru/mobileapi/billing/v1/purchase/content/options/?app_version=870&session=f3359fa8169254457_1460382423-EqsdiLvfoZoeS0UvAnRQw&id=".$row['id'];
                $costRes = file_get_contents_curl($costLink) . "\n";
                if ($costRes)
                    $costRes = json_decode($costRes, true);
                if (array_key_exists('result', $costRes) && array_key_exists('purchase_options', $costRes['result'])) {
                    if (count($costRes['result']['purchase_options'])) {
                        foreach($costRes['result']['purchase_options'] as $option) {
        		            $added = &$this->result[];
        		            $added = $movie;
                            $added["description"] = "IVI " . $option['product_title'];
        		            $added["size"] = $option['price'];
        		            $added["link"].="?type=" . $option['product_identifier'];
                            if (trySkip($added))
                                array_pop($this->result);
                        }
                    } else {
    		            $added = &$this->result[];
    		            $added = $movie;
                        $added["description"] = "IVI";
    		            $added["size"] = $option['price'];
    		            $added["link"].="?type=free";
                        if (trySkip($added))
                            array_pop($this->result);
                    }
                } else
                    $this->logger->warning("no cost for IVI movie " . $row['id']);
            }
        } else 
            $this->logger->warning("no result for IVI collection " . $this->listId);
        /*
		$html = str_get_html($response);
		if (!$html) {
		    $this->logger->warning("failed to convert DOM");
		    return;
		}

        $ul = $html->find('ul[class=gallery]', 0);
        if ($ul)
            foreach($ul->find('li') as $li) {
                $movie = array();
                $movie['link'] = "http://www.ivi.ru" . $li->find('a', 0)->href;
                $movie['title_approx'] = $li->find("span[class=title]", 0)->plaintext;
                $free = (strpos($li->class, 'blockbuster') === false);
                $movie['size'] = $free ? 0 : 299;
                
                $movie['description'] = "IVI";
                $movie['quality'] = "WEB";
                $movie['translateQuality'] = "ЛИЦЕНЗИЯ";
                $movie['type'] = 1;
                $this->result[] = $movie;
    		}*/

		$this->logger->info(count($this->result) . " new links found");
    }

    function load() {
        $this->result = array();
        
        $link = "https://api.ivi.ru/mobileapi/collection/catalog/v5/?" . http_build_query(array(
                "fields" => implode(",", array("id","title","orig_title","year","content_paid_types")),
                "sort" => "priority_in_collection",
                "fake" => 0,
                "withpreorderable" => 0,
                "id" => $this->listId,
                "from" => $this->from,
                "to" => $this->to,
                "app_version" => 870
            ));

        \RollingCurl::$rc->get($link, null, null, array("callback"=>array($this, "getIviCallback")) );
    }

    function getResult() {
        return (array)($this->result);
    }

}
?>