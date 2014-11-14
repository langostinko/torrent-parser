<div id="userSettings" <?php if (array_key_exists('showSettings', $_SESSION) && $_SESSION['showSettings'] === "no") echo "style='display: none;'" ?>>
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