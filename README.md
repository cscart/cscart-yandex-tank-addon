# Yandex.Tank Payload Generator for CS-Cart Benchmarking
Generate configuration for Yandex.Tank to benchmark performance of your site.

## Usage (CLI)

```bash
$ php admin.php --dispatch=yandex_tank.generate_ammo_file --switch_company_id=1
```

This will generate `ammo.txt` file at `var/` directory under your CS-Cart installation directory. You're free to use it as a Yandex.Tank payload.

Ammo file will be filled up with HTTP requests definitions mapped to every product and category existent in your store.
