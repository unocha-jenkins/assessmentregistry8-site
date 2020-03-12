# Assessment Registry

## JSON API

Has a problem outputting the label of a select list

## Todo

1. Use templates for formatters
2. ~~Fix json import~~
3. ~~Do full sync if cache is empty~~
4. ~~Create base class~~
5. ~~Move helpers to ocha_integrations~~

## Cron jobs

fin drush eval --verbose "ocha_countries_cron()"
fin drush eval --verbose "ocha_disasters_cron()"
fin drush eval --verbose "ocha_local_groups_cron()"
fin drush eval --verbose "ocha_locations_cron()"
fin drush eval --verbose "ocha_organizations_cron()"
fin drush eval --verbose "ocha_themes_cron()"
