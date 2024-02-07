<?php

/**
 * @author Ebo Eppenga
 * @copyright 2023
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Bybit API from zhouaini528.

 *** User settings ***
 * $fee            - Exchange fee in percentages (BUY and SELL are the same)
 * $markup         - Minimum profit per trade, can be overriden by URL
 * $spread         - Minimum spread between historical BUY orders,
 *                   setting $spread to zero disables this function,
 *                   can be overriden by URL
 * $multiplier     - Multiplies the order value by this amount.
 * $compounding    - Start amount of Binance account in quote currency,
 *                   0 disables this function (adviced if you start first)

 *** TradingView ***
 * $tv_advice      - Use TradingView advice on single BUY orders
 * $tv_recomMinMax - Bandwith to use for TradingView recommendation
 *                   STRONG_SELL: -1...-0.5, SELL: -0.5...-0.1
 *                   NEUTRAL: -0.1...0.1
 *                   BUY: 0.1...0.5, STRONG_BUY: 0.5...1
 * $tv_periods     - Periods for TradingView recommendation confirmation
 *                   1m: 1, 5m: 5, 15m: 15, 30m: 30, 1h: 60, 2h: 120, 
 *                   4h: 240, 1W: 1W, 1M: 1M, 1d: leave emtpy

 *** Indicators ***
 * Same as TradingView but now GoldStar uses its own internal technical
 * indicators to calculate the advice. All parameters work the same.
 * This methoud is preferred over TradingView because of its speed as
 * TradingView data can lag behind up to 15 minutes.

 *** Exchange keys ***
 * $exchange_key    - Exchange API key
 * $exchange_secret - Exchange API secret
 * $url_key        - Add to your webhook to prevent unwanted execution
 
 **/


// User settings
$fee             = 0.10;
$markup          = 0.75;
$spread          = 0.75;
$multiplier      = 1.00;
$compounding     = 0;

// TradingView
$tv_advice       = false;
$tv_recomMin     = 0.25;
$tv_recomMax     = 1.00;
$tv_periods      = array(15, 60);

// Indicators
$ind_advice       = true;
$ind_recomMin     = 0.25;
$ind_recomMax     = 1.00;
$ind_periods      = array(1, 5, 15);

// Exchange keys (ALWAYS KEEP THESE SECRET!)
$exchange_key    = "12345";
$exchange_secret = "12345";

// Security key (add to your webhook URLs to prevent unwanted execution!)
$url_key         = "";

// Debug mode
$debug           = false;

?>