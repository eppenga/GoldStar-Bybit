<?php

/*
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * revenue.php
 * Calculates revenue of trades and outputs a CSV file
 * Run log_combine.php?files=revenue first
 **/


// Set error reporting
error_reporting(E_ALL & ~E_NOTICE);

$logfile = "data/log_revenue.csv";

// Loop through revenue file for the first time and make a list of BUY orders
$handle = fopen($logfile, "r");
while (($line = fgetcsv($handle)) !== false) {

  // Get some basic variables
  $orderId     = $line[2];
  $side        = $line[5];
  $orderStatus = $line[7];

  // Only create a list with BUY orders
  if (($side == "Buy") && (strpos($orderStatus, "Filled") !== false)) {

    if (empty($revenue[$orderId])) {
      $revenue[$orderId] = [
        'createdTime' => $line[0],
        'updatedTime' => $line[1],
        'orderId'     => $line[2],
        'orderLinkId' => $line[3],
        'symbol'      => $line[4],
        'side'        => $line[5],
        'orderType'   => $line[6],
        'orderStatus' => $line[7],
        'revStatus'   => 'Open',
        'qty'         => $line[9]
      ];  
    } else {
      
      // Sum up qty
      $revenue[$orderId]['qty'] = $revenue[$orderId]['qty'] + $line[9];
      
      // Determine oldest createTime
      if ($line[0] < $revenue[$orderId]['createdTime']) {
        $revenue[$orderId]['createdTime'] = $line[0];
      }
  
      // Determine newest updatedTime
      if ($line[1] > $revenue[$orderId]['updatedTime']) {
        $revenue[$orderId]['updatedTime'] = $line[1];
      }
    }  
  }
}
fclose($handle);

//print_r($revenue);
//exit();

// Loop through revenue file for the second time and determine if orders are closed
$handle = fopen($logfile, "r");
while (($line = fgetcsv($handle)) !== false) {

  // Get some basic variables
  $orderId     = $line[2];
  $orderLinkId = $line[3];
  $side        = $line[5];
  $orderStatus = $line[7];

  // Match the list with SELL orders
  if (($side == "Sell") && (strpos($orderStatus, "Filled") !== false)) {
  
    // Check if we can match a BUY order to a SELL order
    if (!empty($revenue[$orderLinkId])) {

      // Sum up qty
      $revenue[$orderLinkId]['qty'] = $revenue[$orderLinkId]['qty'] + $line[9];
      
      // Determine oldest createTime
      if ($line[0] < $revenue[$orderLinkId]['createdTime']) {
        $revenue[$orderLinkId]['createdTime'] = $line[0];
      }
  
      // Determine newest updatedTime
      if ($line[1] > $revenue[$orderLinkId]['updatedTime']) {
        $revenue[$orderLinkId]['updatedTime'] = $line[1];
      }
      
      // Set order to Closed
      $revenue[$orderLinkId]['revStatus'] = 'Closed';

    } else {

      // Orders that only have a SELL without a BUY
      $revenue[$orderId] = [
        'createdTime' => $line[0],
        'updatedTime' => $line[1],
        'orderId'     => $line[2],
        'orderLinkId' => $line[3],
        'symbol'      => $line[4],
        'side'        => $line[5],
        'orderType'   => $line[6],
        'orderStatus' => $line[7],
        'revStatus'   => 'Open',
        'qty'         => $line[9]
      ];  
    }
  }
}  

//print_r($revenue);
//exit();

// Output the array as CSV file
foreach ($revenue as $orderId => $orderDetails) {
  $line = "";
  foreach ($orderDetails as $key => $value) {
    $line .= $value . ",";
  }
  $line = substr($line, 0, -1) . "\n";
  echo $line;
}

?>