<?php
    include_once "lib/lib.php";
    connect();
        
    Login();
    
    $user = $_SESSION["user"];

    $userId = $user['id'];
    $login = $user['login'] ? $user['login'] : $user['vkid'];
    
    $movieId = array_key_exists('id', $_GET) ? (int)$_GET['id'] : -1;
    $user_country = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);
    $ban = ($user_country == 'Russian Federation') && in_array($movieId, $BANNED);
    $movie = false;
    $desc = false;
    $ignore = false;
    $torrents = false;
    $legals = false;
    $bestQuality = array('quality'=>"CAMRIP", 'translateQuality'=>"ORIGINAL");

    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE id = $movieId");
    $movie = mysqli_fetch_assoc($sqlresult);
    if (!$movie) {
        header('HTTP/1.0 404 Not Found');
        echo "Not Found";
        exit();
    }
    $desc = json_decode($movie['description'], true);

    $movie['Release'] = strtotime($desc['Released']);
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT movieId FROM userignore WHERE userId = $userId AND movieId = $movieId ORDER BY id DESC");
    $ignore = (bool)mysqli_fetch_assoc($sqlresult);

    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE movieId = $movieId AND type=0 ORDER BY seed DESC LIMIT 500");
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        if (qualityToRool($row['quality']) > qualityToRool($bestQuality['quality'])
        || (qualityToRool($row['quality']) == qualityToRool($bestQuality['quality']) && translateQualityToRool($row['translateQuality']) > translateQualityToRool($bestQuality['translateQuality']) ) ) {
            $bestQuality['quality'] = $row['quality'];
            $bestQuality['translateQuality'] = $row['translateQuality'];
        }
        $torrents[] = $row;
    }

    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE movieId = $movieId AND type=1 LIMIT 500");
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        if (qualityToRool($row['quality']) > qualityToRool($bestQuality['quality'])
        || (qualityToRool($row['quality']) == qualityToRool($bestQuality['quality']) && translateQualityToRool($row['translateQuality']) > translateQualityToRool($bestQuality['translateQuality']) ) ) {
            $bestQuality['quality'] = $row['quality'];
            $bestQuality['translateQuality'] = $row['translateQuality'];
        }
        $legals[] = $row;
    }

    $bestQuality['quality'] = mb_strtolower($bestQuality['quality'], "UTF-8");
    $bestQuality['translateQuality'] = mb_strtolower($bestQuality['translateQuality'], "UTF-8");

    if ($login == 'wise guest' || $login == 'guest') {
        $userId = -1;
        $login = false;        
    }
?>
<!DOCTYPE html>
<html lang="en">
<?php
    $title = array_key_exists("titleRu",$desc) ? $desc['titleRu'] : $desc['Title'];
    $metaDescription = $bestQuality['quality'] . ", перевод: " . $bestQuality['translateQuality'] . ", Кинопоиск: " . @$desc['kinopoiskRating'] . ", премьера: " . date("j M Y",$movie['Release']);
    $imgSrc = array_key_exists("PosterRu", $desc)?$desc['PosterRu']:$desc['Poster'];
    @$metaTitle .= "$title - свежие торренты";
    include "html/head.php";
?>

  <body>
    <script src="js/googleApiAuth.js"></script>
    <script type="text/javascript">

        <?php if ($legals) { ?>
        $(document).ready(function() {
            $('#legalTable').DataTable({
                searching: false,
                paging: false,
                ordering: true,
                order: [[2, "asc" ], [3, "asc"], [5, "asc"], [6, "asc"]],
                autoWidth: true,
                info: false
            });
        });
        <?php } ?>

        $(document).ready(function() {
            $('#torrentTable').DataTable({
                searching: false,
                paging: false,
                ordering: true,
                order: [[ 0, "desc" ], [ 1, "desc" ], [ 4, "desc" ]],
                autoWidth: true,
                info: false
            });
        });

        // Search for a specified string.
        function search() {
          gapi.client.setApiKey('AIzaSyBHs2qdWW-24RMwG9IZdtjFh2dxJKFlIi4');
          var request = gapi.client.youtube.search.list({
            q: "<?=html_entity_decode($title)?> <?=$desc['Year']?> трейлер",
            part: 'id',
            type: 'video',
            publishedAfter: "<?=date(DateTime::RFC3339, $movie['Release'] - 3600*24*365)?>"
          });
          request.execute(function(response) {
            var videoId = response.items[0].id.videoId;
            $('#movieTrailer').attr('src', '//www.youtube.com/embed/'+videoId);
            $('#movieTrailerDiv').show();
          });
        }    

        // After the API loads, call a function to enable the search box.
        function handleAPILoaded() {
            search();
        }
    </script>
    <script src="https://apis.google.com/js/client.js?onload=googleApiClientReady"></script>

    <?php
        $liactive = "";
        include "html/navbar.php";
    ?>
    
    <div class="container" itemscope itemtype="http://schema.org/Movie">
    <?php if ($movie) { ?>
        <?php $movieTitle = htmlspecialchars(array_key_exists('titleRu', $desc)?$desc['titleRu']:$desc['Title']); ?>
        <div class="movLeft">
            <link rel="image_src" href='<?php echo $imgSrc; ?>' >
            <img itemprop="image" alt="<?=$movieTitle?>" class="bigPoster" src='<?php echo $imgSrc; ?>' />
            <table class="movDesc table table-condensed">
            <tbody>
                <tr class="movDescName">
                    <td itemprop="name" colspan="2">
                        <?php echo $movieTitle; ?>
                    </td>
                </tr>
                <tr> 
                    <td colspan="2"> 
                        <span itemprop="alternateName"><?php echo array_key_exists('titleRu', $desc)?(@$desc['Title']." "):""; ?></span>
                        <?php echo $desc['Year']; ?>
                    </td>
                </tr>
                <tr itemtype="http://schema.org/AggregateRating" itemscope itemprop="aggregateRating">
                    <td>IMDB</td>
                    <td>
                    <a itemprop="ratingValue" title="открыть на IMDB" target='_blank' href='<?php echo "http://www.imdb.com/title/".$movie['imdbid'];?>/'>
                        <?php echo @$desc['imdbRating']; ?>
                    </a>
                    <meta itemprop="bestRating" content="10" />
                    <meta itemprop="worstRating" content="0" >
                    </td>
                </tr>
                <tr itemtype="http://schema.org/AggregateRating" itemscope itemprop="aggregateRating">
                    <td>КиноПоиск</td>
                    <td>
                    <a itemprop="ratingValue" title="открыть на КиноПоиске" target='_blank' href='<?php echo KINOPOISKROOT."/film/".$desc['kinopoiskId'];?>/'>
                        <?php echo @$desc['kinopoiskRating']; ?>
                    </a>
                    <meta itemprop="bestRating" content="10"/>
                    <meta itemprop="worstRating" content="0"/>
                    </td>
                </tr>
                <!--<tr itemtype="http://schema.org/AggregateRating" itemscope itemprop="aggregateRating">
                    <td>Metascore</td>
                    <td itemprop="ratingValue"><?php echo @$desc['Metascore']; ?>
                    <meta itemprop="bestRating" content="100"/>
                    <meta itemprop="worstRating" content="0"/>
                    </td>
                </tr>-->
                <tr>
                    <td>премьера</td>
                    <td><?php echo date("j M Y",$movie['Release']); ?></td>
                    <meta itemprop="datePublished" content="<?php echo date("c",$movie['Release']); ?>"/>
                </tr>
                <?php if (array_key_exists("ReleaseDVD", $desc)) { ?>
                <tr>
                    <td>на DVD</td>
                    <td><?php echo date("j M Y",strtotime($desc['ReleaseDVD'])); ?></td>
                </tr>
                <?php } ?>
                <tr>
                    <td>жанр</td>
                    <td itemprop="genre"><?php echo array_key_exists("жанр", $desc)?$desc['жанр']:@$desc['Genre']; ?></td>
                </tr>
                <tr>
                    <td>режиссер</td>
                    <td itemprop="director"><?php echo array_key_exists("режиссер", $desc)?$desc['режиссер']:@$desc['Director']; ?></td>
                </tr>
                <!--<tr>
                    <td>сценарист</td>
                    <td itemprop="author"><?php echo array_key_exists("сценарий", $desc)?$desc['сценарий']:@$desc['Writer']; ?></td>
                </tr>
                <tr>
                    <td>продюсер</td>
                    <td itemprop="author"><?php echo array_key_exists("продюсер", $desc)?$desc['продюсер']:@$desc['Producer']; ?></td>
                </tr>-->
                <tr>
                    <td>актеры</td>
                    <td itemprop="actors"><?php echo array_key_exists("актеры", $desc)?$desc['актеры']:@$desc['Actors']; ?></td>
                </tr>
                <tr>
                    <td colspan="2"> 
                        <span itemprop="description"><?php echo array_key_exists('plotRu', $desc)?$desc['plotRu']:""; ?></span>
                    </td>
                </tr>
            </tbody>
            </table>

        <?php if (isAdmin($user['id'])) {?>
        <!-- VK Share -->
        <pre id="vkResult"></pre>
        <script type="text/javascript">
        function postVK() {
            $.post( "ajax.php", { method: "vkUploadPhoto", movieId: <?=$movieId?> })
                .done(function( data ) {
                $('#vkResult').html(data);
                obj = JSON.parse(data);
                VK.Api.call(
                    'wall.post', 
                    {
                        //owner_id: 19309348,
                        owner_id: -87710543,
                        from_group: 1,
                        message: "<?=$title;?>\n" + 
                                "качество: <?=$bestQuality['quality']?> <?=$bestQuality['translateQuality']?>\n" + 
                                "премьера: <?=date("j M Y",$movie['Release'])?>\n" + 
                                "Кинопоиск: <?=@$desc['kinopoiskRating']?>\n" + 
                                "жанр: <?=array_key_exists("жанр", $desc)?$desc['жанр']:@$desc['Genre']?>\n" + 
                        //        "режиссер: <?=array_key_exists("режиссер", $desc)?$desc['режиссер']:@$desc['Director']?>\n" + 
                        //        "актеры: <?=array_key_exists("актеры", $desc)?$desc['актеры']:@$desc['Actors']?>\n" +
                                "<?="http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"?>",
                        attachments:  "photo" + obj.response[0].owner_id + "_" + obj.response[0].id + ",<?="http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"?>",
                    }, 
                    function(r) {
                        $('#vkResult').html(JSON.stringify(r));
                    }
                );
            });
        }
        function updateMovie() {
            $.post("ajax.php", { method: "updateMovie", imdbid: "<?=$movie['imdbid']?>", kpid: "<?=$movie['kpid']?>" })
                .done(function(data) {
                    $('#vkResult').html(data);
                });
        }
        </script>
        <input type="button" value="VK" onclick="postVK()"/>
        <input type="button" value="Update" onclick="updateMovie()"/>
        <?php } ?>
        
        </div>
        <div class="movRight">
            <div id="movieTrailerDiv" class="embed-responsive embed-responsive-16by9" style="display:none;">
                <iframe id="movieTrailer" class="embed-responsive-item" allowfullscreen></iframe>
            </div>

            <?php if ($legals) { ?>
            <table id='legalTable' class='table table-striped table-hover bg-success' cellspacing="0" width="100%">
                <thead>
                    <th>легальный просмотр</th>
                    <th>аренда:</th>
                    <th>SD</th>
                    <th>HD</th>
                    <th>покупка:</th>
                    <th>SD</th>
                    <th>HD</th>
                    <th class='hidden-xs'>дата</th>
                </thead>
                <tbody>
                <?php
                    foreach($legals as $cur) {
                        $aS = "<a target='_blank' href='".$cur['link']."'>";
                        $aE = "</a>";
                        $desc = json_decode($cur['description'], true);
                        $inf = 1<<20;
                        echo "<tr>\n";
                        echo "\t<td><img style='width: 12px; vertical-align: baseline' src='".getImgFromLink($cur['link'])."'/> $aS".$desc['title']."$aE</td>\n";
                        echo "\t<td></td>\n";
                        if (array_key_exists('free', $desc['options']))
                            echo "\t<td data-order=0>0<sup>*с рекламой</sup></td>\n";
                        else
                            echo "\t<td data-order=".($desc['options']['rent_sd']??$inf).">".$desc['options']['rent_sd']."</td>\n";
                        echo "\t<td data-order=".($desc['options']['rent_hd']??$inf).">".$desc['options']['rent_hd']."</td>\n";
                        echo "\t<td></td>\n";
                        if (array_key_exists('sub_trial', $desc['options']))
                            echo "\t<td data-order=".($desc['options']['sub_trial']??$inf).">".$desc['options']['sub_trial']."<sup>*триал</sup></td>\n";
                        else
                            echo "\t<td data-order=".($desc['options']['buy_sd']??$inf).">".$desc['options']['buy_sd']."</td>\n";
                        if (array_key_exists('sub', $desc['options']))
                            echo "\t<td data-order=".($desc['options']['sub']??$inf).">".$desc['options']['sub']."<sup>*подписка</sup></td>\n";
                        else
                            echo "\t<td data-order=".($desc['options']['buy_hd']??$inf).">".$desc['options']['buy_hd']."</td>\n";
                        echo "\t<td data-order='" . strtotime($cur['added']) . "' class='hidden-xs'>".date("M\&\\nb\sp;j", strtotime($cur['added_tracker']?$cur['added_tracker']:$cur['added']))."</td>\n";
                        echo "</tr>\n";
                    }
                ?>
                </tbody>
            </table>
                
            <?php } ?>

            <?php if ($ban) { ?>
                <b>Ссылки на торренты недоступны в Вашей стране (<?=$user_country.":".$_SERVER['REMOTE_ADDR']?>) по просьбе правообладателя</b>
            <?php } ?>
            <table id='torrentTable' class='table table-striped table-hover' cellspacing="0" width="100%">
                <thead>
                    <th>качество</th>
                    <th>перевод</th>
                    <th class='hidden-xs'>скачать торрент</th>
                    <th>размер</th>
                    <th>сиды</th>
                    <th class='hidden-xs'>личеры</th>
                    <th class='hidden-xs'>дата</th>
                </thead>
                <tbody>
                <?php
                    if ($torrents)
                    foreach($torrents as $cur) {
                        $aS = "<a target='_blank' href='".$cur['link']."'>";
                        $aE = "</a>";
                        if ($ban)
                            $aS = $aE = "";
                        echo "<tr>\n";
                        echo "\t<td data-order='" . qualityToRool($cur['quality']) . "'>$aS".$cur['quality']."$aE</td>\n";
                        echo "\t<td data-order='" . translateQualityToRool($cur['translateQuality']) . "'>$aS".$cur['translateQuality']."$aE</td>\n";
                        echo "\t<td class='hidden-xs'><img style='width: 12px; vertical-align: baseline' src='".getImgFromLink($cur['link'])."'/> $aS".$cur['description']."$aE</td>\n";
                        echo "\t<td>".$cur['size']."</td>\n";
                        echo "\t<td>".$cur['seed']."</td>\n";
                        echo "\t<td class='hidden-xs'>".$cur['leech']."</td>\n";
                        echo "\t<td data-order='" . strtotime($cur['added']) . "' class='hidden-xs'>".date("M\&\\nb\sp;j", strtotime($cur['added_tracker']?$cur['added_tracker']:$cur['added']))."</td>\n";
                        echo "</tr>\n";
                    }
                ?>
                </tbody>
            </table>
        </div>
        <div style="clear:both"></div>
    <?php } else
        echo "movie with this id not found";
    ?>

    </div>
    <?php
        include "html/footer.php";
    ?>

  </body>
</html>
