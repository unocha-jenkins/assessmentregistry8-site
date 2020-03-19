# Assessment Registry

## JSON API

Has a problem outputting the label of a select list

## Todo

1. Use templates for formatters
2. ~~Fix json import~~
3. ~~Do full sync if cache is empty~~
4. ~~Create base class~~
5. ~~Move helpers to ocha_integrations~~
6. Update existing assessments
7. Use JSON callback for map
8. Sidebar has max width for map filters

## Cron jobs

fin drush eval --verbose "ocha_countries_cron()"
fin drush eval --verbose "ocha_disasters_cron()"
fin drush eval --verbose "ocha_local_groups_cron()"
fin drush eval --verbose "ocha_locations_cron()"
fin drush eval --verbose "ocha_organizations_cron()"
fin drush eval --verbose "ocha_themes_cron()"

## Migrate

```sql
select * from node__field_ass_date limit 10;
select * from node__field_assessment_data limit 10;
select * from node__field_assessment_questionnaire limit 10;
select * from node__field_assessment_report limit 10;
select * from node__field_asst_organizations limit 10;
select * from node__field_collection_methods limit 10;
select * from node__field_countries limit 10;
select * from node__field_disasters limit 10;
select * from node__field_frequency limit 10;
select * from node__field_key_findings limit 10;
select * from node__field_local_groups limit 10;
select * from node__field_locations limit 10;
select * from node__field_methodology limit 10;
select * from node__field_organizations limit 10;
select * from node__field_other_location limit 10;
select * from node__field_population_types limit 10;
select * from node__field_status limit 10;
select * from node__field_subject_objective limit 10;
select * from node__field_units_of_measurement limit 10;
```

### Wrong mapping

```sql
select * from node__field_themes limit 10; # wrong mapping
```

### Missing data

```sql
select * from node__field_level_of_representation limit 10;
select * from node__field_related_content limit 10;
select * from node__field_sample_size limit 10;
select * from node__field_sources limit 10;
```

Quick check using

```sql
select table_schema as database_name, table_name
   from information_schema.tables
where table_type = 'BASE TABLE' and table_rows = 0 and table_schema = 'default' and table_name like 'node__field%'
order by table_name;
```
