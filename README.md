# MOFH client by Hostronavt Team
## About
We are the best free hosting in Russia, and we understood that WHM API is really poor, so ...

We have created a script, that connects to MyOwnFreeHost panel and interacts with it.

Please if you use it, keep link to our hosting (https://hostronavt.ru) in your footer.
## Installation
Just copy lib folder to your hosting, do not remove .htaccess file there, because script keeps cookies in this folder.

Moreover, please CHMOD folder for server to have an opportunity to read and write.
## Require libs
A first thing to do is to require our lib:
```php
<?php
require_once('lib/lib.php');
```
Then you can use our classes and functions.
## Classes and functions
### Panel() class
Firstly, initialize panel connection with initialize function and your account credentials:
```php
$panel = new Panel($login, $password);
```
Then you have already an access to following functions:
```php
$panel->getStatistics(); 
// You will have access to following:
echo $panel->lastdayusers; // New accounts created in last 24 hours
echo $panel->activeusers; // Total active accounts
echo $panel->idleusers; // Total idle users
echo $panel->monthtraffic; // Total traffic this month
echo $panel->monthhits; // Total hits this month
//

$panel->changePassword($newpassword); // Changes MyOwnFreeHost account password
// Note: MyOwnFreeHost account password has to be 10 characters minimum, only letters and numbers
$users = $panel->findUsers($typeofsearch, $value); // Returns array with usernames
// There are three types of search: 'email', 'ipaddress', 'domain', choose one and give its value

```
### Client() class
To use Client() class you have to have Panel() one initialized.
You have to give two parametres to start: Panel() class variable and username:
```php
$client = new Client($panel, $username);
```
Below are functions:
```php
// Variables
echo $client->status; // Account status, 'Active' for example
echo $client->email; // Client's email
echo $client->plan; // Hosting plan
echo $client->signupDate; // Signup date
echo $client->signupIP; // Signup IP
echo $client->suspendComment // Comment, why user was suspended **
echo $client->resellerComment // Your comment on client
var_dump($client->domains); // Array of user domains *

// Functions

$link = $client->connectLink(); // Returns a direct link to connect to cPanel *
$client->displayErrors($bool); // True - client will see errors, false - not *
$client->showBanners($bool); // True - client will see ads (banners), false - not *
$client->changePlan($newplan); // Changes client's plan (check out Set Packages in MOFH Panel)
$client->suspend($reason); // Suspends user, give a string with reason *
$client->unsuspend(); // Unsuspends user if possible **
$client->setResellerComment($newcomment); // Changes string resellerComment

// * Only when the user has Active status
// ** Only when the user has Closed status
```
## Exceptions
All functions are throwing exceptions, so we advice to use try-catch.
## Code example
This code checks if the user is suspended and and unsuspends it.
```php
<?php

require_once('lib/lib.php');

try {
$panel = new Panel('mymail@gmail.com', 'mypassword');
$client = new Client($panel, 'onavt_24120903');
if ($client->status === "Closed") {
$client->unsuspend();
}
} catch (Exception $e) {
echo $e->getMessage(), "\n";
}
```
## Contact
Feel free to contact us at info@hostroteam.ru or at Byet.net forum (nick @hostronavt).
