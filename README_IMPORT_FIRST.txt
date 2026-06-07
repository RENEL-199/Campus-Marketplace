ISKOHUB NORMALIZED VERSION

1. Copy the whole IskoHub_Normalized folder into C:/xampp/htdocs/
2. Open XAMPP and start Apache + MySQL.
3. Open phpMyAdmin.
4. Import this SQL file:
   database/iskohub_clean_normalized.sql

Database name used by this project:
   iskohub_clean

The app/Database.php file is already updated to connect to iskohub_clean.

Open the website using:
   http://localhost/IskoHub_Normalized/public/

Notes:
- The duplicate lost_found_items and lost_found_messages tables were removed.
- Lost & Found now uses only lost_items and lost_found_claims.
- Order item/rental/service details are separated into child tables.
- Upload folders were included with .keep files.
