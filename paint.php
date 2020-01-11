<?php

//print phpinfo();
//die();


$shop = new PaintShop();
$shop->loadPreferences('data.in');
$shop->processData();
$shop->calculateColors();
//$shop->printPreferences();
$shop->printColors();


class PaintShop {
  private $preferences = [];

  private $fileContent = '';

  private $solutionExists = TRUE;

  private $colorsCount;

  private $colorResult;


  private function removeColorsFromUsers($colorNumber,  $colorStyle) {

    $color = $colorNumber . $colorStyle;
    $oppositeColor = $colorNumber . ($colorStyle == 'G' ? 'M' : 'G');

    foreach ($this->preferences as $key => $userPreference) {
      // successed users.
      if (in_array($color, $userPreference['pref'])) {
        unset($this->preferences[$key]);
      }

      // remove color variant from user
      if (in_array($oppositeColor, $userPreference['pref'])) {
        $keyToRemove = array_search($oppositeColor, $userPreference['pref']);
        
        unset($this->preferences[$key]['pref'][$keyToRemove]);
        $this->preferences[$key]['variants']--;
      }

    }
  }
  /**
   * @return mixed
   */

  private function getVariants($colorNumber) {
    $v = ['color' => 'G', 'users'];
    $lastG = FALSE;
    foreach ($this->preferences as $key => $userPreference) {

      // first user preference.
      $pref = reset($userPreference['pref']);

      if ((integer) $pref == $colorNumber) {
        $v['users'][] = $key;

        if ($userPreference['variants'] == 1) {
          $lastG = TRUE;
        }

        if (substr($pref, 1, 1) !== 'G' && $userPreference['variants'] == 1) {
          $v['color'] = 'M';

          if ($lastG) {
            return FALSE;
          }
        }


        // TODO: check for last variant G and M.
      }



    }

    return $v;
  }

  public function calculateColors() {

    $i=1;

    while ($i < $this->colorsCount + 1) {
      // get variants. No variants, G or M, or conflict.
      $v = $this->getVariants($i);

      if (!$v) {
        $this->solutionExists = FALSE;
        break;
      }

      // set color.
      $this->colorResult[$i] = $v['color'];

      $this->removeColorsFromUsers($i,  $v['color']);
      $i++;

    }




    // Fill empty colors.
    $i = 1;
    while ($i < $this->colorsCount) {
      if (!isset($this->colorResult[$i])) {
        $this->colorResult[$i] = "G";
      }
      $i++;
    }

  }

  public function loadPreferences($fileName) {
    // TODO add file checkings.
    $this->fileContent = file_get_contents($fileName);

  }

  public function processData() {
    $rows = explode("\n", $this->fileContent);

    $this->colorsCount = $rows[0];

    for ($i = 1; $i < count($rows); $i++) {
      $line = explode(' ', $rows[$i]);

      for ($j = 0; $j < count($line); $j = $j + 2) {
        $this->preferences[$i]['pref'][] = $line[$j] . $line[$j+1];
      }

      $this->preferences[$i]['variants'] = count($this->preferences[$i]['pref']);

    }

    // Sort data.
    $this->preferencesSort();

  }

  private function preferencesSort() {
    // Sort users by preferences count.
    usort($this->preferences, function ($x, $y) {
      if ($x['variants'] == $y['variants'])
        return 0;
      else if ($x['variants'] > $y['variants'])
        return 1;
      else
        return -1;
    });

    // Sort preferences for each user by color.
    foreach ($this->preferences as $key => $userPreference) {
      usort($userPreference['pref'], function ($x, $y) {
        $colorX = (integer) $x;
        $colorY = (integer) $y;

        if ($colorX == $colorY)
          return 0;
        else if ($colorX > $colorY)
          return 1;
        else
          return -1;
      });

      $this->preferences[$key]['pref'] = $userPreference['pref'];
    }


  }

  public function printPreferences() {
    foreach ($this->preferences as $userPreference) {
      print $userPreference['variants'] . ' ';

      print implode($userPreference['pref'], ' ');
      echo "\n";
    }
  }

  public function printColors() {
    if (!$this->solutionExists) {
      print 'No solution exists';
      echo "\n";
      return;
    }

    ksort($this->colorResult);
    print implode($this->colorResult, ' ');
    echo "\n";
  }
}

?>
