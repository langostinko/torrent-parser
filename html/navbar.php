<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/">Cinema</a>
      <span class="navbarDesc">персональная доставка лучших фильмов</span>
    </div>
    <div class="navbar-collapse collapse">
    <?php if ($login) { ?>
            <div class="navbar-right">
                  <ul class="nav navbar-nav">
                    <?php if ($login) { ?>
                        <li>
                            <img height='50px' src='<?php echo $user['photo']; ?>'/>
                        </li>
                    <?php } ?>
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
            </div>
    <?php } else { ?>
             <form class="navbar-form navbar-right" role="form" action='https://oauth.vk.com/authorize' method='get'>  
                 <input type='hidden' name='client_id' value='4586424'/>
                 <input type='hidden' name='scope' value=''/>
                 <input type='hidden' name='redirect_uri' value='http://cinema.todeliver.ru/'/>
                 <input type='hidden' name='response_type' value='code'/>
                 <input type='hidden' name='v' value='5.25'/>
                <!--    <div class="form-group">
                      <input name="username" type="text" placeholder="Username" class="form-control">
                    </div>
    onclick="location.href = 'https://oauth.vk.com/authorize?client_id=4586424&scope=friends&redirect_uri=http://cinema.todeliver.ru/&response_type=code&v=5.25';" 
                    <div class="form-group">
                      <input name="password" type="password" placeholder="Password" class="form-control">
                    </div> -->
                <button 
                    type="submit" class="btn btn-success" style="border: 0; background-color: #597DA3">
                    Создать личную выборку / Войти через VK
                </button>
              </form>
    <?php } ?>
    </div><!--/.navbar-collapse -->
  </div>
</div>