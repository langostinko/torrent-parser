<?php
    session_start();
    include_once "lib/lib.php";
    connect();

    vkAuth();

    if (array_key_exists("logout", $_GET)) {
        if(isset($_SESSION['user']))
            unset($_SESSION['user']);       
        if(isset($_SESSION['showSettings']))
            unset($_SESSION['showSettings']);      
        header("Location: http://cinema.todeliver.ru/"); 
    }

    Login();
    $user = $_SESSION["user"];
    $userId = $user['id'];
    $login = $user['login'] ? $user['login'] : $user['vkid'];

    if (array_key_exists('method', $_POST))
        switch ($_POST['method']) {
            case "setSettings":
                setSettings($user, 
                    array("minRating"=>(float)$_POST['minRating'], 
                        "minVotes"=>(int)$_POST['minVotes'], 
                        "maxDaysDif"=>(int)$_POST['maxDaysDif'], 
                        "quality"=>(int)!empty($_POST['quality']),
                        "translateQuality"=>(int)$_POST['translateQuality'],
                        )
                );
                //Login();
                break;
            default:
                break;
        }        
    

    $result = array();
    $movies = array();
    
    $sqlresult = mysql_query("SELECT * FROM movies");
    while ($row = mysql_fetch_assoc($sqlresult))
        $movies[(int)$row['id']] = $row;
    
    $sqlresult = mysql_query("SELECT movieId FROM userignore WHERE userId = $userId");
    $ignore = array();
    while ($row = mysql_fetch_assoc($sqlresult))
        $ignore[$row['movieId']] = true;

    $newMov = array();    
    $sqlresult = mysql_query("SELECT * FROM links ORDER BY added DESC LIMIT 500");
    while ($row = mysql_fetch_assoc($sqlresult))
        if (!array_key_exists($row['movieId'], $ignore)) {
            if (qualityToRool($row['quality']) < $user['quality'])
                continue;
            if (translateQualityToRool($row['translateQuality']) < $user['translateQuality'])
                continue;
            if (!is_array($movies[$row['movieId']]['description']))
                $movies[$row['movieId']]['description'] = json_decode($movies[$row['movieId']]['description'], true);
            if ((float)$movies[$row['movieId']]['description']['imdbRating'] < $user['minRating'])
                continue;
            if ($user['minVotes']) {
                $votes = intval(str_replace(",","",$movies[$row['movieId']]['description']['imdbVotes']));;
                if ($votes < $user['minVotes']) 
                    continue;
            }

            if (empty($movies[$row['movieId']]['Release']))
                $movies[$row['movieId']]['Release'] = strtotime($movies[$row['movieId']]['description']['Released']);

            if ($user['maxDaysDif']) {
                if ((time()-$movies[$row['movieId']]['Release'])/(30.417*24*60*60) > $user['maxDaysDif']) 
                    continue;
            }
            if (!(array_key_exists("Poster", $movies[$row['movieId']]['description']) && $movies[$row['movieId']]['description']['Poster'] != 'N/A'))
                continue;

            $newMov[$row['movieId']] = true;
            $result[] = $row;
        }
        
    if ($login == 'wise guest' || $login == 'guest') {
        $userId = -1;
        $login = false;        
    }

?>
<!DOCTYPE html>
<html lang="en">
    <?php
        $title = "Cinema : Новые фильмы на торрентах";
        include "html/head.php";
    ?>

  <body>
    <script type="text/javascript">
        var userId = <?php echo $userId; ?>;

        function ignoreMovie(movieId) {
            $.post( "ajax.php", { method: "ignoreMovie", userId: userId, movieId: movieId })
                .done(function( data ) {
                $('.movieTr'+movieId).remove();

                owlPos = -1;
                //items = $("#owl-example").data('owlCarousel')._items;
                items = $("#owl-example").data('owlCarousel').$userItems;
                for (i = 0; i < items.length; ++i) 
                    //if (items[i][0].firstChild.className == "movie moviePos"+movieId) {
                    if (items[i].className == "movie moviePos"+movieId) {
                        owlPos = i;
                        break;
                    }
                if (owlPos != -1) {
                    //beta
                    //$("#owl-example").trigger('removeItem', [owlPos]);
                
                    nextPos = $("#owl-example").data('owlCarousel').currentItem;
                    $("#owl-example").data('owlCarousel').removeItem(owlPos);
                    $("#owl-example").data('owlCarousel').jumpTo(nextPos);
                }

            });
        }

        $(document).ready(function() {
            $('#torrentTable').DataTable({
                paging: false,
                ordering: true,
                order: [[ 6, "desc" ]]
            });
        
            //beta
            /*
            $("#owl-example").owlCarousel({
                loop:true,
                autoWidth: true,
                margin: 10,
                dotsEach: 6,
                autoplay: true,
                responsive:{
                    200:{
                        items:1,
                        dotsEach: 1,
                    },
                    400:{
                        items:2,
                        dotsEach: 2,
                    },
                    600:{
                        items:3,
                        dotsEach: 3,
                    },
                    800:{
                        items:4,
                        dotsEach: 4,
                    },
                    1000:{
                        items:5,
                        dotsEach: 5,
                    },
                    1200:{
                        items:6,
                        dotsEach: 6,
                    }
                }
            });
*/
            
            //stable
            $("#owl-example").owlCarousel({
                items : 6,
                itemsDesktop : [1199,5],
                itemsDesktopSmall : [999,4],
                itemsTablet: [799,3],
                itemsTabletSmall: [599,2],
                itemsMobile : [399, 1],
                autoPlay: true,
            });
            
            $(".movieDelete").click(function( event ) {
              event.preventDefault();
              $('#main').css("height", $('#main').height());
              ignoreMovie($(this).attr('movieId'));
              $('#main').css("height", "");
            });
        });



  
  </script>

    
    <?php
        $liactive = "home";
        include "html/navbar.php";
    ?>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron" style="padding: 10px 0">
      <div id='main' class="container-fluid" style="padding: 0">

        <div id="owl-example" class="owl-carousel">
            <?php
                $cnt = 0;
                foreach(array_keys($newMov) as $key) {
                    $desc = $movies[$key]['description'];
                    if (array_key_exists("Poster", $desc) && $desc['Poster'] != 'N/A') {
                    ?>
            <div class='movie moviePos<?php echo $key; ?>'>
                <img class='poster' src='<?php echo $desc['Poster']; ?>' 
                     onclick="$('html, body').animate({ scrollTop: $('tr.movieTr<?php echo $key; ?>').offset().top-100 }, 1000);
                              $('.highlighted').removeClass('highlighted');
                              $('tr.movieTr<?php echo $key; ?> a').addClass('highlighted');
                                            //                          .delay(4500)
                                            //                          .queue(function() {
                                            //                           $('.movieTr<?php echo $key; ?> a').removeClass('highlighted');
                                            //                           $('.movieTr<?php echo $key; ?> a').dequeue();
                                            //                       });"
                />
                <a title="открыть на IMDB" target='_blank' href='<?php echo "http://www.imdb.com/title/".$movies[$key]['imdbid'];?>/'> 
                    <div class='movieInfo'>
                        <div class='movieRating'><?php echo $desc['imdbRating']; ?></div>
                        <div class='movieRelease'><?php echo date("M'y",$movies[$key]['Release']); ?></div>
                    </div>
                </a>
                <?php if ($login) { ?>
                <a title="не показывать (в корзину)" target='_blank' href='#'> 
                    <div class='movieDelete' movieId='<?php echo $key ?>'>
                        <span class="glyphicon glyphicon-remove-circle"></span>
                    </div>
                </a>
                <?php } ?>
            </div>
                    <?php
                        if (++$cnt >= 24)
                            break;
                    }
                }    
            ?>
        </div>
    
      </div>
    </div>

    <div class="container table-responsive" style="padding: 0">
        <!-- <table id = 'hor-minimalist-b'> -->
        <table id='torrentTable' class='table table-striped table-hover'>
            <thead>
                <!-- <td>Title</td> -->
                <!-- <td>IMDB</td> -->
                <!-- <td>Release</td> -->
                <td>Качество</td>
                <td>Перевод</td>
                <td>Ссылка</td>
                <td>Размер</td>
                <td>Сиды</td>
                <td>Личеры</td>
                <td>Добавлено</td>
            </thead>
            <tbody>
            <?php
            foreach($result as $cur) {
                $desc = $movies[$cur['movieId']]['description'];
                echo "<tr class='movieTr" . $movies[$cur['movieId']]['id'] . "'>\n";
                //echo "\t<td><a target='_blank' href='http://www.imdb.com/title/".$movies[$cur['movieId']]['imdbid']."/'><div class='fullDiv'>".$movies[$cur['movieId']]['title']."</div></a></td>\n";
                //echo "\t<td><a target='_blank' href='http://www.imdb.com/title/".$movies[$cur['movieId']]['imdbid']."/'><div class='fullDiv'>".(float)$desc['imdbRating']."</div></a></td>\n";
                //echo "\t<td data-order='" .strtotime($desc['Released']) . "'>".$desc['Released']."</td>\n";
                echo "\t<td data-order='" . qualityToRool($cur['quality']) . "'>".$cur['quality']."</td>\n";
                echo "\t<td data-order='" . translateQualityToRool($cur['translateQuality']) . "'>".$cur['translateQuality']."</td>\n";
                echo "\t<td><a target='_blank' href='".$cur['link']."'><div class='fullDiv'>".$cur['description']."</div></a></td>\n";
                echo "\t<td>".$cur['size']."</td>\n";
                echo "\t<td>".$cur['seed']."</td>\n";
                echo "\t<td>".$cur['leech']."</td>\n";
                echo "\t<td data-order='" . strtotime($cur['added']) . "'>".date("M j", strtotime($cur['added']))."</td>\n";
                echo "</tr>\n";
            }
            ?>
            </tbody>
        </table>
      <hr>

    <?php
        include "html/footer.php";
    ?>


    </div> <!-- /container -->
    
  </body>
</html>
