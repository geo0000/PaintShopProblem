<?php

$shop = new PaintShop();

$shop->calculateColors();
$shop->printColors();


class PaintShop {
  private $preferences = [];

  private $fileContent = '';

  private $solutionExists = TRUE;

  private $colorsCount;

  private $colorResult;

  public function __construct() {
    $this->loadPreferences();
    $this->processData();
  }

  /**
   * Load preferences file.
   */
  private function loadPreferences() {
    // Get input file name from '-f' param.
    $params = getopt("f:");
    $fileName = $params['f'];

    try {
      if (!file_exists($fileName)) {
        throw new Exception('File ' . $fileName . ' doest exist');
      }
    } catch (Exception $e) {
      echo 'Error: ' . $e->getMessage() . "\n";
      exit;
    }

    $this->fileContent = file_get_contents($fileName);
  }

  /**
   * Process user preferences.
   */
  private function processData() {
    $rows = explode("\n", $this->fileContent);

    try {
      if (intval($rows[0]) < 1) {
        throw new Exception('Colors count should be positive integer');
      }

      // Set colors count.
      $this->colorsCount = $rows[0];

      // Get and check preferences.
      for ($i = 1; $i < count($rows); $i++) {
        $line = explode(' ', $rows[$i]);
        $mCount = 0;

        for ($j = 0; $j < count($line); $j = $j + 2) {
          // Check color number.
          $colorNumber = $line[$j];
          $colorStyle = $line[$j + 1];

          if (intval($colorNumber) < 1 || $colorNumber > $this->colorsCount) {
            throw new Exception('Please check color numbers for user ' . $i);
          }

          if ($colorStyle === 'M' || $colorStyle === 'G') {
            if ($colorStyle == 'M') {
              $mCount++;
            }

            if ($mCount > 1) {
              throw new Exception('More than 1 matte color for user ' . $i);
            }

            $this->preferences[$i]['pref'][] = $colorNumber . $colorStyle;
          }
          else {
            throw new Exception('Please check color styles for user ' . $i);
          }
        }

        $this->preferences[$i]['variants'] = count($this->preferences[$i]['pref']);

      }
    } catch (Exception $e) {
      echo 'Error: ' . $e->getMessage() . "\n";
      exit;
    }

    // Sort data.
    $this->preferencesSort();
  }

  /**
   * Change user order to have minimum preferences at the top.
   */
  private function preferencesSort() {
    // Sort users by preferences count.
    usort($this->preferences, function ($x, $y) {
      if ($x['variants'] == $y['variants']) {
        return 0;
      }
      else {
        if ($x['variants'] > $y['variants']) {
          return 1;
        }
        else {
          return -1;
        }
      }
    });

    // Sort preferences for each user by color.
    foreach ($this->preferences as $key => $userPreference) {
      usort($userPreference['pref'], function ($x, $y) {
        $colorX = (integer) $x;
        $colorY = (integer) $y;

        if ($colorX == $colorY) {
          return 0;
        }
        else {
          if ($colorX > $colorY) {
            return 1;
          }
          else {
            return -1;
          }
        }
      });

      $this->preferences[$key]['pref'] = $userPreference['pref'];
    }
  }

  /**
   * Set order how colors should be processed.
   * @return array ordered colors list.
   */
  private function initColors() {
    $colorsOrder = [];
    // Set colors order from users with 1 variant.
    // Get all users with 1 variant and put color number at the beginning.
    foreach ($this->preferences as $userPreference) {
      if ($userPreference['variants'] == 1) {
        // store color.
        $colorNumber = (integer) $userPreference['pref'][0];

        if (!in_array($colorNumber, $colorsOrder)) {
          $colorsOrder[] = $colorNumber;
        }
      }
    }

    // Fill all colors order.
    $i = 1;

    while ($i < $this->colorsCount + 1) {
      if (!in_array($i, $colorsOrder)) {
        $colorsOrder[] = $i;
      }
      $i++;
    }

    return $colorsOrder;
  }

  /**
   * Colors processing to get results.
   */
  public function calculateColors() {
    $colorsOrder = $this->initColors();

    // Check all colors and set result for each of them.
    foreach ($colorsOrder as $i) {
      $colorStyle = $this->getVariants($i);

      if (!$colorStyle) {
        $this->solutionExists = FALSE;
        return;
      }

      // Set color.
      $this->colorResult[$i] = $colorStyle;

      // Remove satisfied users and remove color even it doesn't match.
      $this->removeColorsFromUsers($i, $colorStyle);
    }
  }

  /**
   * Removes color number from user preferences or removes satisfied user.
   *
   * @param $colorNumber integer
   * @param $colorStyle string
   */
  private function removeColorsFromUsers($colorNumber, $colorStyle) {

    $color = $colorNumber . $colorStyle;
    $oppositeColor = $colorNumber . ($colorStyle == 'G' ? 'M' : 'G');

    foreach ($this->preferences as $key => $userPreference) {
      // Satisfied user.
      if (in_array($color, $userPreference['pref'])) {
        unset($this->preferences[$key]);
      }

      // Remove color variant from user.
      if (in_array($oppositeColor, $userPreference['pref'])) {
        $keyToRemove = array_search($oppositeColor, $userPreference['pref']);

        unset($this->preferences[$key]['pref'][$keyToRemove]);
        $this->preferences[$key]['variants']--;
      }
    }
  }

  /**
   *  Returns color style for color number.
   *
   * @param $colorNumber integer
   * @return bool|string false if not variant and M or G.
   */
  private function getVariants($colorNumber) {
    $colorStyle = 'G';
    $lastG = $lastM = FALSE;

    foreach ($this->preferences as $key => $userPreference) {

      // first user preference.
      $pref = reset($userPreference['pref']);

      if ((integer) $pref == $colorNumber) {
        $userColor = substr($pref, 1, 1);

        if ($userPreference['variants'] == 1 && $userColor == 'G') {
          $lastG = TRUE;
        }

        if ($userColor !== 'G' && $userPreference['variants'] == 1) {
          $colorStyle = 'M';
          $lastM = TRUE;
        }
      }

      if ($lastM && $lastG) {
        return FALSE;
      }
    }

    return $colorStyle;
  }

  /**
   * Print user preferences.
   */
  public function printPreferences() {
    foreach ($this->preferences as $userPreference) {
      print $userPreference['variants'] . ' ';

      print implode($userPreference['pref'], ' ');
      echo "\n";
    }
  }

  /**
   * Print results.
   */
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
