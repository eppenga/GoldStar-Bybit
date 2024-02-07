<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * func_tradeview.php
 * Tradeview functions required for GoldStar to run properly.
 * 
 **/

 
/** Get TradingView recommendation **/
// $period = 1m: 1, 5m: 5, 15m: 15, 30m: 30, 1h: 60, 2h: 120, 4h: 240, 1W: 1W, 1M: 1M, 1d: leave emtpy (default)
function getTradingView($symbol, $period) {

  // Declare bot ID variable as global
  global $id;

	// Retrieve from TradingView
	$curl = curl_init();
  $postField = '{"symbols":{"tickers":["BYBIT:' . $symbol . '"],"query":{"types":[]}},"columns":["Recommend.All|' . $period . '"]}';
  curl_setopt_array($curl, array(
  	CURLOPT_URL => "https://scanner.tradingview.com/crypto/scan",
  	CURLOPT_RETURNTRANSFER => true,
  	CURLOPT_CUSTOMREQUEST => "POST",
  	CURLOPT_POSTFIELDS => $postField,
  	CURLOPT_HTTPHEADER => array(
  		"accept: */*",
  		"accept-language: en-GB,en-US;q=0.9,en;q=0.8",
  		"cache-control: no-cache",
  		"content-type: application/x-www-form-urlencoded",
  		"origin: https://www.tradingview.com",
  		"referer: https://www.tradingview.com/",
  		"user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.96 Safari/537.36"
  	)
	));

  // Store result
  try {
    $result = curl_exec($curl);
    if (isset($result) && !empty($result)) {
  	    $j = json_decode($result, true);
  		if (isset($j['data'][0]['d'][0])) {
  			$j = $j['data'][0]['d'][0];
  		}
    }
	} catch (Exception $e) {
	  doError($e, $id, false); 
	}
	
  // Set recommendation
  $recommendation = "ERROR";
  if (($j > -1) && ($j < 1)) {
    $recommendation = $j;
  }

  return $recommendation;
}

/** Evaluate TradingView recommendation for array of periods **/
// STRONG_SELL: -1...-0.5, SELL: -0.5...-0.1, NEUTRAL: -0.1...0.1, BUY: 0.1...0.5, STRONG_BUY: 0.5...1
function evalTradingView($symbol, $periods, $tv_recomMin, $tv_recomMax) {

  // Get recommendations for periods
  foreach ($periods as $period) {
    $recommendation    = getTradingView($symbol, $period);
    $recommendations[] = $recommendation;
    echo " " . $period . "m:" . round($recommendation, 2). ",";
  }

  // Determine total recommendation
  $evalTV = true;
  foreach ($recommendations as $recommendation) {
    if (($recommendation < $tv_recomMin) || ($recommendation > $tv_recomMax)) {
      $evalTV = false;
    }
  }

  return $evalTV;
}

?>