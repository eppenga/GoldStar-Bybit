<?php

/*
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * goldstar.php?id=a1&action=SELL&pair=MATICUSDT&key=12345
 * 
 * For more information please see
 * https://github.com/eppenga/goldstar-crypto-trading-bot
 *
 **/


// Set error reporting
error_reporting(E_ALL & ~E_NOTICE);

// Check kill switch
if (file_exists("killswitch.txt")) {echo "Killswitch actived, check all logs and fix error!<br />You can only restart after manually removing the file killswitch.txt"; exit();}

// Log query string
$log_qstring = "data/log_querystring.txt";
if (!file_exists("data/")) {mkdir("data/");}
if (!file_exists($log_qstring)) {touch($log_qstring);}
file_put_contents($log_qstring, round(microtime(true) * 1000) . "," . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND | LOCK_EX);

// Configuration
if (!file_exists("config.php")) {echo "Error: Configuration file does not exist!"; exit();}
include "config.php";

// Dispay header
include "header.php";

// Check logfiles based on Bot ID
include "check_logfiles.php";

// Check variables
include "check_variables.php";

// Functions
include "functions.php";

// Query string parameters
include "querystring.php";

// Connect to exchange
use Lin\Bybit\BybitV5;
require __DIR__ .'/vendor/autoload.php';
$api=new BybitV5($exchange_key,$exchange_secret);


/*** START PROGRAM ***/

/* Get price of the pair */
$price = (float)getPrice($pair);

/* Check if we have enough to pay fees and get important variables */
include "check_base.php";

/* Get all important variables */
$set_coin = setCoin();

/** Report **/
echo "<b>Executing</b><br />";
echo "Date       : " . date("Y-m-d H:i:s") . "<br />";
echo "Bot ID     : " . $id . "<br />";
echo "Pair       : " . $pair . "<br />";
echo "Spread     : " . $spread . "%<br />";
echo "Markup     : " . $markup . "%<br />";
echo "Multiplier : " . number_format($multiplier, 4) . "x<br />";
echo "Compounding: " . number_format($set_coin['compFactor'], 4) . "x<br />";
if ($tv_advice) {echo "TradingView: (" . $tv_recomMin . "-" . $tv_recomMax . "), (" . implode(",", $tv_periods) . ")<br />";}
echo "Balances   : " . roundStep($set_coin['balanceBase'], $set_coin['quotePrecision']) . " " . $set_coin['baseAsset'] . " / ";
echo roundStep($set_coin['balanceQuote'], $set_coin['tickSize']) . " " . $set_coin['quoteAsset'] . "<br />";
echo "Order value: " . roundStep($set_coin['minBuyBase'], $set_coin['quotePrecision']) . " " . $set_coin['baseAsset'] . " (" . roundStep($set_coin['minBuyQuote'], $set_coin['tickSize']) . " " . $set_coin['quoteAsset'] . ")<br />";
//echo "Order value: " . roundStep($set_coin['minBuyBase'], $set_coin['quotePrecision']) . " " . $set_coin['quoteAsset'] . "<br />";
echo "Total value: " . roundStep($set_coin['walletTotal'], $set_coin['tickSize']) . " " . $set_coin['quoteAsset'] . "<br />";
echo "Command    : "; if ($action <> "CHECK") {echo "MARKET ";} echo $action; if ($limit) {echo " & LIMIT SELL";} echo "<br /><hr />";


/*** BUY action ***/
if ($action == "BUY") {
  
  // Check if there are sold LIMIT orders
  if ($limit) {include("limit_filled.php");}

  // Buy cycle
  echo "<i>Trying to buy " . roundStep($set_coin['minBuyBase'], $set_coin['quotePrecision']) . " " . $set_coin['baseAsset'];
  echo " at " . roundStep($price, $set_coin['tickSize']) ." " . $set_coin['quoteAsset'] . "...</i><br /><hr />";
  
  // Check if price is outside spread
  $nobuy     = false;
  $price_min = $price * (1 - $spread / 100);
  $price_max = $price * (1 + $spread / 100);
  
  // Loop through existing orders
  $handle = fopen($log_trades, "r");
  while (($line = fgetcsv($handle)) !== false) {
    if ($line[9] <> 0) {$buy_price = $line[9];} else {$buy_price = $line[8];}
    if (($buy_price >= $price_min) && ($buy_price <= $price_max)) {
      $nobuy = true;
      echo "<i>Skipping buy because existing trade within " . round((($price / $buy_price) - 1) * 100, 2) . "%...</i><br /><hr />";
      break;
    }    
  }
  fclose($handle);
  
  // Check for TradingView advice
  if (($tv_advice) && (!$nobuy)) {
    echo "<i>TradingView says";
    $tv_eval = evalTradingView($pair, $tv_periods, $tv_recomMin, $tv_recomMax);
    if (!$tv_eval) {
      $nobuy = true;
      echo " skipping...</i><br /><hr />";
    } else {
      echo " buying...</i><br /><hr />";
    }
  }

  // Check for Indicator advice
  if (($ind_advice) && (!$nobuy)) {
    echo "<i>Indicators say";
    $ind_eval = evalIndicators($pair, $ind_periods, $ind_recomMin, $ind_recomMax);
    if (!$ind_eval) {
      $nobuy = true;
      echo " skipping...</i><br /><hr />";
    } else {
      echo " buying...</i><br /><hr />";
    }
  }

  // Prepare totals, they might be overriden when a BUY order is placed
  $total_value    = $set_coin['minBuyQuote'];
  $total_quantity = $set_coin['minBuyBase'];
  $total_fees     = $set_coin['feeExpected'];
  
  // Buy if spread=0 or no adjacent order
  if ((!$nobuy) || ($spread == 0)) {
            
    // Buy Order
    echo "<b>BUY Order</b><br />";
    
    // Check if we have enough quote balance to buy
    if ($set_coin['balanceQuote'] < (2 * $set_coin['minBuyQuote'])) {
      $message = "Error: Insufficient " . $set_coin['quoteAsset'] . " to buy " . $set_coin['baseAsset'];
      doError($message, $id, true);
    }
    
    // BUY BUY BUY!
    $buyOrder = postOrder($pair, 'Buy', 'Market', $set_coin['minBuyQuote'], 0, 0);
    logCommand($buyOrder, "exchange");

    // Set for reporting later
    $total_orders = 1;
    if (strpos($buyOrder['orderStatus'], "Filled") !== false) {
      $total_value    = $buyOrder['cumExecValue'];
      $total_quantity = $buyOrder['cumExecQty'];
      $total_fees     = $buyOrder['cumExecFee'];
    }

    // Report BUY Order
    showOrder($buyOrder);

    // Add a LIMIT order when requested
    if ($limit) {include("limit_order.php");}

    // Log BUY order and history
    $message = implode(",", $buyOrder);
    echo "<i>Updating " . $log_trades . " file for BUY order...</i><br />";
    logCommand($message, "buy");
    echo "<i>Updating " . $log_history . " file for BUY order...</i><br />";
    logCommand($message, "history");
    
    // Add revenue to log file
    logRevenue($buyOrder);
    
    // Log LIMIT order to history
    if ($limit) {
      $message = implode(",", $limitOrder);
      echo "<i>Updating " . $log_history . " file for LIMIT order...</i><br />";
      logCommand($message, "history");
      
      // Add revenue to log file
      logRevenue($limitOrder);
    }
    
    echo "<hr />";
  }
}


/*** SELL action ***/
if ($action == "SELL") {
  echo "<i>Trying to SELL...";
  
  // Check if we have orders to SELL
  if (filesize($log_trades) == 0) {echo "no BUY orders present!";}
  echo "</i><br /><hr />";
  
  // Loop through all the orders
  $counter = 0;  // Counts the number of orders checked
  $handle = fopen($log_trades, "r");
  while (($line = fgetcsv($handle)) !== false) {
    
    // Found a BUY order
    if ($line[5] == "Buy") {
      
      // Count and calculate BUY and SELL
      $counter   = $counter + 1;                                 // Count checked orders
      $quantity   = $line[11];                                   // Buy: Cumulatitive Executed Quantity
      $buy_value  = $line[12];                                   // Buy: Cumulatative Executed Value
      if ($line[9] <> 0) {$buy_price = $line[9];}                // Buy: Price
                    else {$buy_price = $line[8];}                
      $buy_fee    = $line[13];                                   // Buy: fee
      $sell_fee   = ($quantity * $price) * ($fee / 100);         // Sell fee 
      $fees       = ($buy_fee * $price) + $sell_fee;             // Total fees (BUY + SELL fees)
      $markups    = ($quantity * $price) * ($markup / 100);      // Total markup based on total BUY funds 
      $sell_value = ($quantity * $price);                        // Total SELL value
      $sell_price = $sell_value / $quantity;                     // Sell price
      $profit     = $sell_value - $buy_value - $fees;            // Profit
      
      echo "<b>Checking SELL Order " . $counter . "</b><br /><br />";
      echo "<i>BUY Side</i><br />";
      echo "Quantity: " . $quantity . " " . $set_coin['baseAsset'] . "<br />";
      echo "Price   : " . $buy_price . " " . $set_coin['quoteAsset'] . "<br />";
      echo "Value   : " . $buy_value . " " . $set_coin['quoteAsset'] . "<br />";
      echo "Fee     : " . $buy_fee . " " . $set_coin['baseAsset'] . "<br /><br />";
      
      echo "<i>SELL Side</i><br />";
      echo "Quantity: " . $quantity . " " . $set_coin['baseAsset'] . "<br />";
      echo "Price   : " . $sell_price . " " . $set_coin['quoteAsset'] . "<br />";
      echo "Value   : " . $sell_value . " " . $set_coin['quoteAsset'] . "<br />";
      echo "Fee     : " . $sell_fee . " " . $set_coin['quoteAsset'] . "<br /><br />";

      echo "<i>Results</i><br />";
      echo "Fees    : " . $fees . " " . $set_coin['quoteAsset'] . "<br />";
      echo "Markup  : " . $markups . " " . $set_coin['quoteAsset'] . "<br />";
      echo "Profit  : " . $profit  . " " . $set_coin['quoteAsset'] . "<br /><br />";

      // We can SELL with profit!!
      if (($profit) >= 0) {

        // Sell Order
        echo "<b>Profitable SELL Order</b><br />";

        // Do some calculations for now and later
        $total_orders   = $total_orders + 1;
        $total_profit   = $total_profit + $profit;
        $total_quantity = $total_quantity + $quantity;
        $total_value    = $total_value + $sell_value;
        $total_fees     = $total_fees + ($fees / $price);
        
        // SELL SELL SELL!
        $sellOrder = postOrder($pair, 'Sell', 'Market', $quantity, $price, 0);
        logCommand($order, "exchange");
                
        // Report BUY Order
        showOrder($sellOrder);
        
        // Log to history
        echo "<i>Profit, we can sell!</i><br /><br />";
        $history .= implode(",", $sellOrder) . "\n";

        // Add revenue to log file
        logRevenue($sellOrder);

      } else {
        
        // Log to trades
        echo "<i>Insufficient profit, we can not sell!</i><br />";
        $trades .= implode(",", $line) . "\n";
      }
      echo "<hr />";
    }
  }
  fclose($handle);

  // Create new trades log
  echo "<i>Creating " . $log_trades . " file...</i><br />";
  file_put_contents($log_trades, $trades);

  // Log history
  echo "<i>Updating " . $log_history . " file...</i><br /><br />";
  logCommand($history, "history");
}


/*** CHECK action ***/
if ($action == "CHECK") {
  
  // Check if there are sold LIMIT orders
  include("limit_filled.php");
}


/** Report results and end program **/
echo "<b>Results</b><br />";
if ($total_orders > 0) {
  
  // Report
  echo "Total Orders  : " . $total_orders . "<br />";
  echo "Total Quantity: " . $total_quantity . " " . $set_coin['baseAsset'] . "<br />";
  echo "Order Price   : " . ($total_value / $total_quantity) . " " . $set_coin['quoteAsset'] . "<br />";
  echo "Total Value   : " . $total_value . " " . $set_coin['quoteAsset'] . "<br />";
  echo "Total Fees    : " . ($total_fees * $price) . " " . $set_coin['quoteAsset'] . "<br />";
  echo "Average Price : " . (($total_value + $total_fees) / $total_quantity)  . " " . $set_coin['quoteAsset'] . "<br />";
  if ($action == "SELL") {echo "Total Profit  : " . $total_profit . " " . $set_coin['quoteAsset'] . "<br />";}
  echo "<br />";
} else {
  echo "<i>No orders executed...</i><br />";
} 

// Log runtime
echo "<i>Updating " . $log_runs . " file...</i><br />";
$message = round(microtime(true) * 1000) . "," . $id . "," . $pair . ",MARKET," . $action . "," . $total_quantity . "," . $total_value;
logCommand($message, "run");

// End program
echo "<i>GoldStar ended succesfully, status OK!</i><br />";
echo "<hr />";
echo "</pre>

</body>
</html>";

?>