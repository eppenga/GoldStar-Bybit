<?php

/**
 * @author Ebo Eppenga
 * @copyright 2023
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.
 * 
 * check_variables.php
 * Sets and defines variables.
 * 
 **/

// General variables
$buy_value      = 0;
$buy_price      = 0;
$counter        = 0;
$price          = 0;
$profit         = 0;
$quantity       = 0;
$sell_value     = 0;
$sell_price     = 0;
$fees           = 0;
$markups        = 0;
$repeatrun      = 24 * 60 * 60;
$total_fees     = 0;
$total_orders   = 0;
$total_profit   = 0;
$total_quantity = 0;
$total_value    = 0;
$data           = "";
$history        = "";
$message        = "";
$order          = "";
$pair           = "";
$trades         = "";
$limit          = false;
$limit_check    = 25;       // Chance that all limit orders are checked

 ?>