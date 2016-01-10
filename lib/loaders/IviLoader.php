<?php
include_once(__DIR__."/AbstractLoader.php");
include_once(__DIR__.'/../defines.php');
include_once(__DIR__.'/../lib.php');
include_once(__DIR__.'/../simple_html_dom.php');

class IviLoader extends AbstractLoader {

    private $result;
    private $listId;
    
    function __construct($listId) {
        $this->result = array();
        $this->listId = $listId;
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
                if (in_array("EST", $row["content_paid_types"]))
                    $movie["size"] = 299;
                if (in_array("TVOD", $row["content_paid_types"]))
                    $movie["size"] = 199;
                $movie["description"] = "IVI";
                if (in_array("SVOD", $row["content_paid_types"]))
                    $movie["description"] = "IVI+ (подписка)";
                $movie["description"] .= " : " . $movie["title"] . " (" . $movie["year"] . ")";

                $movie['quality'] = "WEB";
                $movie['translateQuality'] = "ЛИЦЕНЗИЯ";
                $movie['type'] = 1;
                $this->result[] = $movie;
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
                "from" => 0,
                "to" => 128,
                "app_version" => 870
            ));

        \RollingCurl::$rc->get($link, null, null, array("callback"=>array($this, "getIviCallback")) );
    }

    function getResult() {
        return (array)($this->result);
    }

}
?>