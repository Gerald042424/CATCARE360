<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CatCareSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $adopterPassword = (string) (getenv('DEMO_ADOPTER_PASSWORD') ?: env('demo.adopter.password', ''));
        $volunteerPassword = (string) (getenv('DEMO_VOLUNTEER_PASSWORD') ?: env('demo.volunteer.password', ''));
        if ($adopterPassword === '' || $volunteerPassword === '') {
            throw new \RuntimeException('Set DEMO_ADOPTER_PASSWORD and DEMO_VOLUNTEER_PASSWORD before running the demo seed.');
        }

        $this->db->table('animals')->insertBatch([
            ['name' => 'Luna', 'species' => 'Cat', 'breed' => 'Maine Coon', 'age' => '3 years', 'weight' => '4.2kg', 'status' => 'Available', 'stage' => 'Adoption Ready', 'health' => 'Healthy', 'intake_date' => '2024-01-15', 'behavior_notes' => 'Playful and energetic', 'medical_notes' => 'Vaccinated, dewormed', 'image_url' => 'https://images.unsplash.com/photo-1574158622682-e40e69881006?auto=format&fit=crop&w=900&q=80', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Milo', 'species' => 'Cat', 'breed' => 'Domestic Shorthair', 'age' => '2 years', 'weight' => '3.8kg', 'status' => 'Adopted', 'stage' => 'Post-Adoption', 'health' => 'Healthy', 'intake_date' => '2023-11-20', 'behavior_notes' => 'Gentle and calm', 'medical_notes' => 'All vaccines up to date', 'image_url' => 'https://images.unsplash.com/photo-1511044568932-338cba0ad803?auto=format&fit=crop&w=900&q=80', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oliver', 'species' => 'Cat', 'breed' => 'Siamese', 'age' => '1 year', 'weight' => '3.1kg', 'status' => 'Medical', 'stage' => 'Under Treatment', 'health' => 'Recovering', 'intake_date' => '2024-02-01', 'behavior_notes' => 'Shy initially', 'medical_notes' => 'Respiratory infection treatment', 'image_url' => 'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?auto=format&fit=crop&w=900&q=80', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bella', 'species' => 'Cat', 'breed' => 'Persian', 'age' => '4 years', 'weight' => '4.5kg', 'status' => 'Foster', 'stage' => 'Foster Care', 'health' => 'Healthy', 'intake_date' => '2023-09-10', 'behavior_notes' => 'Loves cuddles', 'medical_notes' => 'Spayed, microchipped', 'image_url' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=900&q=80', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Leo', 'species' => 'Cat', 'breed' => 'Tabby', 'age' => '5 years', 'weight' => '5.0kg', 'status' => 'Available', 'stage' => 'Adoption Ready', 'health' => 'Healthy', 'intake_date' => '2024-01-28', 'behavior_notes' => 'Independent', 'medical_notes' => 'All vaccines current', 'image_url' => 'https://images.unsplash.com/photo-1543852786-1cf6624b9987?auto=format&fit=crop&w=900&q=80', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->db->table('adopters')->insertBatch([
            ['name' => 'Mara Santos', 'email' => 'mara@example.com', 'phone' => '0917-555-0101', 'occupation' => 'Employed Designer', 'housing_type' => 'Condo', 'address' => 'McKinley West, Taguig', 'address_lat' => 14.53540000, 'address_lng' => 121.05090000, 'compliance_score' => 91, 'next_check_in' => date('Y-m-d', strtotime('+3 days')), 'total_submissions' => 3, 'overdue_visits' => 0, 'gps_verified_count' => 3, 'flag' => 'None', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Luis Reyes', 'email' => 'luis@example.com', 'phone' => '0928-555-0188', 'occupation' => 'Self-employed', 'housing_type' => 'House', 'address' => 'BGC, Taguig', 'address_lat' => 14.55030000, 'address_lng' => 121.04980000, 'compliance_score' => 68, 'next_check_in' => date('Y-m-d', strtotime('-2 days')), 'total_submissions' => 1, 'overdue_visits' => 1, 'gps_verified_count' => 1, 'flag' => 'WANTED', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->db->table('adoptions')->insertBatch([
            ['adopter_id' => 1, 'animal_id' => 2, 'adoption_date' => date('Y-m-d', strtotime('-28 days')), 'status' => 'Active', 'created_at' => $now, 'updated_at' => $now],
            ['adopter_id' => 2, 'animal_id' => 4, 'adoption_date' => date('Y-m-d', strtotime('-9 days')), 'status' => 'Active', 'created_at' => $now, 'updated_at' => $now],
        ]);

        if ($this->db->tableExists('adopter_users')) {
            $this->db->table('adopter_users')->ignore(true)->insert([
                'adopter_id' => 1,
                'email' => 'mara@example.com',
                'password_hash' => password_hash($adopterPassword, PASSWORD_DEFAULT),
                'first_name' => 'Mara',
                'last_name' => 'Santos',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->db->table('applications')->insertBatch([
            ['applicant_name' => 'Ana Cruz', 'email' => 'ana@example.com', 'phone' => '0917-888-2121', 'occupation' => 'Employed Teacher', 'housing_type' => 'Apartment', 'animal_id' => 1, 'status' => 'Pending Review', 'compatibility_score' => 82, 'submitted_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['applicant_name' => 'Nico Lim', 'email' => 'nico@example.com', 'phone' => '0999-121-2222', 'occupation' => 'Student', 'housing_type' => 'Rented', 'animal_id' => 5, 'status' => 'Under Review', 'compatibility_score' => 57, 'submitted_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->db->table('application_documents')->insertBatch([
            ['application_id' => 1, 'doc_type' => 'gov_id', 'file_url' => null, 'status' => 'Verified'],
            ['application_id' => 1, 'doc_type' => 'address_proof', 'file_url' => null, 'status' => 'Pending'],
            ['application_id' => 1, 'doc_type' => 'home_photos', 'file_url' => null, 'status' => 'Pending'],
            ['application_id' => 1, 'doc_type' => 'selfie_with_id', 'file_url' => null, 'status' => 'Pending'],
        ]);

        $this->db->table('monitoring_submissions')->insertBatch([
            ['adopter_id' => 1, 'adoption_id' => 1, 'checkpoint' => '3-day', 'submission_date' => date('Y-m-d H:i:s', strtotime('-25 days')), 'photo_url' => null, 'health_status' => 'Healthy and eating well.', 'behavior_notes' => 'Adjusted quickly.', 'gps_lat' => 14.53550000, 'gps_lng' => 121.05100000, 'gps_verified' => true, 'status' => 'Verified', 'created_at' => $now, 'updated_at' => $now],
            ['adopter_id' => 1, 'adoption_id' => 1, 'checkpoint' => '3-week', 'submission_date' => date('Y-m-d H:i:s', strtotime('-7 days')), 'photo_url' => null, 'health_status' => 'Normal.', 'behavior_notes' => 'Playful.', 'gps_lat' => 14.53550000, 'gps_lng' => 121.05100000, 'gps_verified' => true, 'status' => 'Verified', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->db->table('volunteers')->insertBatch([
            ['name' => 'Kim Dela Paz', 'role' => 'Feeding Lead', 'email' => 'kim@example.com', 'phone' => '0917-333-1212', 'location' => 'Taguig', 'status' => 'Active', 'availability' => 'Mon, Wed, Fri', 'notes' => 'Reliable morning volunteer.', 'image_url' => null, 'joined_date' => '2024-01-11', 'tasks_completed' => 42, 'pending_tasks' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Paolo Garcia', 'role' => 'Transport', 'email' => 'paolo@example.com', 'phone' => '0920-222-1010', 'location' => 'Makati', 'status' => 'Busy', 'availability' => 'Sat, Sun', 'notes' => 'Can handle vet runs.', 'image_url' => null, 'joined_date' => '2024-02-03', 'tasks_completed' => 26, 'pending_tasks' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jia Mercado', 'role' => 'Socialization', 'email' => 'jia@example.com', 'phone' => '0918-222-4555', 'location' => 'BGC', 'status' => 'Active', 'availability' => 'Tue, Thu', 'notes' => 'Great with shy cats.', 'image_url' => null, 'joined_date' => '2024-03-18', 'tasks_completed' => 18, 'pending_tasks' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->db->table('volunteer_users')->insert([
            'volunteer_id' => 1,
            'email' => 'kim@example.com',
            'access_code' => $volunteerPassword,
            'password_hash' => password_hash($volunteerPassword, PASSWORD_DEFAULT),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->table('tasks')->insertBatch([
            ['title' => 'Morning feeding rotation', 'description' => 'Feed shelter cats and refresh water bowls.', 'category' => 'Feeding', 'priority' => 'High', 'status' => 'Pending', 'assigned_to' => 1, 'animal_id' => null, 'task_date' => date('Y-m-d'), 'task_time' => '08:00:00', 'location' => 'Shelter A', 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Vet transport for Oliver', 'description' => 'Bring Oliver to clinic follow-up.', 'category' => 'Transport', 'priority' => 'Medium', 'status' => 'In Progress', 'assigned_to' => 2, 'animal_id' => 3, 'task_date' => date('Y-m-d', strtotime('+1 day')), 'task_time' => '13:30:00', 'location' => 'McKinley West', 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Socialize shy kittens', 'description' => 'Gentle play and handling practice.', 'category' => 'Socialization', 'priority' => 'Low', 'status' => 'Pending', 'assigned_to' => 3, 'animal_id' => null, 'task_date' => date('Y-m-d', strtotime('+2 days')), 'task_time' => '10:00:00', 'location' => 'Foster Room', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
