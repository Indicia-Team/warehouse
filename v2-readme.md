V2 readme

* Any overridden control templates in $indicia_templates must have an {attributes}
  replacement added in order to pick up validation.
* Overridden control templates do not need {readonly} or {disabled} replacement
  tokens where the {attributes} token is present.
* Any custom validation code may need to be altered to work with jQuery Validation
  1.17.
* Updated jQuery to jQuery 3.2.1.
* Updated jQuery UI to 1.12
* PHP 5.4+ mandatory, 5.6+ preferred, 7.0+ recommended.

