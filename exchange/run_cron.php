<?php
/*
 * This script is designed to update exchange currencies and rates by calling 
 * functions from 'exchange_functions.php'. It uses an access key for authentication 
 * and handles errors gracefully. The output is displayed in the terminal or browser.

 * To schedule this script in cPanel as a cron job:
 * 1. Log in to your cPanel account.
 * 2. Navigate to the "Cron Jobs" section (usually under the "Advanced" category).
 * 3. In the "Add New Cron Job" section:
 *    - Set the desired frequency for the cron job (e.g., hourly, daily, etc.).
 *    - In the "Command" field, enter the following:
 *      php /path/to/your/script/run_cron.php
 *      Replace '/path/to/your/script/' with the actual path to this file on your server.
 * 4. Click "Add New Cron Job" to save the configuration.

 * Note: Ensure that PHP is installed and accessible via the command line on your server.
 *       Also, verify that the script has the necessary permissions to execute.
 */

require_once '../dbutils.php';
require_once './exchange_functions.php';

try {
    $access_key = '98d16f4753dc30947ea68aabfeb4a1d8';

    $count = updateExchangeCurrencies($access_key);
    echo "✅ Loaded $count currencies\n";

    $date = updateExchangeRates($access_key);
    echo "✅ Loaded exchange rates for $date\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}