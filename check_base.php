<?php

/**
 * @author Ebo Eppenga
 * @copyright 2023
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * check_base.php
 * Checks if it is required to buy additional base coin to fulfill fees.
 * Only runs every 24 hours, should be more than enough.
 * 
 **/

// Prevent incorrect executioon
$settings_exist = false;
if (file_exists($log_settings)) {$settings_exist = true;}
if ($settings_exist) {
  $id_check = explode(",", file_get_contents($log_settings));
  if ($id_check[0] <> $pair) {
    doError("Error: Pair in URL does not match previously traded pair! Use per ID only one pair.", $id, true);
  }
}

// Check if the settings file exists and is up to date
$settings_check = false;
if (!$settings_exist) {
  $settings_check = true;
} elseif ((time() - filemtime($log_settings)) > $repeatrun) {
  $settings_check = true;
}

// Create a new settings file
if ($settings_check) {
  
  // Determine status, baseAsset, quoteAsset, etc..
  $minimums = [];
  try {
    $info = $api->market()->getInstrumentsInfo([
      'category' => 'spot',
      'symbol'   => $pair
    ]);  
  } catch (\Exception $e) {
    $message = $e->getMessage();
    doError($message, $id, true);
  }
  logAPI($info, "check_base");


  // Assign to variable
  $minimums['pair']           = $pair;                                                          // 00. Symbol
  $minimums['status']         = $info['result']['list'][0]['status'];                           // 01. Is the symbol trading?
  $minimums['baseAsset']      = $info['result']['list'][0]['baseCoin'];                         // 02. Base asset, in case of BTCUSDT it is BTC 
  $minimums['quoteAsset']     = $info['result']['list'][0]['quoteCoin'];                        // 03. Quote asset, in case of BTCUSDT it is USDT
  $minimums['basePrecision']  = $info['result']['list'][0]['lotSizeFilter']['basePrecision'];   // 04. Decimal precision of base asset
  $minimums['quotePrecision'] = $info['result']['list'][0]['lotSizeFilter']['quotePrecision'];  // 05. Decimal precision of quote asset
  $minimums['minOrderQty']    = $info['result']['list'][0]['lotSizeFilter']['minOrderQty'];     // 06. Minimum order quantity in base asset
  $minimums['maxOrderQty']    = $info['result']['list'][0]['lotSizeFilter']['maxOrderQty'];     // 07. Maximum order quantity in base asset
  $minimums['minOrderAmt']    = $info['result']['list'][0]['lotSizeFilter']['minOrderAmt'];     // 08. Minimum order quantity in quote asset
  $minimums['maxOrderAmt']    = $info['result']['list'][0]['lotSizeFilter']['maxOrderAmt'];     // 09. Maximum order quantity in quote asset
  $minimums['tickSize']       = $info['result']['list'][0]['priceFilter']['tickSize'];          // 10. Smallest possible increment in base asset

  // Write new settings file
  $message = implode(",", $minimums);
  file_put_contents($log_settings, $message);

  // Report
  echo "<b>Settings</b><br />";
  echo "Symbol        : " . $minimums['pair'] . "<br />";
  echo "Status        : " . $minimums['status'] . "<br />";
  echo "baseAsset     : " . $minimums['baseAsset'] . "<br />";
  echo "quoteAsset    : " . $minimums['quoteAsset'] . "<br />";
  echo "basePrecision : " . $minimums['basePrecision'] . "<br />";
  echo "quotePrecision: " . $minimums['quotePrecision'] . "<br />";
  echo "minOrderQty   : " . $minimums['minOrderQty'] . "<br />";
  echo "maxOrderQty   : " . $minimums['maxOrderQty'] . "<br />";
  echo "minOrderAmt   : " . $minimums['minOrderAmt'] . "<br />";
  echo "maxOrderAmt   : " . $minimums['maxOrderAmt'] . "<br />";
  echo "tickSize      : " . $minimums['tickSize'] . "<br />";
    
  // Check if we can continue
  if ($minimums['status'] <> "Trading") {
    doError("Error: Pair not trading", $id, true);
  }
  
  echo "<hr />";
}

?>
