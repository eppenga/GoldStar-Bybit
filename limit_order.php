<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * limit_order.php
 * Adds a LIMIT order to a BUY order adding fees and markup.
 * 
 **/


// Do some calculations
$buy_fee    = $total_fees;
$sell_fee   = $total_quantity * ($fee / 100);
$fees       = $buy_fee + $sell_fee;
$markups    = $total_value * ($markup / 100);
if ($set_coin['availWithdraw'] >= $buy_fee) {
  $sell_qty  = $total_quantity;
} else {
  $sell_qty  = $total_quantity - $buy_fee;
}
$sell_value = $total_value + ($fees * $price) + $markups;
$sell_price = $sell_value / $total_quantity;

// Debug
if ($debug) {
  echo "<b>LIMIT Order Calculation</b><br />";
  echo "Markup             : " . $markups . " " . $set_coin['quoteAsset'] . "<br />";
  echo "BUY Fee            : " . $buy_fee . " " . $set_coin['baseAsset'] . " (" . $fee . "%)<br />";
  echo "SELL Fee (Expected): " . $sell_fee . " " . $set_coin['baseAsset'] . " (" . $fee . "%)<br />";
  echo "Total Expected Fees: " . $fees . " " . $set_coin['baseAsset'] . " (" . $fee . "%)<br />";
  echo "Quantity           : " . $total_quantity . " " . $set_coin['baseAsset'] . "<br />";
  echo "Sellable Quantity  : " . $sell_qty . " " . $set_coin['baseAsset'] . "<br />";
  echo "BUY Price          : " . $set_coin['price'] . " " . $set_coin['quoteAsset'] . "<br />";
  echo "BUY Value          : " . $total_value . " " . $set_coin['quoteAsset'] . "<br />";
  echo "SELL Price         : " . $sell_price . " " . $set_coin['quoteAsset'] . "<br />";
  echo "SELL Value         : " . $sell_value . " " . $set_coin['quoteAsset'] . "<br /><br />";          
}

// Place the LIMIT order and link the BUY order to the LIMIT order
$limitOrder = postOrder($pair, 'Sell', 'Limit', $sell_qty, $sell_price, $buyOrder['orderId']);
logCommand($limitOrder, "exchange");

// Link the LIMIT order to the BUY order
$buyOrder['orderLinkId'] = $limitOrder['orderId'];

// Report
echo "<b>LIMIT Order</b><br />";
showOrder($limitOrder);

?>