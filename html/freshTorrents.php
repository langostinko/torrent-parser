<h2 class="hidden-xs">Свежие торренты</h2>
<table id='torrentTable' class='table table-striped table-hover hidden-xs'>
    <thead>
      <tr>
        <td>качество</td>
        <td>перевод</td>
        <td>фильм</td>
        <td>размер</td>
        <td>сиды</td>
        <td>личеры</td>
        <td>добавлено</td>
      </tr>
    </thead>
    <tbody>
    <?php
        $torrents = array();
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE type=0 ORDER BY added DESC LIMIT 10");
        while ($row = mysqli_fetch_assoc($sqlresult))
            $torrents[] = $row;

        foreach($torrents as $cur) {
            echo "<tr>\n";
            echo "\t<td data-order='" . qualityToRool($cur['quality']) . "'>".$cur['quality']."</td>\n";
            echo "\t<td data-order='" . translateQualityToRool($cur['translateQuality']) . "'>".$cur['translateQuality']."</td>\n";
            echo "\t<td><a target='_blank' href='/movie.php?id=".$cur['movieId']."'>".$cur['description']."</a></td>\n";
            echo "\t<td>".$cur['size']."</td>\n";
            echo "\t<td>".$cur['seed']."</td>\n";
            echo "\t<td>".$cur['leech']."</td>\n";
            echo "\t<td data-order='" . strtotime($cur['added']) . "'>".date("M j H:i:s", strtotime($cur['added']))."</td>\n";
            echo "</tr>\n";
        }
    ?>
    </tbody>
</table>      
<?php if (isAdmin($user['id'])) { ?>
<h2 class="hidden-xs">Легальные поступления</h2>
<table id='torrentTable' class='table table-striped table-hover hidden-xs'>
    <thead>
      <tr>
        <td>фильм</td>
        <td>цена</td>
        <td>добавлено</td>
      </tr>
    </thead>
    <tbody>
    <?php
        $legals = array();
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE type=1 ORDER BY added DESC LIMIT 10");
        while ($row = mysqli_fetch_assoc($sqlresult))
            $legals[] = $row;

        foreach($legals as $cur) {
            echo "<tr>\n";
            echo "\t<td><a target='_blank' href='/movie.php?id=".$cur['movieId']."'>".$cur['description']."</a></td>\n";
            echo "\t<td>".$cur['size']."</td>\n";
            echo "\t<td data-order='" . strtotime($cur['added']) . "'>".date("M j H:i:s", strtotime($cur['added']))."</td>\n";
            echo "</tr>\n";
        }
    ?>
    </tbody>
</table>      
<?php } ?>