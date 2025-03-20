# csv-app
 
A simple API for uploading CSV with products data, processing them, and saving the data in a DB
The API uses Laravel Queues to run the CSV processing in the background.
The API also sends an email to the user when the file processing completes/fails.

Prerequisites:
Installed PHP, Laravel and a running MySQL DB
Also configure the .env file correspondingly

To start the dev server run:
`composer run dev`

To run the tests execute:
`php artisan test`
