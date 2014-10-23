Codeception: Sauce with Meta
============================

Based on https://github.com/psychomieze/sauceExtension and https://github.com/neam/codeception-saucelabs-metadata, this pulls both the functionality into one.

```yml
extensions:
  enabled:
    - Codeception\Extension\SauceExtension
  config:
    Codeception\Extension\SauceExtension:
      username: '<username>'
      accesskey: '<accessKey>'
#     tags: ''
      build: 'AProject'
```      