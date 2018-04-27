### Dependencies
1. You need a Server or Client with PHP-CLI

### Getting started
1. Get a Page Access-Token for the Page that holds the Instant Articles (https://developers.facebook.com/tools/explorer/)
2. Open the File and insert your Page Access-Token in Line 105 (https://github.com/AlwokeInteractive/FacebookIAHTTPS/blob/7537073c47ed113ebfec80b615f2f694b91b6dd8/script.php#L105)
3. Run the File with "php script.php"

### Notes
If you have a lot of Instant Articles, you might want to add the "sleep()" Method somewhere so you do not trigger the Facebook rate limitting. Alternatively you can run the Script again after the rate-limitting is over.
