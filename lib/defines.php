<?php
    require_once __DIR__."/../bin/vendor/autoload.php";

    ///List of defines - constants
    define("UPDATEMOVIESEVERYDAYS", 3);
    define("DELETELINKSAFTERDAYS", 7);
    define("ADDLINKSPASTDAYS", 180);
    define("FRESHLINKSDAYS", 21);
    define("LOGDIR", __DIR__."/../logs/");

    define("RUTORROOT", "http://rutor.info/");
    define("NNMROOT", "http://nnmclub.to");
    define("PIRATEROOT", "https://thepiratebay.plus");
    define("KINOPOISKROOT", "https://www.kinopoisk.ru");

    $BANNED = array(1288, 518, 1421, 662, 1499, 1598, 373, 1225, 2503, 2718, 2840, 2769, 3148, 2769, 2192, 4163, 4205, 4724, 4881, 4276, 4583, 5576,
        3881, 3144, 4911, 3071, 4928, 3974, 5015, 5573, 5458, 5604, 5585, 5545, 6658, 6736, 6639, 5573, 7907, 9924, 9847, 9489, 10137, 10584, 9781,
        10581, 10658, 11111, 11103, 11677, 11735, 11585, 11850);
    $logger = new Katzgrau\KLogger\Logger(LOGDIR);
?>