# CatCare 360 Local Setup

1. Start Apache and MySQL in XAMPP.
2. Create the database:
   ```powershell
   C:\xampp\mysql\bin\mysql.exe -uroot -e "CREATE DATABASE IF NOT EXISTS catcare360 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
   ```
3. Run migrations and seed data:
   ```powershell
   php spark migrate
   php spark db:seed CatCareSeeder
   ```
4. Open the app:
   - Public: `http://localhost/CATCARE360/public/`
   - Staff/Admin: `http://localhost/CATCARE360/public/admin/login`
   - Volunteer: use the sign-in overlay on `http://localhost/CATCARE360/public/`

Default accounts:

- Staff/Admin: `staff@catcare360.org` / `password123`
- Adopter: `mara@example.com` / `adopter123`
- Volunteer: `kim@example.com` / `KIM2026`
