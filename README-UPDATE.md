# STRUGAL Production — Laquage multi-step form update

This update changes the Laquage report flow to:

1. Enter the 8 shared report fields.
2. Continue to a production table with the 14 requested columns.
3. Use **Nouvelle ligne** to open a separate row editor. Each column is optional; a row needs at least one value to be added.
4. Save the report and all its production rows together in one database transaction.
5. Download the Excel copy with the Laquage sheet and all saved production rows.

A new `laquage_report_lines` table stores the production rows. The previous row columns on `laquage_reports` are left in place for compatibility with older records.

## Apply to your existing project

1. Make a backup of your current project folder and database first.
2. Extract this ZIP over a copy of your project, or copy the changed files into your existing project while keeping your own `.env` and `database/database.sqlite`.
3. From the project folder, run:

   ```bat
   composer install
   php artisan migrate
   php artisan optimize:clear
   ```

4. Start the application as you normally do. For a local Laravel server, run `php artisan serve`.
5. Open the Laquage report route while signed in as a Laquage department user or an admin.

**Do not run `php artisan migrate:fresh` on a database that contains reports or users.** It deletes existing tables and data.

## Setup on a new copy

This archive intentionally does not include your `.env`, SQLite database, or compiled view cache. For a new local copy:

1. Copy `.env.example` to `.env`.
2. Create an empty SQLite database file if using SQLite:

   ```bat
   type nul > database\database.sqlite
   ```

3. Run `composer install`, `php artisan key:generate`, and `php artisan migrate`.

## Important note about the Excel layout

The export preserves the original workbook and writes each production row from row 6 onward. Header input cells were adjusted to avoid overwriting the visible labels. Please verify the header values and formulas against the exact workbook used by your production team; Excel templates sometimes have merged cells or dedicated input cells that require a final cell-by-cell adjustment.
