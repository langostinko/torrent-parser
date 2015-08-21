<?php
    if (!isset($head_time_start))
        $head_time_start = microtime(true);
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo @$metaDescription?$metaDescription:"Новые фильмы на торрентах"?>">
    <meta name="author" content="">
    <link rel="icon" href="img/icon2.png">

    <title><?php echo @$metaTitle?$metaTitle:"Новые фильмы на торрентах";?></title>
    

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/handlebars.js"></script>
    <script src="js/typeahead.bundle.js"></script>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/typeahead.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/plug-ins/9dcbecd42ad/integration/bootstrap/3/dataTables.bootstrap.css">

    <!-- DataTables -->
    <script type="text/javascript" charset="utf-8" src="js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf-8" src="//cdn.datatables.net/plug-ins/9dcbecd42ad/integration/bootstrap/3/dataTables.bootstrap.js"></script>
   
    <?php if (isAdmin($user['id'])) {?>
    <!-- VK -->
    <script src="//vk.com/js/api/openapi.js" type="text/javascript"></script>
    <script type="text/javascript">
      VK.init({
        apiId: 4586424
      });
    </script>
    <?php } ?>
   
    <!-- Custom styles for this template -->
    <link href="css/template.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>