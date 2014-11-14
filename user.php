<?php
    session_start();
    include_once "lib/lib.php";
    connect();
    
    
    
    Login();
    
    $user = $_SESSION["user"];

    $userId = $user['id'];
    $login = $user['login'] ? $user['login'] : $user['vkid'];
    
    if ($login == 'wise guest' || $login == 'guest') {
        $user = false;
    
        $userId = -1;
        $login = false;        
    }

    $movies = array();

    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies");
    while ($row = mysqli_fetch_assoc($sqlresult))
        $movies[(int)$row['id']] = $row;
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT movieId FROM userignore WHERE userId = $userId ORDER BY id DESC");
    $ignore = array();
    while ($row = mysqli_fetch_assoc($sqlresult))
        $ignore[$row['movieId']] = true;

?>
<!DOCTYPE html>
<html lang="en">
<?php
    $title = "Cinema.$login";
    include "html/head.php";
?>

  <body>
    <script type="text/javascript">
        var userId = <?php echo $userId; ?>;

        function unIgnoreMovie(movieId) {
            $.post( "ajax.php", { method: "unIgnoreMovie", userId: userId, movieId: movieId })
                .done(function( data ) {
                $('tr.movieTr'+movieId).fadeOut();
                //alert( "Data Loaded: " + data );
            });
        }
  
  </script>

    <?php
        $liactive = "history";
        include "html/navbar.php";
    ?>
    
    <div class="container">
        <h3>Фильмы в корзине</h3>
        <table id = 'hor-minimalist-b'>
            <thead>
                <td>Название</td>
                <td>рейтинг IMDB</td>
                <td>Дата премьеры</td>
                <td></td>
            </thead>
            <tbody>
            <?php
            foreach(array_keys($ignore) as $key) {
                $cur = $movies[$key];
                if (!$cur)
                    continue;
                $desc = json_decode($cur['description'], true);
                $poster = $desc && array_key_exists('Poster', $desc) ? $desc['Poster'] : "";
                echo "<tr class='movieTr" . $cur['id'] . "'>\n";
                echo "\t<td><a target='_blank' href='http://www.imdb.com/title/".$cur['imdbid']."/'><div class='fullDiv'>".$cur['title']."</div></a></td>\n";
                echo "\t<td><a target='_blank' href='http://www.imdb.com/title/".$cur['imdbid']."/'><div class='fullDiv'>".(float)$desc['imdbRating']."</div></a></td>\n";
                echo "\t<td>".$desc['Released']."</td>\n";
                echo "\t<td class='button' onclick='unIgnoreMovie(" . $cur['id'] . ")'><img class='delImg' src='img/cross.png'/></td>\n";
                echo "</tr>\n";
    
            }
            ?>
            </tbody>
        </table>
        
    <?php
        include "html/footer.php";
    ?>
    </div>

  </body>
</html>