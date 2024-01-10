<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * func_setcoin.php
 * Prepare all trade data required for GoldStar to run properly.
 * 
 **/


/** Get all data ready before possible trading **/
function setCoin() {

  // Declare some variables as global
  global $api, $log_settings, $compounding, $multiplier, $price, $fee, $id, $debug;

  // Get settings
  if (file_exists($log_settings)) {
    $settings = explode(",", file_get_contents($log_settings));    
  } else {
    doError("Error: No settings file was created automatically!", $id, true);
  }
  
  // Assign coin settings
  $set_coin['symbol']         = $settings[0];
  $set_coin['status']         = $settings[1];
  $set_coin['baseAsset']      = $settings[2];
  $set_coin['quoteAsset']     = $settings[3];
  $set_coin['basePrecision']  = $settings[4];
  $set_coin['quotePrecision'] = $settings[5];  
  $set_coin['minOrderQty']    = $settings[6];
  $set_coin['maxOrderQty']    = $settings[7];
  $set_coin['minOrderAmt']    = $settings[8];
  $set_coin['maxOrderAmt']    = $settings[9];
  $set_coin['tickSize']       = $settings[10];

  // Set price
  $set_coin['price']        = $price;        

  // Get wallet information
  try {
    $wallet = $api->account()->getWalletBalance([
      'accountType'=>'UNIFIED'
    ]);
  } catch (\Exception $e) {
    $message = $e->getMessage();
    doError($message, $id, true);
  }
  logAPI($wallet, "setCoin");
  
  // Get total wallet 
  $set_coin['walletTotal']= $wallet['result']['list'][0]['totalEquity'];
  
  // Determine total and free balance of the coin in Base
  $walletBalance       = 0;
  $availableToWithdraw = 0;
  foreach ($wallet['result']['list'][0]['coin'] as $entry) {
    if ($entry['coin'] == $set_coin['baseAsset']) {
        $walletBalance       = $entry['walletBalance'];
        $availableToWithdraw = $entry['availableToWithdraw'];
        break; // Exit the loop once we find the value
    }
  }
  $set_coin['balanceBase']   = $walletBalance;
  $set_coin['availWithdraw'] = $availableToWithdraw;

  // Determine balance of the coin in Quote
  $walletBalance = 0;
  foreach ($wallet['result']['list'][0]['coin'] as $entry) {
    if ($entry['coin'] == $set_coin['quoteAsset']) {
        $walletBalance = $entry['walletBalance'];
        break; // Exit the loop once we find the value
    }
  }
  $set_coin['balanceQuote'] = $walletBalance;

  if ($debug) {
    echo "<b>Balances</b><br />";
    echo "Total Wallet          : " . $set_coin['walletTotal'] . " USDT<br />";
    echo "Balance in Base       : " . $set_coin['balanceBase'] . " " . $set_coin['baseAsset'] . "<br />";
    echo "Balance in Quote      : " . $set_coin['balanceQuote'] . " " . $set_coin['quoteAsset'] . "<br /><br />";  
  }

  // Calculate minimum order value and add 10% to prevent strange errors
  $minimumQty = $set_coin['minOrderQty'] * $price;
  $minimumAmt = $set_coin['minOrderAmt'];
  if ($minimumQty < $minimumAmt) {
    $minimumOrder = ($minimumAmt / $price) * 1.1;
  } else {
    $minimumOrder = ($minimumQty / $price) * 1.1;
  }
  $set_coin['minBuyBase'] = $minimumOrder;

  if ($debug) {
    echo "<b>Minimum Order</b><br />";
    echo "minOrderQty           : " . $set_coin['minOrderQty'] . "<br />";
    echo "minOrderAmt           : " . $set_coin['minOrderAmt'] . "<br />";
    echo "Minimum Order Quantity: " . $set_coin['minBuyBase'] . "<br />";
    echo "Minimum Order Value   : " . $set_coin['minBuyBase'] * $price . "<br /><br />";  
  }
  
  // Correct for compounding
  $set_coin['compFactor'] = 1;
  if (!empty($compounding)) {
    
    // Calculate compounding in USDT
    $comp_pair = $set_coin['baseAsset'] . "USDT";
    if ($set_coin['quoteAsset'] <> "USDT") {
      $startUSDT = floatval(getPrice($comp_pair)) * $compounding;
    } else {
      $startUSDT = $compounding;
    }
    
    // Caculate new minimum order when compounding
    $set_coin['compFactor'] = $set_coin['walletTotal'] / $startUSDT;
    if ($set_coin['compFactor'] > 1) {
      $set_coin['minBuyBase']   = $set_coin['minBuyBase'] * $set_coin['compFactor'];
    }
  }
  
  if ($debug) {
    echo "<b>Compounding</b><br />";
    echo "Pair                  : " . $set_coin['symbol'] . "<br />";
    echo "Compound Pair         : " . $comp_pair . "<br />";
    echo "Compound in Base      : " . $compounding . "<br />";
    echo "Compound in USDT      : " . $startUSDT . "<br />";
    echo "Compound Factor       : " . $set_coin['compFactor'] . "<br />";
    echo "Minimum Order Quantity: " . $set_coin['minBuyBase'] . "<br /><br />";  
  }

  // Correct for multiplier
  $set_coin['multiplier']  = $multiplier;
  $set_coin['minBuyBase'] = $set_coin['minBuyBase'] * $multiplier;
  
  // Calculate minimum order value in quote asset
  $set_coin['minBuyQuote']  = $set_coin['minBuyBase'] * $price;

  if ($debug) {
    echo "<b>Multiplier</b><br />";
    echo "Multiplier Factor     : " . $set_coin['multiplier'] . "<br />";
    echo "Minimum Order Quantity: " . $set_coin['minBuyBase'] . "<br /><br />";  
  }

  // Fix exchange precission errors
  //$set_coin['minBuyBase']  = roundStep($set_coin['minBuyBase'], $set_coin['basePrecision']);
  //$set_coin['minBuyQuote'] = roundStep($set_coin['minBuyQuote'], $set_coin['quotePrecision']);
  
  // Calculate expected fee
  $set_coin['feeExpected'] = $set_coin['minBuyBase'] * ($fee / 100);

  if ($debug) {
    echo "<b>Order precission</b><br />";
    echo "Minimum Order Quantity in Quote Asset: " . $set_coin['minBuyBase'] . "<br />";
    echo "Minimum Order Quantity in Base Asset : " . $set_coin['minBuyQuote'] . "<br /><br />";  
  }

  // Summerize all data and return
  $data['symbol']         = $set_coin['symbol'];          // Symbol
  $data['price']          = $set_coin['price'];           // Price
  $data['status']         = $set_coin['status'];          // Is the symbol trading
  $data['baseAsset']      = $set_coin['baseAsset'];       // Base asset, in case of BTCUSDT it is BTC
  $data['quoteAsset']     = $set_coin['quoteAsset'];      // Quote asset, in case of BTCUSDT it is USDT
  $data['basePrecision']  = $set_coin['basePrecision'];   // Decimal precision of base asset
  $data['quotePrecision'] = $set_coin['quotePrecision'];  // Decimal precision of quote asset
  $data['minOrderQty']    = $set_coin['minOrderQty'];     // Minimum order quantity in base asset
  $data['maxOrderQty']    = $set_coin['maxOrderQty'];     // Maximum order quantity in base asset
  $data['minOrderAmt']    = $set_coin['minOrderAmt'];     // Minimum order quantity in quote asset
  $data['maxOrderAmt']    = $set_coin['maxOrderAmt'];     // Maximum order quantity in quote asset
  $data['tickSize']       = $set_coin['tickSize'];        // Smallest possible increment in quote asset for BUY and SELL orders
  $data['balanceBase']    = $set_coin['balanceBase'];     // How much of the base asset is available in the account on the exchange
  $data['balanceQuote']   = $set_coin['balanceQuote'];    // How much of the quote asset is available in the account on the exchange 
  $data['minBuyBase']     = $set_coin['minBuyBase'];      // Minimum BUY value in Base Asset (possibly corrected for compounding & multiplier!)
  $data['minBuyQuote']    = $set_coin['minBuyQuote'];     // Minimum BUY value in Quote Asset (possibly corrected for compounding & multiplier!)
  $data['feeExpected']    = $set_coin['feeExpected'];     // Expected fee on buy order
  $data['compFactor']     = $set_coin['compFactor'];      // Compounding factor
  $data['multiplier']     = $set_coin['multiplier'];      // Multiplier factor
  $data['walletTotal']    = $set_coin['walletTotal'];     // Wallet total of the entire account in quote asset
  $data['availWithdraw']  = $set_coin['availWithdraw'];   // Available amount to withdraw of current coin in base asset
  
  if ($debug) {
    echo "<b>Trade Data</b><br />";
    print_r($data);
  }

  return $data;  
}

?>