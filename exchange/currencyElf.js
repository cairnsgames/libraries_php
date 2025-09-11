let countriesCache = null;
let isLoadingCountries = false;

const baseUrl = "https://cairnsgames.co.za/php/exchange";

const exchange = {
  async list_countries() {
    const response = await fetch(`${baseUrl}/list_countries.php`);
    if (!response.ok) {
      throw new Error("Failed to fetch countries");
    }
    const result = await response.json();
    console.log("Countries fetched:", result);
    countriesCache = result.countries; 
    return result.countries;
  },

  async convert(from, to, amount) {
    const response = await fetch(`${baseUrl}/convert.php?from=${from}&to=${to}&amount=${amount}`);
    if (!response.ok) {
      throw new Error("Failed to convert currency");
    }
    return response.json();
  },

  async getCountry(code) {
    if (!countriesCache) {
      await this.list_countries();
    }
    if (!countriesCache) {
      throw new Error("Countries data could not be loaded");
    }
    return countriesCache.find(country => country.cca2 === code || country.cca3 === code);
  },

  async getCountryByCurrency(currencyCode) {
    if (!countriesCache) {
      await this.list_countries();
    }
    if (!countriesCache) {
      throw new Error("Countries data could not be loaded");
    }
    return countriesCache.filter(country => country.currency.includes(currencyCode));
  },

  async quickConvert(amount, from, to) {
    if (!countriesCache) {
      await this.list_countries();
    }
    if (!countriesCache) {
      throw new Error("Countries data could not be loaded");
    }

    const fromCountry = countriesCache.find(country => country.currency_code === from);
    const toCountry = countriesCache.find(country => country.currency_code === to);

    if (!fromCountry || !toCountry) {
      throw new Error("Invalid currency codes provided");
    }

    const euroRateFrom = parseFloat(fromCountry.rate);
    const euroRateTo = parseFloat(toCountry.rate);

    if (!euroRateFrom || !euroRateTo) {
      throw new Error("Euro rates not available for the provided currencies");
    }

    const amountInEuro = amount / euroRateFrom;
    const convertedAmount = amountInEuro * euroRateTo;

    return {
      from,
      to,
      amount,
      converted: convertedAmount
    };
  }
};

Object.defineProperty(exchange, 'countries', {
  get: async () => {
    if (!countriesCache && !isLoadingCountries) {
      isLoadingCountries = true;
      try {
        await exchange.list_countries();
      } finally {
        isLoadingCountries = false;
      }
    }
    return countriesCache;
  }
});

window.exchange = exchange;
