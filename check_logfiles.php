<?php

/**
 * @author Ebo Eppenga
 * @copyright 2023
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * check_logfiles.php
 * Checks if all logfiles exist and if not create empty logs.
 * 
 **/


 // Check if we have a bot ID and create log files
 if (isset($_GET["id"]) || (!empty($botid))) {
  if (empty($botid)) {$id = $_GET["id"];} else {$id = $botid;}
  $log_trades     = "data/" . $id . "_log_trades.csv";      // Trades
  $log_history    = "data/" . $id . "_log_history.csv";     // History
  $log_revenue    = "data/" . $id . "_log_revenue.csv";     // Profit / Losses
  $log_runs       = "data/" . $id . "_log_runs.csv";        // Executing log
  $log_exchange   = "data/" . $id . "_log_exchange.txt";    // Responses from exchange
  $log_settings   = "data/" . $id . "_log_settings.csv";    // Exchange settings
  $log_errors     = "data/" . $id . "_log_errors.csv";      // Errors
  $log_api        = "data/" . $id . "_log_api.csv";         // Responses from API
} else {
  if (!$trailer) {
    $message = round(microtime(true) * 1000) . ",Error: ID not set\n";
    echo $message;
    if (!file_exists("data/")) {mkdir("data/");}
    file_put_contents("data/log_errors.csv", $message, FILE_APPEND | LOCK_EX);
    exit();
  }
}

/* Set headers of log files
settings: Symbol,status,baseCoin,quoteCoin,basePrecision,quotePrecision,minOrderQty,maxOrderQty,minOrderAmt,maxOrderAmt,tickSize
orders  : createdTime,updatedTime,orderId,orderLinkId,symbol,side,orderType,orderStatus,price,avgPrice,qty,cumExecQty,cumExecValue,cumExecFee 
trades  : createdTime,updatedTime,orderId,orderLinkId,symbol,side,orderType,orderStatus,price,avgPrice,qty,cumExecQty,cumExecValue,cumExecFee
revenue : createdTime,updatedTime,orderId,orderLinkId,symbol,side,orderType,orderStatus,type,qty | qty is always in quote asset, type is Order or Feee
runs    : createdTime,botId,Symbol,orderType,Side,cumExecQty,cumExecValue
api     : createdTime,botId,retCode,retMsg,function
errors  : createdTime,botId,message
*/

// Check if all files exist and if not create empty files, log settings file is created in check_base.php
if (!file_exists("data/"))       {mkdir("data/");}
if (!file_exists($log_trades))   {touch($log_trades);}
if (!file_exists($log_history))  {touch($log_history);}
if (!file_exists($log_history))  {touch($log_revenue);}
if (!file_exists($log_runs))     {touch($log_runs);}
if (!file_exists($log_exchange)) {touch($log_exchange);}
if (!file_exists($log_api))      {touch($log_api);}
if (!file_exists($log_errors))   {touch($log_errors);}

?>