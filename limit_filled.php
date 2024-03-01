<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * limit_filled.php
 * Checks if there are limit orders that are already sold.
 * 
 * Dependancies:
 * $action        - if equals CHECK than all Binance orders are checked for $pair
 * $pair          - what pair to check
 * $log_trades    - containing ID of log file
 * 
 **/


// Determine a random so it only checks all exchange trades every 10th time
$go_check    = false;
$limit_check = false;
if (rand(0, $limit_check) == 1) {$limit_check = true;}
if ($action == "CHECK") {
  $limit_check = true;
  echo "<i>Checking for open LIMIT orders...</i><br><hr>";
}

// Count how many orders we are going to check maximum
$amountOrders = 0;
$handle       = fopen($log_trades, "r");
while (($line = fgetcsv($handle)) !== false) {
  $amountOrders = $amountOrders + 1;
}

// Loop through trades
$unique_ids[] = [];
$handle       = fopen($log_trades, "r");
while (($line = fgetcsv($handle)) !== false) {

  // Compensate if spread has been set to 0
  if ($spread == 0) {$c_spread = 1;} else {$c_spread = $spread;}

  $go_check  = $limit_check;
  $buy_id    = $line[2];      // Get the buy order ID from the buy order
  $limit_id  = $line[3];      // Get the linked LIMIT order ID from the buy order, so we can match
  if ($line[9] <> 0) {$buy_price = $line[9];} else {$buy_price = $line[8];}
  $price_min = $price * (1 - ($c_spread * 2) / 100);
  $price_max = $price * (1 + ($c_spread * 2) / 100);
  
  // Only check exchange trades in range to save on API calls
  if (!$go_check) {
    if (($buy_price >= $price_min) && ($buy_price <= $price_max)) {
      $go_check = true;
    }
  }

  // We can check against exchange data
  if ($go_check) {
    
    // Get order data
    $checkOrder = getOrder($limit_id);
    //echo "Checking order ID: " . $limit_id . " with status " . $checkOrder['orderStatus'] . "\n";

    // Add a pause of 0.2 seconds to prevent rate limit API errors, limit is 0.05 seconds per request
    if (($amountOrders > 10) && ($limit_check == true)) {
      usleep(200000);
    }

    // Found a FILLED limit order, let's process it as a sales order
    if (($checkOrder['orderStatus'] == "Filled") && ($checkOrder['orderType'] == "Limit")) {
      
      // Log exchange FILLED order
      logCommand($checkOrder, "exchange");
      
      // Calculate the profit
      $profit = $checkOrder['cumExecValue'] - $line[12] - $line[13] - $checkOrder['cumExecFee'];
      
      // Report
      echo "<i>LIMIT Order " . $checkOrder['orderId'] . " was filled!</i><br /><br />";

      echo "<b>Matching BUY trade</b><br />";
      echo "Date    : " . $line[0] . "<br />";
      echo "Order ID: " . $line[2] . "<br />";
      echo "Quantity: " . $line[11] . "<br />";
      echo "Price   : " . $line[9] . "<br />";
      echo "Value   : " . $line[12] . "<br />";
      echo "Fee     : " . $line[13] . "<br /><br />";

      echo "<b>Matching LIMIT Trade</b><br />";
      echo "Date    : " . $checkOrder['createdTime'] . "<br />";
      echo "Order ID: " . $checkOrder['orderId'] . "<br />";
      echo "Quantity: " . $checkOrder['cumExecQty'] . "<br />";
      echo "Price   : " . $checkOrder['avgPrice'] . "<br />";
      echo "Value   : " . $checkOrder['cumExecValue'] . "<br />";
      echo "Fee     : " . $checkOrder['cumExecFee'] . "<br /><br />";
      
      echo "<b>Result</b><br />";
      echo "Profit  : " . $profit . "<br /><br />";
  
      // Add linked order to LIMIT SELL order
      $checkOrder['orderLinkId'] = $line[2]; 

      // Add LIMIT SELL trade to history, runtime and profit log
      $message = round(microtime(true) * 1000) . "," . $id . "," . $pair . ",LIMIT,SELL," . $checkOrder['cumExecQty'] . "," . $checkOrder['cumExecValue'];
      $history = implode(",", $checkOrder);
      echo "<i>Updating " . $log_history . " file for matching order...</i><br />";
      logCommand($history, "history");
      echo "<i>Updating " . $log_runs . " file for matching order...</i><br />";
      logCommand($message, "run");
      echo "<hr /><br />";

      // Add to revenue
      logRevenue($checkOrder);

      // Add to array of to be removed unique IDs
      $unique_ids[] = $buy_id;

    }
  }
}
fclose($handle);

// Remove BUY order from $log_trades
if (!empty($unique_ids)) {
  $trades = "";
  $handle = fopen($log_trades, "r");
  while (($line = fgetcsv($handle)) !== false) {
  
    // Skip BUY order with ID
    $uid_skip = false;
    foreach ($unique_ids as &$unique_id) {
      if ($line[2] == $unique_id) {
        $uid_skip = true;
      }
    }
    if (!$uid_skip) {
      $trades .= implode(",", $line) . "\n";    
    }
  }
  fclose($handle);
  file_put_contents($log_trades, $trades);
}

?>
