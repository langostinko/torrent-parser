<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" style="font-family: Georgia, 'Times New Roman', Times, serif;" href="/">FRESH SWAG</a>
      <span class="navbarDesc"></span>
    </div>
    <div class="navbar-collapse collapse">
        <p class="navbar-text" style="margin: 10px 15px">
            <a title="мы на Пикабу" href="http://pikabu.ru/profile/freshs" target='_blank' ><img style="height:30px" src="img/pk_64.png"/></a>
            <a title="мы в ВК" href="https://vk.com/freshswagru" target='_blank' ><img style="height:30px" src="img/vk_64.png"/></a>
        </p>
        <!--<form class="navbar-form navbar-left" role="search">
          <div class="form-group">
            <input type="text" class="form-control" placeholder="Search">
          </div>
        </form>-->
    <?php if ($login) { ?>
      <ul class="nav navbar-nav navbar-right">
        <li>
            <img height='50px' src='<?php echo $user['photo']; ?>'/>
        </li>
        <li <?php if ($liactive=='home') echo "class='active'"; ?>>
            <a href='/'>главная</a>
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
        <li <?php if ($liactive=='history') echo "class='active'"; ?>>
            <a href='user.php'>корзина</a>
        </li>
        <li <?php if ($liactive=='exit') echo "class='active'"; ?>>
            <a href='/?logout=1'>выйти</a>
        </li>
      </ul>
    <?php } else { ?>
     <form class="navbar-form navbar-right" role="form" action='https://oauth.vk.com/authorize' method='get'>  
         <input type='hidden' name='client_id' value='4586424'/>
         <input type='hidden' name='scope' value=''/>
         <input type='hidden' name='redirect_uri' value='<?php echo \pass\VK::$redirect_uri; ?>'/>
         <input type='hidden' name='response_type' value='code'/>
         <input type='hidden' name='v' value='5.25'/>
        <button 
            type="submit" class="btn btn-success" style="border: 0; background-color: #597DA3">
            Создать личную выборку / Войти через VK
        </button>
      </form>
    <?php } ?>
    </div><!--/.navbar-collapse -->
  </div>
</div>