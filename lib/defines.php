<?php
    require_once __DIR__."/../bin/vendor/autoload.php";

    ///List of defines - constants
    define("UPDATEMOVIESEVERYDAYS", 3);
    define("DELETELINKSAFTERDAYS", 7);
    define("ADDLINKSPASTDAYS", 180);
    define("FRESHLINKSDAYS", 14);
    define("LOGDIR", __DIR__."/../logs/");

    define("RUTORROOT", "http://ru-free-tor.org");
    define("NNMROOT", "http://nnmclub.to");
    define("PIRATEROOT", "https://thepiratebay.plus");
    define("KINOPOISKROOT", "https://www.kinopoisk.ru");

    $BANNED = array(1288, 518, 1421, 662, 1499, 1598, 373, 1225, 2503, 2718, 2840, 2769, 3148, 2769, 2192, 4163, 4205, 4724, 4881, 4276, 4583);
    $logger = new Katzgrau\KLogger\Logger(LOGDIR);
?>