<?php
    include_once "lib/lib.php";
    connect();
        
    Login();

    $user = $_SESSION["user"];
    $userId = $user['id'];
    $login = $user['login'] ? $user['login'] : $user['vkid'];

    if (!isAdmin($user['id'])) {
        header('HTTP/1.0 401 Unauthorized');
        echo "Unauthorized\n";
        exit(0);
    }
?>
<!DOCTYPE html>
<html lang="en">
<?php
    $title = "Admin page";
    $metaDescription = "";
    include "html/head.php";
?>
  <body>
    <?php
        $liactive = "";
        include "html/navbar.php";
    ?>
    <div class="jumbotron">
    <div class="container">
    
    <pre id="response"></pre>

    <div class="form-group">
        <input id="getIdsTitle" class="form-control" type="text" value="getIdsTitle"/>
        <input id="getIdsYear" class="form-control" type="number" value="getIdsYear"/>
        <input id="setIdsId" class="form-control" type="number" value="0"/>
        <input id="getIdsBtn" class="btn btn-default" type="submit"/>
        <script type="text/javascript">
            $("#getIdsBtn").click(function() {
                $.post( "ajax.php", { method: "getIds", title: $("#getIdsTitle").val(), year: $("#getIdsYear").val(), movieId: $("#setIdsId").val() })
                .done(function( data ) {
                    $('#response').html(data);
                });
            });
        </script>
    </div>
    
    <pre id="logs"></pre>
    <script type="text/javascript">
        window.setInterval(function(){
            $.post( "ajax.php", { method: "getLogs" })
            .done(function( data ) {
                $('#logs').html(data);
            });
        }, 5000);
    </script>

    <?php
        include "html/footer.php";
    ?>
    </div>
    </div>

  </body>
</html>