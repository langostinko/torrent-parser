<?php
    function myExec($command) {
        $res = "";
        $res .= "COMMAND :: $command ::\n";
        $res .= ":: START OUTPUT ::\n";
        $result = array();
        exec($command, $result);
        foreach($result as $s)
            $res .= "$s\n";
        $res .= ":: END OUTPUT ::\n\n";
        return $res;
    }

    function gitPull() {
        echo myExec("git pull");
        echo myExec("git status");
    }
    
    echo "cron job\n";
    gitPull();
    
?>
