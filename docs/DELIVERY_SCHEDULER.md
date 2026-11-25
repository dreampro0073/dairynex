# Automatic delivery generation

DairyNex schedules `deliveries:generate-next-week` every day at 12:10 AM in the
application timezone. The command prepares deliveries for today and the next
six calendar days for every active dairy with an active client admin. Running
it again is safe: an existing delivery for the same subscription, date, and
slot is not duplicated.

Set `APP_TIMEZONE` in the production environment to the timezone used by the
dairy, for example `Asia/Kolkata` for India. Clear cached configuration after
changing it:

```sh
php artisan config:clear
```

Add Laravel's scheduler runner to the server crontab. Replace the project path
with the deployed DairyNex path and use the PHP executable installed on that
server:

```cron
* * * * * cd /path/to/dairynex && php artisan schedule:run >> /dev/null 2>&1
```

The command can also be run manually:

```sh
php artisan deliveries:generate-next-week
```

If a client has an invalid delivery route or schedule, the command reports that
client and date, continues with the remaining dates and clients, and exits with
a failure status. Check Laravel's command output/application log, correct the
setup, then run the command again.
