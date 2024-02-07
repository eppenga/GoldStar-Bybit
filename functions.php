<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * functions.php
 * All functions required for GoldStar to run properly.
 * 
 **/

/** Load all functions **/
include "func_logerror.php";      // Log errors
include "func_order.php";         // Post orders
include "func_setcoin.php";       // Prepare data
include "func_tradeview.php";     // Get Trading advice
include "func_indicators.php";    // Calculate indicators
include "func_advice.php";    // Get advice based on indicators
include "func_klines.php";    // Get KLines used in indicator advice

?>