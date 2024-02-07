<?php

/**
* @author Ebo Eppenga
* @copyright 2022
*
* GoldStar Buy and Sell bot based on signals from for example TradeView
* or any other platform using PHP Bybit API from zhouaini528.
* 
* func_klines.php
* Get KLines from exchanges and add some helper functions
* 
**/


/*===========================================================================*/
/* Get price information                                                     */
/*===========================================================================*/

// Function to get historical prices
function getKlines($symbol, $interval, $period) {
  
  // Declare API and id as global
  global $api, $id;
  
  // Get the data from the exchange
  try {
    $result=$api->market()->getKline([
      'category' => 'spot',  
      'symbol'   => $symbol,
      'interval' => strval($interval),
      'limit'    => $period
    ]);
  }catch (\Exception $e){
    $message = $e->getMessage();
    doError($message, $id, true);
  }
  logAPI($result, "getKlines");
  
  // Convert the data to the desired format
  $klines = [];
  foreach ($result['result']['list'] as $kline_data) {
    $klines[] = [
      "time"     => $kline_data[0],
      "open"     => $kline_data[1],
      "high"     => $kline_data[2],
      "low"      => $kline_data[3],
      "close"    => $kline_data[4],
      "volume"   => $kline_data[5],
      "turnover" => $kline_data[6]
    ];
  }
  
  // Reverse the array as Bybit delivers newest items first
  $klines = array_reverse($klines);
  
  return $klines;
}

/*===========================================================================*/
/* Helper functions                                                         */
/*===========================================================================*/

// Function to select the first $period of KLines
function sliceKlines($klines, $period) {
  
  // Count and check number of klines
  $count = count($klines);
  if ($count < $period) {
    echo "Not enough KLines provided!";
    exit();
  }
  
  // Slice the array
  $sliceKlines = array_slice($klines, -1 * $period);
  
  return $sliceKlines;
}

// Function to split the KLines into prices
function splitKlines($klines, $element = 'close') {
  
  foreach ($klines as $item) {
    if (isset($item[$element])) {
      $splitKlines[] = $item[$element];
    }
  }
  
  return $splitKlines;
}


?>
