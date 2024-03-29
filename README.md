# GoldStar Crypto Trading Bot for Bybit
**GoldStar Crypto Trading Bot trades based on signals from for example TradeView or any other platform. BUY and SELL orders are triggered via webhooks. It is possible to run as many bots as you like as long as the 'id' differs per bot. Data is stored in CSV files in the 'data/' folder, containing trades, orders, runtime and other logs for further analysis.**

GoldStar automatically determines the smallest possible order value and uses that as BUY orders. This amount is based on [Bybit minimum order value](https://www.bybit.com/en/announcement-info/spot-trading-rules/) and can be multiplied with the 'mult' parameter. By default GoldStar is setup so it can't sell at a loss (unless you set profit to negative levels or due to any other unforeseen circumstance).

The application relies on [PHP Bybit API from zhouaini528](https://github.com/zhouaini528/bybit-php/) to place the actual orders. You need to install that application first and put the GoldStar files in the same folder. Please remember to set a key to prevent others from calling your BUY and SELL URLs because they are exposed to the outside world! Preferably also using an https connection on your server.

**How to install**

1) Install 'PHP Bybit API from zhouaini528', please see: https://github.com/zhouaini528/bybit-php/
2) Install 'GoldStar Trading Bot' in the same folder (this application)
3) Copy config.example.php to config.php and modify
4) Optionally download Lynx to use as Gridbot (please see below)

**Using GoldStar as a signalbot**

Please make sure the BUY and SELL URL both share the same ID, else the application is unable to match the orders. The parameters markup, spread, trade and key are optional. They take their defaults either from the configuration file or have predefined values. It is recommended to use a key and an https connection! Please find below some real world setups on how to run GoldStar.

Simple example trading the pair MATICBUSD:

BUY:
`http://foo.com/path/goldstar.php?id=a1&action=BUY&pair=MATICBUSD`

The minimum order value of MATIC is bought.

SELL:
`http://foo.com/path/goldstar.php?id=a1&action=SELL&pair=MATICBUSD`

All MATIC that can be sold at a profit will be sold. This will check all open orders.

Another example running GoldStar preferably via an SSL (https) connections including manual spread and markup (profit) parameters. In a normal situation you would define the spread and markup in the configuration file. This shows you can override those parameters through the URL querystring.

BUY:
`https://foo.com/path/goldstar.php?id=a3&action=BUY&pair=MATICBUSD&spread=0.5&key=12345`

SELL:
`https://foo.com/path/goldstar.php?id=a3&action=SELL&pair=MATICBUSD&markup=0.7&key=12345`

**Using GoldStar as a gridbot**

GoldStar can also be used as a gridbot. In that case it will only execute BUY MARKET orders on Bybit and schedule LIMIT SELL orders with a predefined profit percentage. You will need some external tooling to call GoldStar every so many seconds or minutes. Usually a timeschedule of every minute is more than enough, an example batch file `gridbot.bat` is provider for educational purposes. A normal CURL, or if you would like to monitor the output the command line browser Lynx suffices, please see: https://lynx.invisible-island.net/

If you execute the example below every minute you will deploy a grid bot trading on SYSLIMIT spreading the BUY orders 0.9% between each other and setting the profit margin to 0.9% by using a LIMIT SELL order.

BUY:
`http://foo.com/path/goldstar.php?id=syslimit&pair=SYSBUSD&spread=0.9&markup=0.9&action=BUY&key=12345&limit=true`

![Running GoldStar as a gridbot](http://www.eppenga.com/goldstar/binance-onebusd-Jan-02-2022-13-51-0.png)

**Webhooks**

Please use the URLs above in TradingView (or any other platform) and set them up as webhooks. If you do not know what webhooks are, please forget about this application :) More information on these webhooks can be found here: https://www.google.com/search?q=tradingview+webhook

**Signals**

You choose your own signals. Based on that the bot will either BUY or SELL. My personal favorite is "Market Liberator" and I use the Short and Long signals on a one minute timescale. They have an awesome Discord Channel here: https://discord.me/marketliberator

**TradingView**

Additionally you can use TradingView to validate a BUY order, if the order is within the parameters of TradingView the BUY order will take place or else if will be cancelled. This can work efficient if you use GoldStar as a gridbot. You can define the minimum and maximum TradingView recommendation as a number. As reference strong sell (-1 to-0.5), sell (-0.5 to -0.1), neutral (-0.1 to 0.1), buy (0.1 to 0.5) and strong buy (0.5 to 1). You can define as many standard periods to check on as you prefer. TradingView periods are 1m: 1, 5m: 5, 15m: 15, 30m: 30, 1h: 60, 2h: 120, 4h: 240, 1W: 1W, 1M: 1M, 1d: none.

In the example below it uses TradingView verification to prevent BUY orders when the price falls. TradingView recommendation must be between 0.25 and 1.0 and it will check on the timeframes of 1, 15 and 60 minutes.

`http://foo.com/path/goldstar.php?id=a4&pair=ONEBUSD&action=BUY&key=12345&limit=true&trade=live&tv=true&tvmin=0.25&tvmax=1.0&tvpers=1,15,60`

![Using TradingView verification to prevent BUY orders when price falls](http://www.eppenga.com/goldstar/tradingview_protection.png)

**Querystring parameters**

- id       - id of the bot, multiple instances can be run (required). Please make sure the BUY and SELL URLs share the same id to be able to match the trades.
- action   - BUY or SELL, for SELL no quantity is required.
- pair     - Crypto pair to be used (required).
- key      - Add a unique key to URL to prevent unwanted execution (optional).
- mult     - Multiply the minimum order value by this amount (optional).
- spread   - Minimum spread between historical BUY orders, setting to zero disables. Defaults to the setting in config.php (optional).
- markup   - Minimum profit. Defaults to setting in config.php (optional).
- limit    - Place a limit (SELL) order on top of every (BUY) order, set to true or false
- tv       - Use TradingView verification on BUY orders, set to true or false
- tvmin    - Minimum TradingView verification, use a number to reference
- tvmax    - Maximum TradingView verification, use a number to reference
- tvpers   - TradingView periods to use as verification

**Logfiles and analyses**

All logs reside in the 'data/' folder and are seperated per Bot ID (usually you run a bot per pair so per pair). Also there is some special functionality that allows you to retreive a combined log of all bots. 

- *bot_id*_log_exchange.txt - Log of all Bybit responses (verbose logging without structure)
- *bot_id*_log_history.csv  - History of trades
- *bot_id*_log_trades.csv   - All active trades (also known as bags)
- *bot_id*_log_revenue.csv  - Revenue of trades in quote asset
- *bot_id*_log_runs.csv     - Runtime log of executions
- *bot_id*_log_errors.csv   - Log of all errors
- *bot_id*_log_settings.csv	- Exchange settings

You can also combine the log files of all Bot IDs by using the log combiner:
`http://foo.com/path/log_combine.php?files=history|trades|errors|revenue` displays in the browser and creates files below. Displaying directly in the browser allows it to be read directly by Google Sheets for examples and files can be used for other analyses.

- log_history.csv     - History of all trades for all coins
- log_trades.csv      - All active trades for all coins
- log_revenue.csv     - All revenue lines for all coins in quote asset
- log_errors.csv      - Log of all errors for all coins

**Disclaimer**

I give no warranty and accepts no responsibility or liability for the accuracy or the completeness of the information and materials contained in this project. Under no circumstances will I be held responsible or liable in any way for any claims, damages, losses, expenses, costs or liabilities whatsoever (including, without limitation, any direct or indirect damages for loss of profits, business interruption or loss of information) resulting from or arising directly or indirectly from your use of or inability to use this code or any code linked to it, or from your reliance on the information and material on this code, even if I have been advised of the possibility of such damages in advance.

So use it at your own risk!
