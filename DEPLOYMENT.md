# Healthcare deployment

Deploy the complete `HEALTHCARE` directory as one web root. The applications now live under these routes:

- `/` — citizen-facing Healthcare portal
- `/drivers/` — ambulance driver portal
- `/hospitals/` — hospital portal

All three use the `healthcare` MySQL database configured in their local `db.php` files. Before deployment, update those database credentials for the target environment and ensure the shared database contains the tables required by each module.

For the hospital demo data, visit `/hospitals/create_demo_hospitals.php` once after the database is ready, then remove or protect that script.
