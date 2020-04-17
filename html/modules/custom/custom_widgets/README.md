# Custom widgets for Drupal 8

## TextAutocompleteWidget

Widget to use autocomplete functionality on lists, supports

- [List (float)](https://api.drupal.org/api/drupal/core%21modules%21options%21src%21Plugin%21Field%21FieldType%21ListFloatItem.php/9.0.x)
- [List (integer)](https://api.drupal.org/api/drupal/core%21modules%21options%21src%21Plugin%21Field%21FieldType%21ListIntegerItem.php/9.0.x)
- [List (string)](https://api.drupal.org/api/drupal/core%21modules%21options%21src%21Plugin%21Field%21FieldType%21ListStringItem.php/9.0.x)

Works with lists defined in the UI and list defined using [callback_allowed_values_function](https://api.drupal.org/api/drupal/core%21modules%21options%21options.api.php/function/callback_allowed_values_function/9.0.x)

### Widget settings

#### Max number of results to return

Specify the max number of result to return during the AJAX callback.

Default: 15

#### Matching method

- Contains
- Begins with

Default: Contains

#### Use select 2

If the [Select 2](https://www.drupal.org/project/select2) is installed and enabled, Select 2 can be used.


