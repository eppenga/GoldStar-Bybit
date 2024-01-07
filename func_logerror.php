<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * func_logerror.php
 * Error handling and log functions required for GoldStar to run properly.
 * 
 **/

 
/** Log to files **/
function logCommand($logcommand, $type) {
  
  // Declare some variables as global
  global $log_trades, $log_history, $log_revenue, $log_runs, $log_errors, $log_exchange, $log_api;

  // Remove double end lines and add some remarks for exchange log
  if ($type <> "exchange") {
    // Standard log format
    $message = $logcommand . "\n";
    while (strpos($message, "\n\n") !== false) {$message = str_replace("\n\n", "\n", $message);}
    if ($message == "\n") {$message = "";}
  } else {
    // Exchange log format
    $message  = "==============================================================================================================\n";
    $message .= "Timestamp: " . date("Y-m-d H:i:s") . "\n\n";
    $message .= print_r($logcommand, true);
    $message .= "==============================================================================================================\n\n";    
  }

  // Walk through different log files
  if ($type == "buy") {
    // Store in active trade log
    file_put_contents($log_trades, $message, FILE_APPEND | LOCK_EX);
  } elseif ($type == "history") {
    // Store in historical log
    file_put_contents($log_history, $message, FILE_APPEND | LOCK_EX);    
  } elseif ($type == "profit") {
    // Store in profit log
    file_put_contents($log_revenue, $message, FILE_APPEND | LOCK_EX);    
  } elseif ($type == "run") {
    // Store in runtime log
    file_put_contents($log_runs, $message, FILE_APPEND | LOCK_EX);    
  } elseif ($type == "exchange") {
    // Store in exchange log
    file_put_contents($log_exchange, $message, FILE_APPEND | LOCK_EX);
  } elseif ($type == "api") {
    // Store in api log
    file_put_contents($log_api, $message, FILE_APPEND | LOCK_EX);    
  } elseif ($type == "error") {
    // Store in errors log
    file_put_contents($log_errors, $message, FILE_APPEND | LOCK_EX);
  } else {
    // Store in unknowns also in errors log
    $message = round(microtime(true) * 1000) . ",Error: Unknown";
    file_put_contents($log_errors, $message, FILE_APPEND | LOCK_EX);
  }
}

/** Report error and log it **/
function doError($message, $id, $quit) {
 
  $message = round(microtime(true) * 1000) . "," . $id . "," . $message;
  echo $message;
  logCommand($message, "error");
  
  // Stop program only if required
  if ($quit) {exit();}
 }

/** Log API return message and flip killswitch if necessary **/
function logAPI($order, $command) {

  // Declare some variables as global
  global $id;

  $message = $order['time'] . "," . $id . "," . $order['retCode'] . "," . $order['retMsg'] . "," . $command;
  logCommand($message, "api");

  // Activate kill switch
  if ($order['retCode'] <> 0) {
    $message  = "<br /><b>Abnormal API response!</b>\n";
    $message .= "Bot ID       : " . $id . "\n";
    $message .= "Return Code  : " . $order['retCode'] . "\n";
    $message .= "Message      : " . $order['retMsg'] . "\n";
    $message .= "Command      : " . $command . "\n";
    $message .= "Exchange time: " . $order['time'] . "\n";
    $message .= "Calendar time: " . date("Y-m-d H:i:s") . "\n\n";
    $message .= "GoldStar stopped, fix error first before restarting, check all logs!\n\n";
    echo $message;
    file_put_contents("data/log_exchange_errors.txt", strip_tags($message), FILE_APPEND | LOCK_EX);
    
    // Kill application based on error codes
    if (($order['retCode'] == 10006) || ($order['retCode'] == 10018)) {
      file_put_contents("killswitch.txt", strip_tags($message));
    }

    // Kill application based on text in description
    if (strpos($order['retMsg'], 'Too many') !== false) {
      file_put_contents("killswitch.txt", strip_tags($message));
    }
    
  }
}
 
 ?>
