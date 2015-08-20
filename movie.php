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
    $bestQuality = array('quality'=>"CAMRIP", 'translateQuality'=>"ORIGINAL");

    if ($movieId != -1) {
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

        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE movieId = $movieId ORDER BY seed DESC LIMIT 500");
        while ($row = mysqli_fetch_assoc($sqlresult)) {
            if (qualityToRool($row['quality']) > qualityToRool($bestQuality['quality'])
            || (qualityToRool($row['quality']) == qualityToRool($bestQuality['quality']) && translateQualityToRool($row['translateQuality']) > translateQualityToRool($bestQuality['translateQuality']) ) ) {
                $bestQuality['quality'] = $row['quality'];
                $bestQuality['translateQuality'] = $row['translateQuality'];
            }
            $torrents[] = $row;
        }
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
    $metaDescription = "скачать торрент " . $bestQuality['quality'] . ", перевод: " . $bestQuality['translateQuality'] . ", Кинопоиск: " . @$desc['kinopoiskRating'] . ", премьера: " . date("j M Y",$movie['Release']);
    $imgSrc = array_key_exists("PosterRu", $desc)?$desc['PosterRu']:$desc['Poster'];
    @$metaTitle .= "$title - свежие торренты";
    include "html/head.php";
?>

  <body>
    <script src="js/googleApiAuth.js"></script>
    <script type="text/javascript">

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
          var q = '<?php echo html_entity_decode($title); ?>';
          gapi.client.setApiKey('AIzaSyDtncZmxqR9jZlLDT00WbT1FwdYGkoY8G0');
          var request = gapi.client.youtube.search.list({
            q: q + " trailer",
            part: 'id'
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
    
    <div class="jumbotron">
    <div class="container" itemscope itemtype="http://schema.org/Movie">
    <?php if ($movie) { ?>
        <div class="movLeft">
            <link rel="image_src" href='<?php echo $imgSrc; ?>' >
            <img itemprop="image" class="bigPoster" src='<?php echo $imgSrc; ?>' />
            <table class="movDesc table table-condensed">
            <tbody>
                <tr class="movDescName">
                    <td itemprop="name" colspan="2">
                        <?php echo array_key_exists('titleRu', $desc)?$desc['titleRu']:$desc['Title']; ?>
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
                    <a itemprop="ratingValue" title="открыть на КиноПоиске" target='_blank' href='<?php echo "http://www.kinopoisk.ru/film/".$desc['kinopoiskId'];?>/'>
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
                        message: "<?=$title;?>\n\n" + 
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
                        echo "\t<td class='hidden-xs'>$aS".$cur['description']."$aE</td>\n";
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
        echo "movie with this id not found"
    ?>

    <?php
        include "html/footer.php";
    ?>
    </div>
    </div>

  </body>
</html>