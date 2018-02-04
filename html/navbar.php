<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" style="font-family: Georgia, 'Times New Roman', Times, serif;" href=".">FRESH SWAG</a>
      <!--span class="navbarDesc"></span-->
    </div>
    <div class="navbar-collapse collapse">
        <p class="navbar-text" style="margin: 10px 15px">
            <a title="мы на Пикабу" href="http://pikabu.ru/profile/freshs" target='_blank'><img style="height:30px" alt="мы на Пикабу" src="img/pk_64.png"/></a>
            <a title="Telegram канал" href="https://t.me/freshswag" target='_blank'><img style="height:30px" alt="мы в ВК" src="img/tg_64.png"/></a>
            <!--a title="импорт из КиноПоиска" href="kp.php"><img style="height:30px" alt="импорт из КиноПоиска" src="img/kp_64.png"/></a-->
            <a title="поддержать" href="http://yasobe.ru/na/freshswag" target='_blank'><img style="height:30px" alt="поддержать" src="img/dn_64.png"/></a>
        </p>
        <form class="navbar-form navbar-left hidden-xs hidden-sm">
          <div class="form-group">
          <?php
            $vars = getRandomList();
            $placeholder = $vars[array_rand($vars)];
          ?>
            <input id="search" style="width: 250px" type="text" class="typeahead form-control" placeholder="поиск: <?=$placeholder?>">
          </div>
        </form>
    <?php if ($login) { ?>
      <ul class="nav navbar-nav navbar-right">
        <li class="hidden-xs">
            <img height='50px' src='<?php echo $user['photo']; ?>'/>
        </li>
        <li <?php if ($liactive=='home') echo "class='active'"; ?>>
            <a href='.'>главная</a>
        </li>
        <li style='display:none;' <?php if ($liactive=='settings') echo "class='active'"; ?>>
            <a href="#" onclick='
              if ( $( "#userSettings" ).is( ":hidden" ) ) {
                $( "#userSettings" ).slideDown( "fast" );
              } else {
                $( "#userSettings" ).slideUp( "fast" );
              } return;
              '>settings</a>
        </li>
        <!--li <?php if ($liactive=='kp') echo "class='active'"; ?>>
            <a href='kp.php'>кинопоиск</a>
        </li-->
        <li <?php if ($liactive=='history') echo "class='active'"; ?>>
            <a href='user.php'>корзина</a>
        </li>
        <li <?php if ($liactive=='exit') echo "class='active'"; ?>>
            <a href='/?logout=1'>выйти</a>
        </li>
      </ul>
    <?php } else { ?>
     <form class="navbar-form navbar-right" action='https://oauth.vk.com/authorize' method='get'>  
         <input type='hidden' name='client_id' value='4586424'/>
         <input type='hidden' name='scope' value='<?php echo array_key_exists("admin", $_GET)?"4":""; ?>'/>
         <input type='hidden' name='redirect_uri' value='<?php echo \pass\VK::$redirect_uri; ?>'/>
         <input type='hidden' name='response_type' value='code'/>
         <input type='hidden' name='v' value='5.25'/>
        <button 
            type="submit" class="btn btn-success" style="border: 0; background-color: #597DA3">
            Войти через VK
        </button>
      </form>
    <?php } ?>
    </div><!--/.navbar-collapse -->
  </div>
</div>


<script type="text/javascript">
var bestPictures = new Bloodhound({
  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
  queryTokenizer: Bloodhound.tokenizers.whitespace,
  limit: 20,
  remote: 'ajax.php?search=%QUERY',
});
 
bestPictures.initialize();
 
var myTypeahead = $('#search').typeahead({
    autoselect: true,
  }, {
    name: 'best-pictures',
    displayKey: 'value',
    source: bestPictures.ttAdapter(),
    templates: {
      empty: [
        '<div class="tt-dropdown-menu tt-suggestion">',
        'ничего не нашлось',
        '</div>'
      ].join('\n'),
      suggestion: Handlebars.compile("<a href='movie.php?id={{id}}''><strong>{{value}}</strong> ({{year}})")
  }
});

myTypeahead.on('typeahead:selected',function(evt,data){
    window.location.href = "movie.php?id="+data.id;
});

myTypeahead.on('typeahead:autocompleted',function(evt,data){
    window.location.href = "movie.php?id="+data.id;
});
</script>