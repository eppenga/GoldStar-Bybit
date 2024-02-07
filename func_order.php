<?php

/**
 * @author Ebo Eppenga
 * @copyright 2023
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * func_order.php
 * Trade functions required for GoldStar to run properly.
 * 
 **/

 
/** Get price from a pair **/
function getPrice($symbol) {

  // Declare some variables as global
  global $api, $id;

  // Get price for pair
  try {
    $result = $api->market()->getTickers([
      'category' => 'spot',
      'symbol'   => $symbol
    ]);
  } catch (\Exception $e) {
    $message = $e->getMessage();
    doError($message, $id, true);
  }
  logAPI($result, "getPrice");

  // Get actual price
  $price = $result["result"]["list"][0]["lastPrice"];

  // Check if we have a price
  if ($price == 0) {
    $message = "Error: Price not found for pair " . $symbol;
    doError($message, $id, true);
  }

  return $price;
}

 /** Extract data from exchange order **/
function getOrder($orderId) {

  // Declare some variables as global
  global $api, $set_coin, $id, $debug;

  // Get Order data for order ID
  try {
    $order = $api->order()->getHistory([
      'category' => 'spot',
      'orderId' => $orderId
    ]);
  } catch (\Exception $e) {
    $message = $e->getMessage();
    doError($message, $id, true);
  }
  logAPI($order, "getOrder");

  if ($debug) {
    echo "<b>Result of getOrder</b><br />";
    print_r($order);
  }

  $transaction = [
    'createdTime'   => $order['result']['list'][0]['createdTime'],   // Order created timestamp (ms)
    'updatedTime'   => $order['result']['list'][0]['updatedTime'],   // Order updated timestamp (ms)

    'orderId'       => $order['result']['list'][0]['orderId'],       // Order ID
    'orderLinkId'   => '0',                                          // Order LinkId
    'symbol'        => $order['result']['list'][0]['symbol'],        // Symbol name
    'side'          => $order['result']['list'][0]['side'],          // Side. Buy,Sell
    'orderType'     => $order['result']['list'][0]['orderType'],     // Order type. Market,Limit.
    'orderStatus'   => $order['result']['list'][0]['orderStatus'],   // Order status

    'price'         => $order['result']['list'][0]['price'],         // Order price
    'avgPrice'      => $order['result']['list'][0]['avgPrice'],      // Average filled price. If unfilled, it is "0"
    'qty'           => $order['result']['list'][0]['qty'],           // Order quantity
    'cumExecQty'    => $order['result']['list'][0]['cumExecQty'],    // Cumulative executed order quantity 
    'cumExecValue'  => $order['result']['list'][0]['cumExecValue'],  // Cumulative executed order value (executed total buy / sell, should equal cumExecQty * avgPrice ).
    'cumExecFee'    => $order['result']['list'][0]['cumExecFee']     // Cumulative executed trading fee. 
  ];
  
  // Set price to order price when market order, personally I find this an error of the exchange *** CHECK ***
  if ($transaction['orderType'] == 'Market') {
    $transaction['price'] = $set_coin['price'];                      // Order price
  }

  return $transaction;
}

/** Posts an order to the exchange **/
function postOrder($pair, $side, $type, $quantity, $price, $link) {

  // Declare $api as global
  global $api, $set_coin, $id, $debug;
  
  // Fix decimals in order
  if ($side == "Buy") {
    $quantity = roundStep($quantity, $set_coin['quotePrecision']);
  } else {
    $quantity = roundStep($quantity, $set_coin['basePrecision']);
  }
  $price = roundStep($price, $set_coin['tickSize']);

  // Post order
  try {
    $result = $api->order()->postCreate([
      'category'  =>'spot',
      'symbol'    => $pair,
      'side'      => $side,
      'orderType' => $type,
      'qty'       => strval($quantity),
      'price'     => strval($price)
    ]);
  } catch (\Exception $e) {
    $message = $e->getMessage();
    doError($message, $id, true);
  }
  logAPI($result, "postOrder");
  
  if ($debug) {
    echo "<b>Result of postOrder</b><br />";
    print_r($result);
  }
  
  // Check if we have valid order ID
  $orderId = $result['result']['orderId'];
  if (empty($orderId)) {
    doError("Error: postOrder did not work out.", $id, true);
  }
  
  // Get order data and give it 0.5 seconds when it's a market order to fill
  if ($side == "Market") {usleep(500000);}
  $order = getOrder($orderId);
  
  // Link matching BUY order to LIMIT order
  $order['orderLinkId'] = $link;

  return $order;  
}

/** Log revenue or posted orders **/
function logRevenue($order) {

  // Declare $price and $id as global
  global $price, $id;

  // Only on filled orders
  if (strpos($order['orderStatus'], "Filled") !== false) {
    
    // Split fee and order
    $qty_fee   = $order['cumExecFee'] * -1;
    $qty_order = $order['cumExecValue'];

    // Convert to quote asset
    if ($order['side'] == "Buy") {
      $qty_order = $qty_order * -1;
      $qty_fee   = $qty_fee * $price;
    }

    //revenue : createdTime,updatedTime,orderId,orderLinkId,symbol,side,orderType,orderStatus,type,qty | qty is always in quote asset, type is Order or Feee 
    $log_order = $order['createdTime'] . "," . $order['updatedTime'] . "," . $order['orderId'] . "," . $order['orderLinkId'] . "," . $order['symbol'] . "," . $order['side'] . "," . $order['orderType'] . "," . $order['orderStatus'] . ",Order," . $qty_order;
    $log_fee   = $order['createdTime'] . "," . $order['updatedTime'] . "," . $order['orderId'] . "," . $order['orderLinkId'] . "," . $order['symbol'] . "," . $order['side'] . "," . $order['orderType'] . "," . $order['orderStatus'] . ",Fee," . $qty_fee;

    // Log to files
    logCommand($log_order, "profit");
    logCommand($log_fee, "profit");
  } 
  
  return;
}

/** Show order status **/
function showOrder($order) {

  // Declare $set_coin as global
  global $set_coin;

  // What data to display
  $showFilled = false;
  if (strpos($order['orderStatus'], "Filled") !== false) {
    $showFilled = true;
  }

  /*
    0. 'createdTime'    // Order created timestamp (ms)
    1. 'updatedTime'    // Order updated timestamp (ms)

    2. 'orderId'        // Order ID
    3. 'orderLinkId'    // LIMIT Order linked to BUY Order
    4. 'symbol'         // Symbol name
    5. 'side'           // Side Buy, Sell
    6. 'orderType'      // Order type. Market, Limit.
    7. 'orderStatus'    // Order status. Filled, PatrtiallyFilled, New.

    8. 'price'          // Order price
    9. 'avgPrice'       // Average filled price. If unfilled, it is "0"

    10. 'qty'           // Order quantity
    11. 'cumExecQty'    // Cumulative executed order quantity 

    12. 'cumExecValue'  // Cumulative executed order value (executed total buy / sell, should equal cumExecQty * avgPrice ).
    13. 'cumExecFee'    // Cumulative executed trading fee. 
  */

  echo "Created Time       : " . $order['createdTime'] . "<br />";
  echo "Updated Time       : " . $order['updatedTime'] . "<br /><br />";
  
  echo "Order ID           : " . $order['orderId'] . "<br />";
  if ($order['orderLinkId'] <> 0) {
    echo "Order Linked ID    : " . $order['orderLinkId'] . "<br />";    
  }
  
  echo "Symbol             : " . $order['symbol'] . "<br />";
  echo "Side               : " . $order['side'] . "<br />";
  echo "Order Type         : " . $order['orderType'] . "<br />";
  echo "Order Status       : " . $order['orderStatus'] . "<br /><br >";
  
  echo "Price              : " . $order['price'] . " " . $set_coin['quoteAsset'] . "<br />";
  if ($showFilled) {
    echo "Avg. Price Filled  : " . $order['avgPrice'] . " " . $set_coin['quoteAsset'] . "<br />";
  }

  if ($order['side'] == 'Buy' && $order['orderType'] == 'Market') {
    echo "Quantity           : " . $order['qty'] . " " . $set_coin['quoteAsset'] . "<br />";
  } else {
    echo "Quantity           : " . $order['qty'] . " " . $set_coin['baseAsset'] . "<br />";
    echo "Value              : " . ($order['price'] * $order['qty']) . " " . $set_coin['quoteAsset'] . "<br />";
  }

  if ($showFilled) {
    echo "Cum. Exec. Quantity: " . $order['cumExecQty'] . " " . $set_coin['baseAsset'] . "<br />";
    echo "Cum. Exec. Value   : " . $order['cumExecValue'] . " " . $set_coin['quoteAsset'] . "<br />";
    if ($order['side'] == 'Buy') {
      echo "Cum. Exec. Fee     : " . $order['cumExecFee'] . " " . $set_coin['baseAsset'] . "<br />";
    } else {
      echo "Cum. Exec. Fee     : " . $order['cumExecFee'] . " " . $set_coin['quoteAsset'] . "<br />";
    }

  }
  echo "<br />";
}

/** Round value to the nearest stepSize **/
function roundStep($value, $stepSize = 0.1) {

  $factor  = 1 / $stepSize;
  $value = floor($value * $factor) / $factor;
    
  return $value;
}

 ?>