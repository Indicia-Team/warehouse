Provides template report files that are designed to be fired by the Triggers and notifications
tool on the warehouse, for example to provide notifications when a record of a certain species
is added to the system.

Predefined columns:
* website_id - provided to enable permissions to be applied.
* notify_user_id - if a column exists then specifies the user ID or a comma separated list of user
  IDs of a person who will automatically receive a notification. If using this method, then a
  subscription must still be created for the trigger to define the frequency, even if the
  subscription doesn't declare any email addresses to send it to.