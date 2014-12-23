# Lone Star PHP Mass Mailer

To get setup:

1. Run `composer install`
2. Copy `config.php.dist` into `config.php` and update the values accordingly
3. Generate an `acceptance.csv` containing: First Name, Last Name, Email, and a pipe-separated list of talk titles. Please see `acceptance.csv.sample` for an example.
4. Generate a `rejection.csv` contianing: First Name, Last Name, and Email.
5. Update the `acceptance.txt` and `rejection.txt` templates as needed.

## Testing

### Dry Run

`php send_emails.php dry` will perform a dry run and show the emails that would attempt to send.

### Override to-email

`php send_emails.php "some@email.tld"` will override the TO field for all emails.

### Further Testing

You'll wish to alter the source of `send_emails.php` to dump message contents. About line 104 is a good place to drop a `print $message->getText();` to inspect message contents.

## Execution

`php send_emails.php` will perform a LIVE email of all recipients. **ENSURE YOU'VE TESTED AS THERE IS NO GOING BACK**
