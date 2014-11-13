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
      <span class="desc">персональная доставка лучших фильмов</span>
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

<div id="userSettings" <?php if ($_SESSION['showSettings'] === "no") echo "style='display: none;'" ?>>
<div class="container">
    <form class="form-inline" role="form" action='/' method='post'>
        <input type='hidden' name="method" value="setSettings"/>
      <div class="form-group">
        <label class="sr-only" for="minRating">рейтинг IMDB</label>
        <div class="input-group">
            <div class="input-group-addon">рейтинг IMDB ≥</div>
            <input name="minRating" type="number" class="form-control" id="minRating" placeholder="0.0" min=0 max=10 step=0.1 value='<?php echo $user['minRating']; ?>'>
        </div>
      </div>
      <div class="form-group">
        <label class="sr-only" for="minVotes">голосов IMDB</label>
        <div class="input-group">
            <div class="input-group-addon">голосов IMDB ≥</div>
            <input name="minVotes" type="number" class="form-control" id="minVotes" placeholder="0" min=0 max=9000000 step=1 value='<?php echo $user['minVotes']; ?>'>
        </div>
      </div>
      <div class="form-group">
        <label class="sr-only" for="maxDaysDif">Прошло месяцев с премьеры</label>
        <div class="input-group">
            <div class="input-group-addon">Прошло месяцев с премьеры ≤</div>
            <input name="maxDaysDif" type="number" class="form-control" id="maxDaysDif" placeholder="0" min=0 max=9000 step=1 value='<?php echo $user['maxDaysDif']; ?>'>
        </div>
      </div>
      <div class="form-group">
        <div class="checkbox">
            <label>
                <input name="quality" type="checkbox" <?php echo $user['quality']?"checked":""; ?> >
                только HD
            </label>
        </div>
      </div>
      <div class="form-group">
        <label class="sr-only" for="translateQuality">Перевод не хуже, чем</label>
        <div class="input-group">
            <div class="input-group-addon">Перевод не хуже, чем</div>
            <select name="translateQuality" class="form-control">
              <option value="3" <?php echo $user['translateQuality']==3?"selected":""; ?>>дубляж</option>
              <option value="2" <?php echo $user['translateQuality']==2?"selected":""; ?>>многоголосый</option>
              <option value="1" <?php echo $user['translateQuality']==1?"selected":""; ?>>любительский</option>
              <option value="0" <?php echo $user['translateQuality']==0?"selected":""; ?>>оригинал</option>
            </select>
        </div>
      </div>
    
      <button type="submit" class="btn btn-default">
            Обновить
      </button>
      <?php if (!$login) { ?>
        <span class="help-block" style="text-align: center; margin-bottom: 0">Войдите, чтобы сохранить настройки и получить возможность удалять просмотренные/неинтересные фильмы</span>
      <?php } ?>
    </form>
</div>
</div>