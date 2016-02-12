<?php

function echoCharityForm($all = false) {
    $vars = array(
        "WWF" => array(
            "shopId" => 13981,
            "scid" => 5825,
            "glyphicon" => "leaf",
            "texts" => array("помочь природе"),
            "imgs" => array("wwf_0.jpg", "wwf_1.jpg", "wwf_2.jpg", "wwf_3.jpg"),
            ),
        "фонд \"Подари жизнь\"" => array(
            "shopId" => 10631,
            "scid" => 8474,
            "glyphicon" => "heart",
            "texts" => array("подарить жизнь"),
            "imgs" => array("pz_0.png"),
            ),
        "фонд \"Линия жизни\"" => array(
            "shopId" => 311328,
            "scid" => 2079,
            "glyphicon" => "heart-empty",
            "texts" => array("спасти ребенка"),
            "imgs" => array("lz_0.png", "lz_1.jpg"),
            ),
        "Wikipedia" => array(
            "shopId" => 0,
            "scid" => 2579,
            "glyphicon" => "info-sign",
            "texts" => array("поддержать Википедию"),
            "imgs" => array("wiki_0.jpg"),
            ),
        );

    $titles = array_keys($vars);
    if (!$all)
        $titles = array(array_rand($vars));
    
    foreach($titles as $title) {
        $shopid = $vars[$title]["shopId"];
        $scid = $vars[$title]["scid"];
        $glyphicon = $vars[$title]["glyphicon"];
        $text = mb_strtoupper($vars[$title]["texts"][array_rand($vars[$title]["texts"])], "UTF-8");
        $img = $vars[$title]["imgs"][array_rand($vars[$title]["imgs"])];
        $sum = 50;
        ?>
        <div class='movie'>
            <form id="form-charity-<?=$shopid?>" action="https://money.yandex.ru/eshop.xml" method="post" target="_blank">
                <input name="shopId" value="<?=$shopid?>" type="hidden"/>
                <input name="scid" value="<?=$scid?>" type="hidden"/>
                <input name="sum" value="<?=$sum?>" type="hidden">
                <?php if ($shopid != 10631) { ?>
                    <input name="paymentType" value="AC" type="hidden"/>
                <?php } ?>
                
                <a title='пожертвовать <?=$sum?>₽ в <?=$title?>' href="#" onclick="document.getElementById('form-charity-<?=$shopid?>').submit();">
                    <img class='poster' alt='пожертвовать в <?=$title?>' src='img/charity/<?=$img?>' />
                </a>
                <div class='movieTitle legal'>
                    <?php if (!defined("allCharity")) { ?>
                    Очистить карму
                    <?php } ?>
                    <div class='movieQuality'>
                        <span class="glyphicon glyphicon-<?=$glyphicon?>"></span>
                        <?=$text?>
                    </div>
                </div>
            </form> 
        </div>
        <?php
    }
}
    if (defined("allCharity"))
        echoCharityForm(true);
    else
        echoCharityForm(false);
?>