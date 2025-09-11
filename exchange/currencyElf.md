# CurrencyElf.js Documentation

The `currencyElf.js` file provides functionality for fetching country and currency data and performing currency conversions. This guide explains how to use the file effectively.

## Features

1. Fetch a list of countries and their associated currencies.
2. Perform currency conversions between two currencies.

## Setup

Ensure the `currencyElf.js` file is included in your HTML file:

```html
<script src="currencyElf.js"></script>
```

## Example Usage

### Fetching and Displaying Currencies

To fetch and display the list of currencies in dropdowns, use the `exchange.list_countries()` function:

```javascript
async function loadCurrencies() {
  try {
    const countries = await exchange.list_countries();
    const fromSelect = document.getElementById('fromCurrency');
    const toSelect = document.getElementById('toCurrency');

    countries.forEach(country => {
      const optionFrom = document.createElement('option');
      optionFrom.value = country.currency_code;
      optionFrom.textContent = `${country.currency_code} - ${country.currency_name}`;
      fromSelect.appendChild(optionFrom);

      const optionTo = document.createElement('option');
      optionTo.value = country.currency_code;
      optionTo.textContent = `${country.currency_code} - ${country.currency_name}`;
      toSelect.appendChild(optionTo);
    });
  } catch (error) {
    console.error('Error loading currencies:', error);
  }
}

document.addEventListener('DOMContentLoaded', loadCurrencies);
```

### Performing a Currency Conversion

To perform a currency conversion, use the `exchange.quickConvert()` function:

```javascript
document.getElementById('convertForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fromSelect = document.getElementById('fromCurrency');
  const toSelect = document.getElementById('toCurrency');
  const from = fromSelect.value;
  const to = toSelect.value;
  const amount = parseFloat(document.getElementById('amount').value);

  try {
    const result = await exchange.quickConvert(amount, from, to);
    document.getElementById('result').textContent = `Converted Amount: ${result.converted}`;
  } catch (error) {
    console.error('Error during conversion:', error);
    document.getElementById('result').textContent = 'Error during conversion. Please try again later.';
  }
});
```

## API Methods

### `exchange.list_countries()`
Fetches a list of countries and their associated currencies.

- **Returns**: An array of country objects with the following properties:
  - `currency_code`: The currency code (e.g., USD, EUR).
  - `currency_name`: The name of the currency (e.g., United States Dollar).
  - `rate`: The exchange rate relative to the Euro.

### `exchange.quickConvert(amount, from, to)`
Converts an amount from one currency to another.

- **Parameters**:
  - `amount` (number): The amount to convert.
  - `from` (string): The currency code to convert from.
  - `to` (string): The currency code to convert to.
- **Returns**: An object with the following properties:
  - `from`: The source currency code.
  - `to`: The target currency code.
  - `amount`: The original amount.
  - `converted`: The converted amount.

## Error Handling

Ensure proper error handling when using the API methods. For example:

```javascript
try {
  const countries = await exchange.list_countries();
} catch (error) {
  console.error('Error fetching countries:', error);
}
```

## Notes

- The `currencyElf.js` file relies on the backend for caching country data.
- Ensure the `baseUrl` in `currencyElf.js` points to the correct API endpoint.

This guide provides a basic overview of how to use the `currencyElf.js` file. For more advanced use cases, refer to the source code or contact the developer.