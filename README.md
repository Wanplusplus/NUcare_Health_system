For the database setup, import the SQL files in the correct order.

First, import `nucaredb.sql` because this should create the main database structure, tables, and required base data.

After `nucaredb.sql` has been imported successfully, import `summary.sql`. This file should be treated as the next database update or additional SQL setup that depends on the main database already existing.

Make sure the database import order is followed exactly:

1. `nucaredb.sql`
2. `summary.sql`

Do not import `summary.sql` before `nucaredb.sql`, because it may depend on tables, fields, or data created by `nucaredb.sql`.

Also check the database-related file paths after reorganizing the project structure. If any backend file connects to the database or references these SQL files, update the paths so they match the final folder structure.

Place the SQL files inside the proper database folder, for example:

database/

* nucaredb.sql
* summary.sql

After restructuring, include the correct database setup instructions in the final summary, including:

* where the SQL files are located
* the correct import order
* any command or manual steps needed to import them
* any database connection files that were edited
