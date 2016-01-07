<?php
    // this requires global $keys
    $cnt = 0;
    foreach($keys as $key=>$movieSorted) {
        $movie = $movies[$movieSorted['id']];
        $desc = $movie['description'];
        $movieTitle = htmlspecialchars(array_key_exists("titleRu", $desc)?$desc['titleRu']:$desc['Title']);
        ?>
        <div class='movie moviePos<?php echo $movie['id']; ?>'>
            <a title="<?php echo array_key_exists("titleRu", $desc)?$desc['titleRu']:$desc['Title']; echo " (".$movie['totalSeed']."↑ ".$movie['totalLeech']."↓)"; ?>" target='_blank' href="movie.php?id=<?php echo $movie['id']; ?>">
                <img class='poster' alt='<?=$movieTitle?>' src='<?php echo array_key_exists("PosterRu", $desc)?$desc['PosterRu']:$desc['Poster']; ?>' />
            </a>
            <?php if (array_key_exists("kinopoiskId", $desc)) {?>
                <a title="открыть на Кинопоиске" target='_blank' href='<?php echo "http://www.kinopoisk.ru/film/".$desc['kinopoiskId'];?>/'> 
            <?php } else { ?>
                <a title="открыть на IMDB" target='_blank' href='<?php echo "http://www.imdb.com/title/".$movie['imdbid'];?>/'> 
            <?php } ?>
                <div class='movieInfo'>
                    <div class='movieRating'><?php echo sprintf("%.1f", (array_key_exists("kinopoiskRating", $desc)&&$desc['kinopoiskRating'])?$desc['kinopoiskRating']:$desc['imdbRating'] ); ?></div>
                    <div class='movieRelease'>
                        <?php echo date("M'y",$movie['Release']); ?>
                        <!--<?php echo $movie['totalSeed']."↑ ".$movie['totalLeech']."↓"; ?>-->
                    </div>
                </div>
            </a>
            <div class='movieTitle'>
                <?php echo $movieTitle; ?>
                <div class='movieQuality'>
                    <span class="glyphicon glyphicon-facetime-video"></span> <?php echo $movie['qualityStr']; ?>
                    <?php if ($movie['translateQualityStr']) { ?>
                    <span class="glyphicon glyphicon-volume-up"></span> <?php echo $movie['translateQualityStr'];/*translateQualityToStr($movie['translateQuality']);*/ ?>
                    <?php } ?>
                </div>
            </div>
            <?php if ($login) { ?>
            <a title="не показывать (в корзину)" target='_blank' href='#'> 
                <div class='movieDelete' movieId='<?php echo $movie['id']; ?>'>
                    <span class="glyphicon glyphicon-remove-circle"></span>
                </div>
            </a>
            <?php } ?>
        </div>
        <?php
            if (++$cnt >= 48)
                break;
    }    
?>   