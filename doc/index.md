# Mail bundle

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](usage.md)
- [Events](events.md)
- [Tracking](tracking.md)

## Installation

First ensure you have added the endpoint `https://symfony-recipes.webeak.fr` to your `composer.json` file, 
so `flex` can find the recipe :

```json
{
    [...]
    "extra": {
        "symfony": {
            "endpoint": "https://symfony-recipes.webeak.fr"
        }
    }
}
```

You can then install the bundle:

```bash
composer require webeak-mail
```

The if you use `Doctrine`, execute the following command to create bundle's entities:

```bash
php bin/console doctrine:schema:update --force
```

## Configuration

Edit your `.env` file and set the `MAILER_URL` of SwitfMailer: 

```dotenv
# .env

[...]
###> symfony/swiftmailer-bundle ###
# For Gmail as a transport, use: "gmail://username:password@localhost"
# For a generic SMTP server, use: "smtp://localhost:25?encryption=&auth_mode="
# Delivery is disabled by default via "null://localhost"
MAILER_URL=null://localhost
###< symfony/swiftmailer-bundle ###
```

While in dev you can install [MailHog](https://github.com/mailhog/MailHog) for example to catch outgoing emails.

You can then set `MAILER_URL` to `smtp://localhost:1025`.


