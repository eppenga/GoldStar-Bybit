<?php

/**
* @author Ebo Eppenga
* @copyright 2022
*
* GoldStar Buy and Sell bot based on signals from for example TradeView
* or any other platform using PHP Bybit API from zhouaini528.
* 
* func_advice.php
* Combine indicators to calculate a BUY or SELL advice
* 
**/


// Function to determine BUY or SELL advice based on all technical indicators
function indicatorResults($symbol, $interval) {
  
  // Declare debug as global
  global $debug;
  
  // Initialize $advice
  $advice = [];
  
  // Get KLines
  $klines    = getKlines($symbol, $interval, 250);
  
  // Split KLines in prices and volunes
  $opens   = splitKlines($klines, 'open');
  $closes  = splitKlines($klines, 'close');
  $lows    = splitKlines($klines, 'low');
  $highs   = splitKlines($klines, 'high');
  $volumes = splitKlines($klines, 'volume');
  
  // Get Oscillators
  $rsi       = end(calcRSI($closes, 14));
  $stochkd   = end(calcStochKD($highs, $lows, $closes, 14, 3, 3));
  $cci       = end(calcCCI($highs, $lows, $closes, 20));
  $adx       = end(calcADX($highs, $lows, $closes, 14));
  $awesome   = calcAO($highs, $lows, 5, 34);
  $momentum  = end(calcMomentum($closes, 10));
  $macd      = calcMACD($closes, 12, 26, 9);
  $stochrsi  = end(calcStochRSI($closes, 14, 14, 3, 3));
  $williams  = end(calcWilliamsR($highs, $lows, $closes, 14));
  $bullbear  = calcBullBear($highs, $lows, $closes, 13);
  $ultimate  = end(calcUO($highs, $lows, $closes, 7, 14, 28));

  // Get Moving Averages
  $sma10     = end(calcSMA($closes, 10));
  $sma20     = end(calcSMA($closes, 20));
  $sma30     = end(calcSMA($closes, 30));
  $sma50     = end(calcSMA($closes, 50));
  $sma100    = end(calcSMA($closes, 100));
  $sma200    = end(calcSMA($closes, 200));
  
  $ema10     = end(calcEMA($closes, 10));
  $ema20     = end(calcEMA($closes, 20));
  $ema30     = end(calcEMA($closes, 30));
  $ema50     = end(calcEMA($closes, 50));
  $ema100    = end(calcEMA($closes, 100));
  $ema200    = end(calcEMA($closes, 200));
  //$ichimoku    = end(calcIchimoku());
  $hull      = end(calcHull($closes, 9));
  $vwma      = end(calcVWMA($closes, $volumes, 20));

  /*===========================================================================*/

  // Advice for EMA and SMA
  $advice[$symbol]['ema10']  = [$ema10, advESMA($ema10), 'A'];
  $advice[$symbol]['sma10']  = [$sma10, advESMA($sma10), 'A'];
  
  $advice[$symbol]['ema20']  = [$ema20, advESMA($ema20), 'A'];
  $advice[$symbol]['sma20']  = [$sma20, advESMA($sma20), 'A'];
  
  $advice[$symbol]['ema30']  = [$ema30, advESMA($ema30), 'A'];
  $advice[$symbol]['sma30']  = [$sma30, advESMA($sma30), 'A'];
  
  $advice[$symbol]['ema50']  = [$ema50, advESMA($ema50), 'A'];
  $advice[$symbol]['sma50']  = [$sma50, advESMA($sma50), 'A'];
  
  $advice[$symbol]['ema100'] = [$ema100, advESMA($ema100), 'A'];
  $advice[$symbol]['sma100'] = [$sma100, advESMA($sma100), 'A'];
  
  $advice[$symbol]['ema200'] = [$ema200, advESMA($ema200), 'A'];
  $advice[$symbol]['sma200'] = [$sma200, advESMA($sma200), 'A'];
  
  // Advice for Ichimoku (to be implemented)
  
  // Advice for Volume Weighted Moving Average
  $advice[$symbol]['vwma']   = [$vwma, advESMA($vwma), 'A'];
  
  // Advice for Hull Moving Average (to be implemented)
  $advice[$symbol]['hull']   = [$hull, advESMA($hull), 'A'];
  
  // Advice for RSI
  unset($bsn);
  if ($rsi > 70)   {$bsn = 'S';}
  if ($rsi < 30)   {$bsn = 'B';}
  if (empty($bsn)) {$bsn = 'N';}
  $advice[$symbol]['rsi'] = [$rsi, $bsn, 'O'];
  
  // Advice for Stochastic %K (simple version, add %D later)
  unset($bsn);
  if ($stochkd['k'] < 20) {
    if ($stochkd['k'] > $stochkd['d']) {$bsn = 'B';}
  }
  if ($stochkd['k'] > 80) {
    if ($stochkd['k'] < $stochkd['d']) {$bsn = 'S';}
  }
  if (empty($bsn)) {$bsn = 'N';}
  $advice[$symbol]['stochkd'] = [$stochkd, $bsn, 'O'];
  
  // Advice for CCI 
  unset($bsn);
  if ($cci > 100)  {$bsn = 'B';}
  if ($cci < -100) {$bsn = 'S';}
  if (empty($bsn)) {$bsn = 'N';}
  $advice[$symbol]['cci'] = [$cci, $bsn, 'O'];
  
  // Advice for ADX (add and plusDI is rising?)
  unset($bsn);
  if ($adx['ADX'] > 25) {
    if ($adx['plusDI'] > $adx['minusDI']) {$bsn = 'B';}
    if ($adx['plusDI'] < $adx['minusDI']) {$bsn = 'S';}    
  }
  if (empty($bsn)) {$bsn = 'N';}  
  $advice[$symbol]['adx'] = [$adx, $bsn, 'O'];

  // Advice for Awesome Indicator (to be implemented)
  unset($bsn);
  if (end($awesome) >= 0) {
    if (checkHL($awesome)) {$bsn = 'B';}  
  }
  if (end($awesome) < 0) {
    if (checkHL($awesome, true)) {$bsn = 'S';}  
  }
  if (empty($bsn)) {$bsn = 'N';}  
  $advice[$symbol]['awesome'] = [end($awesome), $bsn, 'O'];

  // Advice for Momentum Indicator 
  unset($bsn);
  if ($momentum >= 0) {
    if (checkHL($closes)) {$bsn = 'B';}
  }
  if ($momentum < 0) {
    if (checkHL($closes, true)) {$bsn = 'S';}
  }
  if (empty($bsn)) {$bsn = 'N';}
  $advice[$symbol]['momentum'] = [$momentum, $bsn, 'O'];
  
  // Adivce for MACD
  unset($bsn);
  if (end($macd)['histogram'] >= 0) {
    if (checkHL($macd, 'histogram')) {$bsn = 'B';}
  }
  if (end($macd)['histogram'] < 0) {
    if (checkHL($macd, 'histogram', true)) {$bsn = 'S';}
  }
  if (empty($bsn)) {$bsn = 'N';}
  $advice[$symbol]['macd'] = [end($macd), $bsn, 'O'];
  
  // Advice for Stochastic RSI
  unset($bsn);
  if ($stochrsi['k'] < 20) {
    if ($stochrsi['k'] > $stochkd['d']) {$bsn = 'B';}
  }
  if ($stochrsi['k'] > 80) {
    if ($stochrsi['k'] < $stochkd['d']) {$bsn = 'S';}
  }
  if (empty($bsn)) {$bsn = 'N';}
  $advice[$symbol]['stochrsi'] = [$stochrsi, $bsn, 'O'];
  
  // Advice for Williams Percent Range
  unset($bsn);
  if ($williams > -20) {$bsn = 'B';}
  if ($williams < -80) {$bsn = 'S';}
  if (empty($bsn)) {$bsn = 'N';}
  $advice[$symbol]['williams'] = [$williams, $bsn, 'O'];
  
  // Advice for Bull Bear Power
  unset($bsn);
  if (end($bullbear)['bullbear' >= 0]) {
    if (checkHL($bullbear, 'bullbear')) {$bsn = 'B';}
  }
  if (end($bullbear)['bullbear' < 0]) {
    if (checkHL($bullbear, 'bullbear')) {$bsn = 'S';}
  }
  if (empty($bsn)) {$bsn = 'N';}
  $advice[$symbol]['bullbear'] = [end($bullbear), $bsn, 'O'];

  // Advice for Ultimate Oscillator
  unset($bsn);
  if ($ultimate < 30) {$bsn = 'B';}
  if ($ultimate > 70) {$bsn = 'S';}
  if (empty($bsn)) {$bsn = 'N';}
  $advice[$symbol]['ultimate'] = [$ultimate, $bsn, 'O'];
  
  // DEBUG: Display the advice
  if ($debug) {print_r($advice);}
  
  return $advice;
}

/*===========================================================================*/

// Function to get an advice for SMA, EMA and HULL
function advESMA($esma) {
  
  // Declare price and debug as global
  global $price, $debug;
  
  // Determine advice
  $bsn = '';
  if ($esma > $price) {$bsn = 'S';}
  if ($esma < $price) {$bsn = 'B';}
  if ($esma == $price) {$bsn = 'N';}
  
  return $bsn;
}

/*===========================================================================*/

// Function to weigh the advice
function indicatorAdvice($symbol, $interval) {
  
  // Declare debug as global
  global $debug;
  
  // Get indicator results
  $advice = indicatorResults($symbol, $interval);
  
  // DEBUG: Display Advice
  //print_r($advice);
  
  // Count the BUYs, SELLs and NEUTRALs
  $countA  = 0;   // Moving Averages
  $countAN = 0;   // Moving Averages Neutral
  $countAB = 0;   // Moving Averages Buy
  $countAS = 0;   // Moving Averages Sell
  $countO  = 0;   // Oscillators
  $countON = 0;   // Oscillators Neutral
  $countOB = 0;   // Oscillators Buy
  $countOS = 0;   // Oscillators Sell
  foreach ($advice as $symbolData) {
    foreach ($symbolData as $indicatorData) {
      if ($indicatorData[1] === 'B') {
        if ($indicatorData[2] == 'O') {$countOB++;} else {$countAB++;}
      }
      if ($indicatorData[1] === 'S') {
        if ($indicatorData[2] == 'O') {$countOS++;} else {$countAS++;}
      }
      if ($indicatorData[1] === 'N') {
        if ($indicatorData[2] == 'O') {$countON++;} else {$countAN++;}
      }
    }
  }
  
  // Calculate the advice
  $countA = $countAN + $countAB + $countAS;                 // Moving Averages
  $countO = $countON + $countOB + $countOS;                 // Oscillators
  $count  = $countA + $countO;                              // All
  $countB = $countAB + $countOB;                            // Total Buys
  $countS = $countAS + $countOS;                            // Total Sells
  $countN = $countAN + $countON;                            // Total Neutrals

  // Calculate strengths
  $strengthA = calcStrength($countA, $countAB, $countAS);   // Moving Averages
  $strengthO = calcStrength($countO, $countOB, $countOS);   // Oscillators
  $strength  = calcStrength($count, $countB, $countS);      // All
  
  // Get all advices
  $advice  = strengthAdvice($strength);                     // Total advice
  $adviceA = strengthAdvice($strengthA);                    // Moving Average advice
  $adviceO = strengthAdvice($strengthO);                    // Oscillator advice

  if ($debug) {
    echo "Moving Averages BUY     : " . $countAB . "\n";
    echo "Moving Averages NEUTRAL : " . $countAN . "\n";
    echo "Moving Averages SELL    : " . $countAS . "\n";
    echo "Moving Averages         : " . $countA . "\n";
    echo "Moving Averages Strength: " . $strengthA . "\n";
    echo "Moving Averages Advice  : " . $adviceA . "\n\n";

    echo "Oscillators BUY         : " . $countOB . "\n";
    echo "Oscillators NEUTRAL     : " . $countON . "\n";
    echo "Oscillators SELL        : " . $countOS . "\n";
    echo "Oscillators             : " . $countO . "\n";
    echo "Oscillators Strength    : " . $strengthO . "\n";
    echo "Oscillators Advice      : " . $adviceO . "\n\n";

    echo "All Indicators BUY      : " . $countB . "\n";
    echo "All Indicators NEUTRAL  : " . $countN . "\n";
    echo "All Indicators SELL     : " . $countS . "\n";
    echo "All Indicators          : " . $count . "\n";
    echo "All Indicators Strength : " . $strength . "\n";
    echo "All Indicators Advice   : " . $advice . "\n\n";
  }
  
  return $strength;
}

// Function to calculate strength
function calcStrength($count, $countB, $countS) {

  $strength = 0;  
  if ($countB > $countS) {
    $strength = $countB / $count;
  } else {
    $strength = -$countS / $count;
  }

  return $strength;
}

// Function to convert strength number to string
function strengthAdvice($strength) {
  
  $advice = "";
  if ($strength > 0.5) {$advice = "Strong buy";}
  if (($strength > 0.1) && ($strength <= 0.5)) {$advice = "Buy";}
  if ($strength < -0.5) {$advice = "Strong sell";}
  if (($strength < 0.1) && ($strength >= -0.5)) {$advice = "Sell";}
  if (empty($advice)) {$advice = "Neutral";}
  
  return $advice;
}

// Function to evaluate Indicator recommendation for array of periods **/
// STRONG_SELL: -1...-0.5, SELL: -0.5...-0.1, NEUTRAL: -0.1...0.1, BUY: 0.1...0.5, STRONG_BUY: 0.5...1
function evalIndicators($symbol, $intervals, $tv_recomMin, $tv_recomMax) {
  
  // Get recommendations for periods
  $recommendation = 0;
  foreach ($intervals as $interval) {
    $recommendation    = indicatorAdvice($symbol, $interval);
    $recommendations[] = $recommendation;
    echo " " . $interval . "m:" . round($recommendation, 2). ",";
  }
  
  // Determine total recommendation
  $evalInd = true;
  foreach ($recommendations as $recommendation) {
    if (($recommendation < $tv_recomMin) || ($recommendation > $tv_recomMax)) {
      $evalInd = false;
    }
  }
  
  return $evalInd;
}


?>