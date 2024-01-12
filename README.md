Currency Center
--------------------------------------------------------------------------------
Currency converter for Drupal 9 based on Fixer.io rates.

You will need an API key from Fixer.io in order to make module work.

Module stores rates (predefined currencies are EUR, USD, GBP, JPY) and updates them once a day via cron.

With help of this module you can convert currencies without external API call to Fixer.io.
Use convert function (make sure currency codes are correct):

```php
   convert(float $value, string $fromCurrency, string $toCurrency);
```

Please note, that all rates are stored in EUR, as EUR is a default base currency for Fixer.io
