<div id="userSettings" <?php if (array_key_exists('showSettings', $_SESSION) && $_SESSION['showSettings'] === "no") echo "style='display: none;'" ?>>
<div class="container">
    <form class="form-inline" action='.' method='post'>
        <input type='hidden' name="method" value="setSettings"/>
      <div class="form-group">
        <label class="sr-only" for="minRating">рейтинг</label>
        <div class="input-group">
            <div class="input-group-addon">рейтинг ≥</div>
            <input name="minRating" type="number" class="form-control" style="width: 60px" id="minRating" placeholder="0.0" min=0 max=10 step=0.1 value='<?php echo $user['minRating']; ?>'>
        </div>
      </div>
      <div class="form-group" style="display: none;">
        <label class="sr-only" for="minVotes">голосов IMDB</label>
        <div class="input-group">
            <div class="input-group-addon">голосов IMDB ≥</div>
            <input name="minVotes" type="number" class="form-control" id="minVotes" placeholder="0" min=0 max=9000000 step=1 value='<?php echo $user['minVotes']; ?>'>
        </div>
      </div>
      <div class="form-group">
        <label class="sr-only" for="maxDaysDif">месяцев с премьеры</label>
        <div class="input-group">
            <div class="input-group-addon">месяцев с премьеры ≤</div>
            <input name="maxDaysDif" type="number" class="form-control" style="width: 60px" id="maxDaysDif" placeholder="0" min=0 max=9000 step=1 value='<?php echo $user['maxDaysDif']; ?>'>
        </div>
      </div>
      <div class="form-group">
        <div class="checkbox">
            <label>
                <input name="quality" type="checkbox" <?php echo $user['quality']?"checked":""; ?> >
                HD
            </label>
        </div>
        <div class="checkbox">
            <label>
                <input name="onlyNewTor" type="checkbox" <?php echo $user['onlyNewTor']?"checked":""; ?> >
                свежие
            </label>
        </div>
      </div>
      <div class="form-group">
        <label class="sr-only" for="translateQuality">перевод не хуже, чем</label>
        <div class="input-group">
            <div class="input-group-addon">перевод не хуже, чем</div>
            <select name="translateQuality" class="form-control">
              <option value="5" <?php echo $user['translateQuality']==5?"selected":""; ?>>лицензия</option>
              <option value="4" <?php echo $user['translateQuality']==4?"selected":""; ?>>чистый звук</option>
              <option value="3" <?php echo $user['translateQuality']==3?"selected":""; ?>>многоголосый</option>
              <option value="2" <?php echo $user['translateQuality']==2?"selected":""; ?>>любительский</option>
              <option value="1" <?php echo $user['translateQuality']==1?"selected":""; ?>>с TS</option>
              <option value="0" <?php echo $user['translateQuality']==0?"selected":""; ?>>оригинал</option>
            </select>
        </div>
      </div>
      <div class="form-group">
        <label class="sr-only" for="sortType">сортировка по</label>
        <div class="input-group">
            <div class="input-group-addon">сортировка по</div>
            <select name="sortType" class="form-control">
              <option value="0" <?php echo $user['sortType']==0?"selected":""; ?>>пирам</option>
              <option value="1" <?php echo $user['sortType']==1?"selected":""; ?>>новизне</option>
              <option value="2" <?php echo $user['sortType']==2?"selected":""; ?>>рейтингу</option>
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
