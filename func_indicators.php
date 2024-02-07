<?php

/**
* @author Ebo Eppenga
* @copyright 2022
*
* GoldStar Buy and Sell bot based on signals from for example TradeView
* or any other platform using PHP Bybit API from zhouaini528.
* 
* func_indicators.php
* Calculate Technical indicators and some helper functions to
* clean up the data generated.
* 
**/


/*===========================================================================*/
/* Helper functions                                                         */
/*===========================================================================*/

// Function to remap an array (OK)
function remapArray($data, $remap) {
  
  // Remap the array
  $new = [];
  foreach ($data as $key => $value) {
    $new[$key + ($remap - 1)] = $value;
  }
  
  return $new;
}

/*===========================================================================*/

// Function to remove empty values (OK)
function removeEmptyValues($array) {
  
  foreach ($array as $key => $value) {
    if (empty($value)) {
      unset($array[$key]);
    } else {
      break;
    }
  }
  
  return $array;
}

/*===========================================================================*/

// Function to remove arrays that have an empty element
function removeEmptyArrays($array) {
  
  foreach ($array as $key => $subArray) {
    // Filter the sub-array to remove empty elements
    $filteredArray = array_filter($subArray);
    
    // Check if the filtered array has fewer elements than the original sub-array
    if (count($filteredArray) < count($subArray)) {
      // Unset the sub-array if it contained any empty element
      unset($array[$key]);
    }
  }
  
  return $array;
}

/*===========================================================================*/

// Function to find the index of the first non-null value
function firstvalueArray($data) {
  foreach ($data as $key => $value) {
    if ($value !== null) {
      // Return the key (index) of the first non-null value
      return $key;
    }
  }
  // Return null if no non-null value is found
  return null;
}

/*===========================================================================*/

// Function to show the values of a list
function showValues($data, $reverse = false) {
  
  if ($reverse) {$data = array_reverse($data);}
  
  foreach ($data as $value) {
    echo $value . "\n";
  }
}


/*===========================================================================*/
/* Indicators                                                                */
/*===========================================================================*/

// Function to calculate Simple Moving Average (OK)
function calcSMA($data, $period) {
  
  // Initialize variables
  $smaValues  = [];
  $dataCount  = count($data);
  $startIndex = array_key_first($data);
  $endLoop    = $dataCount + $startIndex;
  
  // Do calculation
  for ($i = $startIndex; $i < $endLoop; $i++) {
    if ($i >= ($period + $startIndex) - 1) {
      $sum = 0;
      for ($j = $i; $j > $i - $period; $j--) {
        $sum += $data[$j];
      }
      $sma = $sum / $period;
      $smaValues[$i] = $sma;
    }
  }
  
  return $smaValues;
}

/*===========================================================================*/

// Function to calculate Exponential Moving Average (OK)
function calcEMA($data, $period) {
  
  $smooth     = 2;
  $dataCount  = count($data);
  $startIndex = array_key_first($data);
  $endLoop    = $dataCount + $startIndex;
  
  // Calculate the initial SMA for the first EMA value
  $sma = 0;
  for ($i = $startIndex; $i < ($period + $startIndex); $i++) {
    $sma += $data[$i];
  }
  $sma /= $period;
  $ema[($period + $startIndex) - 1] = $sma;
  
  // Calculate the multiplier for weighting the EMA
  $multiplier = $smooth / ($period + 1);
  
  // Calculate EMA for each data point after the initial SMA period
  for ($i = ($period + $startIndex); $i < $endLoop; $i++) {
    $emaToday = ($data[$i] - $ema[$i - 1]) * $multiplier + $ema[$i - 1]; 
    $ema[] = $emaToday;
  }
  return $ema;
}

/*===========================================================================*/

function calcWMA($data, $period) {
  
  // Initialize variables
  $wma        = [];
  $dataCount  = count($data);
  $startIndex = array_key_first($data);
  $endLoop    = $dataCount + $startIndex - $period;
  
  // Fill in non applicables with null
  for ($i = 0; $i <= ($period - 2); $i++) {
    $wma[$i] = null;
  }
  
  // Do calculation
  for ($i = $startIndex; $i <= $endLoop; $i++) {
    $weightedSum = 0;
    $weightSum = 0;
    
    for ($j = 0; $j < $period; $j++) {
      // The weight increases as we get closer to the current date
      $weight = $period - $j;
      $weightedSum += $data[$i + $j] * $weight;
      $weightSum += $weight;
    }
    
    $wma[$i + ($period - 1)] = $weightedSum / $weightSum;
  }
  
  return $wma;
}

/*===========================================================================*/

// Function to calculate the Volume Weighted Moving Average (VWMA) (startiondex enzo)
function calcVWMA($data, $volume, $period) {
  
  // Initialize variables
  $vwma       = [];
  $dataCount  = count($data);
  $startIndex = array_key_first($data);
  $endLoop    = $dataCount + $startIndex;
  
  // Do calculation
  for ($i = $startIndex; $i < $endLoop; $i++) {
    if ($i >= $period - 1) {
      $totalVolume = 0;
      $volumeWeightedPrice = 0;
      
      for ($j = 0; $j < $period; $j++) {
        $totalVolume += $volume[$i - $j];
        $volumeWeightedPrice += $data[$i - $j] * $volume[$i - $j];
      }
      
      $vwma[$i] = $volumeWeightedPrice / $totalVolume;
    } else {
      // Not enough data to calculate VWMA
      $vwma[$i] = null;
    }
  }
  
  return $vwma;
}

/*===========================================================================*/


// Function to calculate the Welles Wilder's Moving Average (WWMA)
function calcWWMA($data, $period) {
  
  // Initialize variables
  $wwma       = [];
  $startIndex = array_key_first($data);
  $dataCount  = count($data);
  $endLoop    = $dataCount + $startIndex;
  
  // The first value in the smoothed data is just the average of the first 'period' data points
  $sum = 0;
  for ($i = $startIndex; $i < ($period + $startIndex); $i++) {
    $sum += $data[$i];
  }
  $wwma[($period + $startIndex) - 1] = $sum / $period;
  
  // Apply Welles Wilder's smoothing method for the rest of the data points
  for ($i = ($period + $startIndex); $i < $endLoop; $i++) {
    $wwma[$i] = (($wwma[$i - 1] * ($period - 1)) + $data[$i]) / $period;
  }
  
  return $wwma;
}

/*===========================================================================*/

// Function to calculate the Average True Range
function calcATR($highs, $lows, $closes, $period) {

  // Initialize variables
  $trueRanges = [];
  $atrValues  = [];
  $startIndex = array_key_first($highs);
  $dataCount  = count($highs);
  $endLoop    = $dataCount + $startIndex;

  // Calculate True Range for each day
  for ($i = $startIndex; $i < $endLoop; $i++) {
      if ($i == $startIndex) {
          // For the first period, true range is simply high - low
          $trueRanges[] = $highs[$i] - $lows[$i];
      } else {
          // For subsequent periods, calculate true range as the maximum of the following:
          $tr1 = $highs[$i] - $lows[$i];
          $tr2 = abs($highs[$i] - $closes[$i - 1]);
          $tr3 = abs($lows[$i] - $closes[$i - 1]);
          $trueRanges[] = max($tr1, $tr2, $tr3);
      }
  }

  // Initial ATR is the average of the first 'period' true ranges
  $initialATR = array_sum(array_slice($trueRanges, 0, $period)) / $period;
  $atrValues[] = $initialATR;

  // Calculate subsequent ATR values
  for ($i = ($period + $startIndex); $i < $endLoop; $i++) {
      $currentATR = (($atrValues[$i - $period] * ($period - 1)) + $trueRanges[$i]) / $period;
      $atrValues[] = $currentATR;
  }

  // Remap array
  $atrValues = remapArray($atrValues, $period);
  
  return $atrValues;
}

/*===========================================================================*/

function calcNATR($highs, $lows, $closes, $period) {

  // Calculate ATR using the provided calcATR function
  $atrValues = calcATR($highs, $lows, $closes, $period);

  // Initialize variables
  $natrValues = [];
  $startIndex = array_key_first($atrValues);
  $dataCount  = count($atrValues);
  $endLoop    = $dataCount + $startIndex;

  // Calculate NATR for each period where ATR is available
  for ($i = $startIndex; $i < $endLoop; $i++) {
    $natrValues[] = ($atrValues[$i] / $closes[$i]) * 100;
  }

  // Remap array
  $natrValues = remapArray($natrValues, $period);

  return $natrValues;
}

/*===========================================================================*/

// Function to calculate Standard Deviation (OK)
function calcStdDev($data) {
  
  $mean = array_sum($data) / count($data);
  $sumOfSquares = 0;
  
  foreach ($data as $value) {
    $sumOfSquares += pow($value - $mean, 2);
  }
  
  // Using count($data) - 1 for Bessel's correction
  $variance = $sumOfSquares / (count($data) - 1);
  $standardDeviation = sqrt($variance);
  
  return $standardDeviation;
}

/*===========================================================================*/

// Function to calculate Mean Deviation (OK)
function calcMeanDev($data) {
  $sum = 0;
  $n = count($data);
  
  // Calculate the mean average
  foreach ($data as $value) {
    $sum += $value;
  }
  $mean = $sum / $n;
  
  // Calculate the absolute deviations from the mean
  $deviations = array_map(function ($value) use ($mean) {
    return abs($value - $mean);
  }, $data);
  
  // Calculate the mean deviation
  $meanDeviation = array_sum($deviations) / $n;
  
  return $meanDeviation;
}

/*===========================================================================*/

// Function to calculate Relative Strength Index (OK)
function calcRSI($data, $period) {
  
  // Set variables
  $rsi    = array();
  $gains  = 0;
  $losses = 0;
  
  // Do calculation
  for ($i = 1; $i < count($data); $i++) {
    $change = $data[$i] - $data[$i - 1];
    if ($change > 0) {
      $gains += $change;
    } else {
      $losses -= $change;
    }
    
    if ($i >= $period) {
      // Average gains and losses
      $avgGain = $gains / $period;
      $avgLoss = $losses / $period;
      
      // Calculate RS
      $rs = $avgLoss == 0 ? 100 : $avgGain / $avgLoss;
      
      // Calculate RSI
      $rsi[$i] = 100 - (100 / (1 + $rs));
      
      // Adjust gains and losses for next iteration
      if ($i + 1 < count($data)) {
        $nextChange = $data[$i + 1] - $data[$i];
        if ($nextChange > 0) {
          $gains = $avgGain * ($period - 1) + $nextChange;
          $losses = $avgLoss * ($period - 1);
        } else {
          $gains = $avgGain * ($period - 1);
          $losses = $avgLoss * ($period - 1) - $nextChange;
        }
      }
    }
  }
  
  // Remap array
  //$rsi = remapArray($rsi, $period);
  
  return $rsi;
}

/*===========================================================================*/

// Function to calculate Momentum (OK)
function calcMomentum($data, $period) {
  $momentumValues = [];
  
  foreach ($data as $index => $value) {
    if ($index >= $period) {
      // Calculate momentum as the difference between current value and the value $period steps ago.
      $momentum = $value - $data[$index - $period];
    } else {
      // If the period exceeds the index, set momentum to null
      $momentum = null;
    }
    $momentumValues[] = $momentum;
  }
  
  // Remove empty values
  $momentumValues = removeEmptyValues($momentumValues);
  
  return $momentumValues;
}

/*===========================================================================*/

// Function to calculate MACD (OK)
function calcMACD($data, $fastPeriod, $slowPeriod, $signalPeriod) {
  
  // Calculate fast EMA
  $fastEMA = calcEMA($data, $fastPeriod);
  
  // Calculate slow EMA
  $slowEMA = calcEMA($data, $slowPeriod);
  
  // Initialize MACD array
  $macd = [];
  
  // Calculate MACD values
  for ($i = 0; $i < count($data); $i++) {
    if (!isset($fastEMA[$i]) || !isset($slowEMA[$i])) {
      // Ensure both EMAs are set for the current index
      $macd[$i] = null;
    } else {
      $macd[$i] = $fastEMA[$i] - $slowEMA[$i];
    }
  }
  
  // Calculate Signal line (EMA of MACD)
  $signalLine = calcEMA($macd, $signalPeriod);
  
  // Initialize histogram array
  $histogram = [];
  
  // Calculate Histogram values
  for ($i = 0; $i < count($macd); $i++) {
    if (!isset($macd[$i]) || !isset($signalLine[$i])) {
      // Ensure both MACD and Signal line are set for the current index
      $histogram[$i] = null;
    } else {
      $histogram[$i] = $macd[$i] - $signalLine[$i];
    }
  }
  
  // Combine MACD, Signal line, and Histogram into a single array
  $result = [];
  for ($i = 0; $i < count($data); $i++) {
    $result[$i] = [
      'macd' => $macd[$i],
      'signal' => $signalLine[$i],
      'histogram' => $histogram[$i]
    ];
  }
  
  // Unset arrays with an emtpy element
  $result = removeEmptyArrays($result);
  
  return $result;
}

/*===========================================================================*/

// Function to calculate CCI (Commodity Channel Index) (OK)
function calcCCI($high, $low, $close, $period) {
  
  $tp = array(); // Array to store Typical Prices
  $cci = array(); // Array to store CCI values
  
  // Calculate Typical Price for each day
  for ($i = 0; $i < count($high); $i++) {
    $tp[$i] = ($high[$i] + $low[$i] + $close[$i]) / 3;
  }
  
  // Calculate CCI for each day where enough data is available
  for ($i = $period - 1; $i < count($high); $i++) {
    // Get the slice of TP for the given period
    $tpSlice = array_slice($tp, $i - $period + 1, $period);
    
    // Calculate the SMA for the TP slice
    $smaTP = end(calcSMA($tpSlice, $period));
    
    // Calculate the Mean Deviation
    $meanDev = calcMeanDev($tpSlice);
    
    // Calculate CCI
    $cci[$i] = ($tp[$i] - $smaTP) / (0.015 * $meanDev);
  }
  
  return $cci;
}

/*===========================================================================*/

// Function to calculate AO (Awesome Oscillator) (OK)
function calcAO($highs, $lows, $shortPeriod, $longPeriod) {
  
  // Calculate midpoints for each period
  $midpoints = array();
  for ($i = 0; $i < count($highs); $i++) {
    $midpoints[] = ($highs[$i] + $lows[$i]) / 2;
  }
  
  // Calculate SMA for the short and long periods
  $shortSMA = calcSMA($midpoints, $shortPeriod);
  $longSMA = calcSMA($midpoints, $longPeriod);
  
  $aoValues = array();
  $startIndex = $longPeriod - 1;
  for ($i = $startIndex; $i < count($highs); $i++) {
    if (isset($longSMA[$i])) {
      $aoValues[] = $shortSMA[$i] - $longSMA[$i];
    }
  }
  
  return $aoValues;
}

/*===========================================================================*/

// Function to calculate the Williams Percentage Range (OK)
function calcWilliamsR($highs, $lows, $closes, $period) {
  
  // Initialize variable
  $williamsR = array();
  
  // Do calculation
  for ($i = 0; $i < count($closes); $i++) {
    // Check if we have enough data to calculate Williams %R
    if ($i >= $period - 1) {
      $highPeriod = array_slice($highs, $i - $period + 1, $period);
      $lowPeriod = array_slice($lows, $i - $period + 1, $period);
      $close = $closes[$i];
      
      $highestHigh = max($highPeriod);
      $lowestLow = min($lowPeriod);
      
      // Avoid division by zero
      if ($highestHigh != $lowestLow) {
        $williamsRValue = (($highestHigh - $close) / ($highestHigh - $lowestLow)) * -100;
      } else {
        $williamsRValue = 0; // Assign a default or error value
      }
      
      $williamsR[] = $williamsRValue;
    } else {
      // Not enough data to compute Williams %R
      $williamsR[] = null;
    }
  }
  
  // Remove empty values
  $williamsR = removeEmptyValues($williamsR);
  
  return $williamsR;
}

/*===========================================================================*/

// Function to calculate the Bull and Bear Power (OK)
function calcBullBear($highs, $lows, $closes, $period) {
  
  // Calculate the EMA of the closing prices.
  $ema = calcEMA($closes, $period);
  
  $bullPower     = [];
  $bearPower     = [];
  $bullbearPower = [];
  
  // Calculating Bull and Bear Power
  for ($i = 0; $i < count($highs); $i++) {
    if (isset($ema[$i])) {
      $bullPower[$i]     = $highs[$i] - $ema[$i];
      $bearPower[$i]     = $lows[$i] - $ema[$i];
      $bullbearPower[$i] = $bullPower[$i] + $bearPower[$i];
    } else {
      // In case EMA is not available for the initial period
      $bullPower[$i]     = null;
      $bearPower[$i]     = null;
      $bullbearPower[$i] = null;
    }
  }
  
  // Combine all powers into a single array
  $result = [];
  for ($i = 0; $i < count($highs); $i++) {
    $result[$i] = [
      'bull'     => $bullPower[$i],
      'bear'     => $bearPower[$i],
      'bullbear' => $bullbearPower[$i]
    ];
  }
  
  // Unset arrays with an emtpy element
  $result = removeEmptyArrays($result);
  
  return $result;
}

/*===========================================================================*/

// Function to calculate the UO (Ultimate Oscillator) (OK)
function calcUO($highs, $lows, $closes, $period1, $period2, $period3) {
  
  // Initialize variables
  $bp = [];        // Buying Pressure
  $tr = [];        // True Range
  $uoValues = [];  // Array to store Ultimate Oscillator values
  
  $len = count($closes);
  
  // Calculate BP and TR for each day
  for ($i = 0; $i < $len; $i++) {
    $prevClose = $i === 0 ? $closes[$i] : $closes[$i - 1];
    $bp[] = $closes[$i] - min($lows[$i], $prevClose);
    $tr[] = max($highs[$i], $prevClose) - min($lows[$i], $prevClose);
  }
  
  // Function to calculate the sum of N elements up to index i of an array
  $sumLastN = function($arr, $n, $i) {
    $start = max(0, $i - $n + 1);
    return array_sum(array_slice($arr, $start, $n));
  };
  
  // Calculate UO for each possible period starting from the largest period
  for ($i = max($period1, $period2, $period3) - 1; $i < $len; $i++) {
    $avg1 = $sumLastN($bp, $period1, $i) / $sumLastN($tr, $period1, $i);
    $avg2 = $sumLastN($bp, $period2, $i) / $sumLastN($tr, $period2, $i);
    $avg3 = $sumLastN($bp, $period3, $i) / $sumLastN($tr, $period3, $i);
    
    $uo = 100 * ((4 * $avg1) + (2 * $avg2) + $avg3) / 7;
    $uoValues[$i] = $uo;
  }
  
  return $uoValues;
}

/*===========================================================================*/

// Function to calculate Stochastic %K Oscillator (OK)
function calcStochKD($highs, $lows, $closes, $periodD, $smoothK, $smoothD) {
  
  // Initialize arrays for %K and %D
  $kValues = array();
  $dValues = array();
  
  // Ensure input arrays are of the same length
  if (count($highs) !== count($lows) || count($lows) !== count($closes)) {
    return ['error' => 'Input arrays must be of the same length'];
  }
  
  // Calculate %K values
  for ($i = 0; $i < count($closes); $i++) {
    if ($i >= $periodD - 1) {
      $highestHigh = max(array_slice($highs, $i - $periodD + 1, $periodD));
      $lowestLow = min(array_slice($lows, $i - $periodD + 1, $periodD));
      $k = 100 * ($closes[$i] - $lowestLow) / ($highestHigh - $lowestLow);
      $kValues[] = $k;
    }
  }
  
  // Apply smoothing to %K
  $kValuesSmoothed = calcSMA($kValues, $smoothK);
  
  // Calculate %D values
  $dValues = calcSMA($kValuesSmoothed, $smoothD);
  
  // Combine all powers into a single array
  $result = [];
  for ($i = 0; $i < count($highs); $i++) {
    $result[$i + ($periodD - 1)] = [
      'k' => $kValuesSmoothed[$i],
      'd' => $dValues[$i],
    ];
  }
  
  // Unset arrays with an emtpy element
  $result = removeEmptyArrays($result);
  
  // Return the %K and %D arrays
  return $result;
}

/*===========================================================================*/

// Function to calculate Stochastic RSI (OK)
function calcStochRSI($closes, $periodRSI, $periodStoch, $smoothK, $smoothD) {
  
  // Step 1: Calculate RSI
  $rsiValues = calcRSI($closes, $periodRSI);
  
  // Step 2: Calculate Stochastic RSI
  $stochRSIValues = [];
  for ($i = 0; $i < count($rsiValues); $i++) {
    if ($i < $periodStoch - 1) {
      // Not enough data to calculate Stochastic RSI
      $stochRSIValues[] = null;
      continue;
    }
    
    $rsiSlice = array_slice($rsiValues, $i - $periodStoch + 1, $periodStoch);
    $lowestRSI = min($rsiSlice);
    $highestRSI = max($rsiSlice);
    $currentRSI = $rsiValues[$i + 14];
    
    if ($highestRSI - $lowestRSI == 0) {
      $stochRSIValues[] = 0;
    } else {
      $stochRSI = ($currentRSI - $lowestRSI) / ($highestRSI - $lowestRSI);
      $stochRSIValues[] = $stochRSI;
    }
  }
  
  // Smooth the StochRSI (K line) and Calculate the D Line
  $kValuesSmoothed = calcSMA($stochRSIValues, $smoothK);
  $dValuesSmoothed = calcSMA($kValuesSmoothed, $smoothD);
  
  // Combine K and D values into a single array
  $counter = 0;
  $startin = max($periodRSI, $periodStoch) + max($smoothK, $smoothD) - 1;
  $result  = [];
  foreach ($kValuesSmoothed as $i => $kValue) {
    if (isset($dValuesSmoothed[$i])) {
      $result[$counter + $startin] = [
        'k' => $kValue * 100,
        'd' => $dValuesSmoothed[$i] * 100,
      ];
    }
    $counter++;
  }
  
  // Return the %K and %D arrays
  return $result;
}

/*===========================================================================*/

// Function to calculate Average Direction Index ADX (?)
function calcADX($highs, $lows, $closes, $period) {
  
  // Initialize variables
  $dataCount = count($closes);
  $endLoop   = $dataCount;
  $plusDM    = $minusDM = $TR = $plusDI = $minusDI = $DX = [];
  
  // Calculate +DM, -DM, and TR
  for ($i = 1; $i < $endLoop; $i++) {
    $plusDM[$i] = $highs[$i] - $highs[$i - 1];
    $minusDM[$i] = $lows[$i - 1] - $lows[$i];
    if ($plusDM[$i] < 0 || $plusDM[$i] < $minusDM[$i]) $plusDM[$i] = 0;
    if ($minusDM[$i] < 0 || $minusDM[$i] < $plusDM[$i]) $minusDM[$i] = 0;
    $TR[$i] = max($highs[$i] - $lows[$i], abs($highs[$i] - $closes[$i - 1]), abs($lows[$i] - $closes[$i - 1]));
  }
  
  // Calculate Smoothed +DM, -DM, and TR
  $smoothedPlusDM  = calcWWMA($plusDM, $period);
  $smoothedMinusDM = calcWWMA($minusDM, $period);
  $smoothedTR      = calcWWMA($TR, $period);
  
  // Calculate +DI and -DI
  for ($i = $period; $i < $endLoop; $i++) {
    $plusDI[$i]  = ($smoothedPlusDM[$i] / $smoothedTR[$i]) * 100;
    $minusDI[$i] = ($smoothedMinusDM[$i] / $smoothedTR[$i]) * 100;
    $DX[$i]      = abs($plusDI[$i] - $minusDI[$i]) / ($plusDI[$i] + $minusDI[$i]) * 100;
  }
   
  // Calculate ADX
  $ADX = calcEMA($DX, $period);
    
  // Combine all powers into a single array
  $result     = [];
  $startIndex = array_key_first($ADX);
  for ($i = $startIndex; $i < $dataCount; $i++) {
    $result[$i] = [
      'ADX' => $ADX[$i],
      'plusDI' => $plusDI[$i],
      'minusDI' => $minusDI[$i]
    ];
  }
  
  return $result;
}

/*===========================================================================*/

// Function to calculate Hull Moving Average (?)
function calcHull($data, $period) {
  
  $halfPeriod = intval($period / 2);
  $sqrtPeriod = intval(sqrt($period));
  $WMA_half = calcWMA($data, $halfPeriod);
  $WMA_full = calcWMA($data, $period);
  
  // Find first real values
  $unsetIndex = max(firstvalueArray($WMA_half), firstvalueArray($WMA_full));
  
  // Balance the arrays
  for ($i = 0; $i < ($unsetIndex); $i++) {
    unset($WMA_half[$i]);
    unset($WMA_full[$i]);
  }
  
  //echo "WMA Half\n";
  //print_r($WMA_half);
  
  //echo "WMA Full\n";
  //print_r($WMA_full);
  
  // Calculate the difference and then the Hull Moving Average
  $Difference = array_map(function ($half, $full) {
    return 2 * $half - $full;
  }, $WMA_half, $WMA_full);
  
  //echo "Difference:\n";
  //print_r($Difference);
  
  // Calculate the final HMA
  $HMA = calcWMA($Difference, $sqrtPeriod);
  
  // Remove empty values
  $HMA = removeEmptyValues($HMA);
  
  // Remap array
  $HMA = remapArray($HMA, $period);
  
  return $HMA;
}

/*===========================================================================*/

// Function to check if previous value was lower (default) or higher (OK)
function checkHL($data, $item = "", $invert = false) {

  // Initialize variables
  $check = false;
  
  // Check for item
  if (empty($item)) {
    $lastValue  = end($data);
    $slastValue = end(array_slice($data, 0, -1));
  } else {
    $lastValue  = end($data)[$item];
    $slastValue = end(array_slice($data, 0, -1))[$item];
  }
  
  // Check
  if ($lastValue >= $slastValue) {
    $check = true;
  } else {
    $check = false;
  }
  
  // Invert
  if ($invert) {
    $check = !$check;
  }
  
  return $check;   
}


/*===========================================================================*/
/* TO DO                                                                     */
/*===========================================================================*/

/*
 * Function to calculate Volatility
 * Function to calculate Rate Of Change
 * Function to calculate Change percentage
*/

?>