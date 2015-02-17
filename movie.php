<?php
    session_start();
    include_once "lib/lib.php";
    connect();
        
    Login();
    
    $user = $_SESSION["user"];

    $userId = $user['id'];
    $login = $user['login'] ? $user['login'] : $user['vkid'];
    
    $movieId = array_key_exists('id', $_GET) ? (int)$_GET['id'] : -1;
    $movie = false;
    $desc = false;
    $ignore = false;
    $torrents = false;
    $bestQuality = array('quality'=>"CAMRIP", 'translateQuality'=>"ORIGINAL");

    if ($movieId != -1) {
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE id = $movieId");
        $movie = mysqli_fetch_assoc($sqlresult);
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
    $metaDescription = "скачать торрент " . $bestQuality['quality'] . ", перевод - " . $bestQuality['translateQuality'];
    $metaTitle .= "$title - свежие торренты";
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
        <div style="float:left; width: 25%; padding-right: 10px;">
            <img itemprop="image" class="bigPoster" src='<?php echo array_key_exists("PosterRu", $desc)?$desc['PosterRu']:$desc['Poster']; ?>' />
            <table class="movDesc table table-condensed">
            <tbody>
                <tr class="movDescName">
                    <td itemprop="name" colspan="2">
                        <?php echo array_key_exists('titleRu', $desc)?$desc['titleRu']:$desc['Title']; ?>
                    </td>
                </tr>
                <tr> 
                    <td colspan="2"> 
                        <span itemprop="alternateName"><?php echo array_key_exists('titleRu', $desc)?($desc['Title']." "):""; ?></span>
                        <?php echo $desc['Year']; ?>
                    </td>
                </tr>
                <tr itemtype="http://schema.org/AggregateRating" itemscope itemprop="aggregateRating">
                    <td>IMDB</td>
                    <td>
                    <a itemprop="ratingValue" title="открыть на IMDB" target='_blank' href='<?php echo "http://www.imdb.com/title/".$movie['imdbid'];?>/'>
                        <?php echo $desc['imdbRating']; ?>
                    </a>
                    <meta itemprop="bestRating" content="10" />
                    <meta itemprop="worstRating" content="0" >
                    </td>
                </tr>
                <tr itemtype="http://schema.org/AggregateRating" itemscope itemprop="aggregateRating">
                    <td>КиноПоиск</td>
                    <td>
                    <a itemprop="ratingValue" title="открыть на КиноПоиске" target='_blank' href='<?php echo "http://www.kinopoisk.ru/film/".$desc['kinopoiskId'];?>/'>
                        <?php echo $desc['kinopoiskRating']; ?>
                    </a>
                    <meta itemprop="bestRating" content="10"/>
                    <meta itemprop="worstRating" content="0"/>
                    </td>
                </tr>
                <tr itemtype="http://schema.org/AggregateRating" itemscope itemprop="aggregateRating">
                    <td>Metascore</td>
                    <td itemprop="ratingValue"><?php echo $desc['Metascore']; ?>
                    <meta itemprop="bestRating" content="100"/>
                    <meta itemprop="worstRating" content="0"/>
                    </td>
                </tr>
                <tr>
                    <td>премьера</td>
                    <td><?php echo date("j M Y",$movie['Release']); ?></td>
                    <meta itemprop="datePublished" content="<?php echo date("c",$movie['Release']); ?>"/>
                </tr>
                <tr>
                    <td>жанр</td>
                    <td itemprop="genre"><?php echo $desc['Genre']; ?></td>
                </tr>
                <tr>
                    <td>режиссер</td>
                    <td itemprop="director"><?php echo $desc['Director']; ?></td>
                </tr>
                <tr>
                    <td>сценарист</td>
                    <td itemprop="author"><?php echo $desc['Writer']; ?></td>
                </tr>
                <tr>
                    <td>актеры</td>
                    <td itemprop="actors"><?php echo $desc['Actors']; ?></td>
                </tr>
            </tbody>
            </table>
        </div>
        <div style="float:right; width: 75%">
            <div id="movieTrailerDiv" class="embed-responsive embed-responsive-16by9" style="display:none;">
                <iframe id="movieTrailer" class="embed-responsive-item" allowfullscreen></iframe>
            </div>
            <table id='torrentTable' class='table table-striped table-hover'>
                <thead>
                    <th>качество</th>
                    <th>перевод</th>
                    <th>скачать торрент</th>
                    <th>размер</th>
                    <th>сиды</th>
                    <th>личеры</th>
                    <th>дата</th>
                </thead>
                <tbody>
                <?php
                    foreach($torrents as $cur) {
                        echo "<tr>\n";
                        echo "\t<td data-order='" . qualityToRool($cur['quality']) . "'>".$cur['quality']."</td>\n";
                        echo "\t<td data-order='" . translateQualityToRool($cur['translateQuality']) . "'>".$cur['translateQuality']."</td>\n";
                        echo "\t<td><a target='_blank' href='".$cur['link']."'>".$cur['description']."</a></td>\n";
                        echo "\t<td>".$cur['size']."</td>\n";
                        echo "\t<td>".$cur['seed']."</td>\n";
                        echo "\t<td>".$cur['leech']."</td>\n";
                        echo "\t<td data-order='" . strtotime($cur['added']) . "'>".date("M\&\\nb\sp;j", strtotime($cur['added']))."</td>\n";
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