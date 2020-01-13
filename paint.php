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

        $this->preferences[$i]['pref'] = array_fill(1, $this->colorsCount, '');
        $this->preferences[$i]['variants'] = 0;

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

            $this->preferences[$i]['pref'][$colorNumber] = $colorStyle;
            $this->preferences[$i]['variants']++;
          }
          else {
            throw new Exception('Please check color styles for user ' . $i);
          }
        }
      }
    } catch (Exception $e) {
      echo 'Error: ' . $e->getMessage() . "\n";
      exit;
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

        $positionM = array_search('M', $userPreference['pref']);
        $positionG = array_search('G', $userPreference['pref']);

        $colorNumber = ($positionM ? $positionM : $positionG);

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
    foreach ($this->preferences as $key => $userPreference) {
      if ($this->preferences[$key]['pref'][$colorNumber] !== '') {
        // Satisfied user.
        if ($userPreference['pref'][$colorNumber] == $colorStyle) {
          unset($this->preferences[$key]);
          continue;
        }

        unset($this->preferences[$key]['pref'][$colorNumber]);
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
      if ($userPreference['pref'][$colorNumber] == '' || $userPreference['variants'] > 1) {
        continue;
      }

      if ($userPreference['pref'][$colorNumber] == 'M') {
        $colorStyle = 'M';
      }

      ${'last' . $userPreference['pref'][$colorNumber]} = TRUE;

      if ($lastM && $lastG) {
        return FALSE;
      }
    }

    return $colorStyle;
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
