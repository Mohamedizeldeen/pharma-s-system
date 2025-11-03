<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\pharma;
use App\Models\branch;
use App\Models\medicines;

class RoleBasedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@pharma.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin'
        ]);
        
        echo "✓ Admin created: admin@pharma.com / admin123\n";

        // Create Pharmacy Owner 1
        $pharmaOwner1 = User::create([
            'name' => 'Ahmed Mohamed',
            'email' => 'ahmed@pharma.com',
            'password' => Hash::make('pharma123'),
            'role' => 'pharma'
        ]);

        $pharmacy1 = pharma::create([
            'user_id' => $pharmaOwner1->id,
            'name' => 'El Ezaby Pharmacy',
            'email' => 'ahmed@pharma.com',
            'main_address' => 'Cairo, Egypt',
            'phone' => '+201234567890'
        ]);

        echo "✓ Pharmacy 1 created: ahmed@pharma.com / pharma123\n";

        // Create branches for pharmacy 1
        $branch1_1 = branch::create([
            'pharma_id' => $pharmacy1->id,
            'branch_name' => 'El Ezaby - Nasr City',
            'longitude' => 31.3260,
            'latitude' => 30.0594,
            'phone' => '+201111111111',
            'open_time' => '08:00',
            'close_time' => '23:00'
        ]);

        $branch1_2 = branch::create([
            'pharma_id' => $pharmacy1->id,
            'branch_name' => 'El Ezaby - Maadi',
            'longitude' => 31.2631,
            'latitude' => 29.9602,
            'phone' => '+201111111112',
            'open_time' => '09:00',
            'close_time' => '22:00'
        ]);

        echo "✓ 2 branches created for El Ezaby Pharmacy\n";

        // Create medicines for branch 1_1
        medicines::create([
            'branch_id' => $branch1_1->id,
            'pharma_id' => $pharmacy1->id,
            'name' => 'Panadol',
            'scientific_name' => 'Paracetamol',
            'description' => 'Pain reliever and fever reducer',
            'quantity' => 150,
            'price' => 25.00
        ]);

        medicines::create([
            'branch_id' => $branch1_1->id,
            'pharma_id' => $pharmacy1->id,
            'name' => 'Brufen',
            'scientific_name' => 'Ibuprofen',
            'description' => 'Anti-inflammatory pain reliever',
            'quantity' => 100,
            'price' => 35.00
        ]);

        medicines::create([
            'branch_id' => $branch1_1->id,
            'pharma_id' => $pharmacy1->id,
            'name' => 'Augmentin',
            'scientific_name' => 'Amoxicillin + Clavulanic Acid',
            'description' => 'Antibiotic',
            'quantity' => 75,
            'price' => 85.00
        ]);

        // Create medicines for branch 1_2
        medicines::create([
            'branch_id' => $branch1_2->id,
            'pharma_id' => $pharmacy1->id,
            'name' => 'Aspirin',
            'scientific_name' => 'Acetylsalicylic acid',
            'description' => 'Blood thinner and pain reliever',
            'quantity' => 200,
            'price' => 15.00
        ]);

        medicines::create([
            'branch_id' => $branch1_2->id,
            'pharma_id' => $pharmacy1->id,
            'name' => 'Ventolin',
            'scientific_name' => 'Salbutamol',
            'description' => 'Asthma inhaler',
            'quantity' => 50,
            'price' => 120.00
        ]);

        echo "✓ 5 medicines created for El Ezaby branches\n";

        // Create Pharmacy Owner 2
        $pharmaOwner2 = User::create([
            'name' => 'Sara Ali',
            'email' => 'sara@pharma.com',
            'password' => Hash::make('pharma123'),
            'role' => 'pharma'
        ]);

        $pharmacy2 = pharma::create([
            'user_id' => $pharmaOwner2->id,
            'name' => 'Seif Pharmacy',
            'email' => 'sara@pharma.com',
            'main_address' => 'Alexandria, Egypt',
            'phone' => '+201987654321'
        ]);

        echo "✓ Pharmacy 2 created: sara@pharma.com / pharma123\n";

        // Create branch for pharmacy 2
        $branch2_1 = branch::create([
            'pharma_id' => $pharmacy2->id,
            'branch_name' => 'Seif - San Stefano',
            'longitude' => 29.9510,
            'latitude' => 31.2394,
            'phone' => '+201222222221',
            'open_time' => '08:00',
            'close_time' => '00:00'
        ]);

        echo "✓ 1 branch created for Seif Pharmacy\n";

        // Create medicines for branch 2_1
        medicines::create([
            'branch_id' => $branch2_1->id,
            'pharma_id' => $pharmacy2->id,
            'name' => 'Voltaren',
            'scientific_name' => 'Diclofenac',
            'description' => 'Anti-inflammatory',
            'quantity' => 80,
            'price' => 45.00
        ]);

        medicines::create([
            'branch_id' => $branch2_1->id,
            'pharma_id' => $pharmacy2->id,
            'name' => 'Zantac',
            'scientific_name' => 'Ranitidine',
            'description' => 'Stomach acid reducer',
            'quantity' => 60,
            'price' => 55.00
        ]);

        medicines::create([
            'branch_id' => $branch2_1->id,
            'pharma_id' => $pharmacy2->id,
            'name' => 'Lipitor',
            'scientific_name' => 'Atorvastatin',
            'description' => 'Cholesterol medication',
            'quantity' => 8,  // Low stock
            'price' => 150.00
        ]);

        echo "✓ 3 medicines created for Seif branch (1 low stock)\n";

        // Create Regular Users
        $user1 = User::create([
            'name' => 'Mohamed Hassan',
            'email' => 'mohamed@example.com',
            'password' => Hash::make('user123'),
            'role' => 'user'
        ]);

        $user2 = User::create([
            'name' => 'Fatma Ibrahim',
            'email' => 'fatma@example.com',
            'password' => Hash::make('user123'),
            'role' => 'user'
        ]);

        echo "✓ 2 regular users created\n";

        echo "\n========================================\n";
        echo "DATABASE SEEDED SUCCESSFULLY!\n";
        echo "========================================\n\n";
        
        echo "Admin Account:\n";
        echo "  Email: admin@pharma.com\n";
        echo "  Password: admin123\n\n";
        
        echo "Pharmacy Accounts:\n";
        echo "  1. ahmed@pharma.com / pharma123 (El Ezaby - 2 branches)\n";
        echo "  2. sara@pharma.com / pharma123 (Seif - 1 branch)\n\n";
        
        echo "User Accounts:\n";
        echo "  1. mohamed@example.com / user123\n";
        echo "  2. fatma@example.com / user123\n\n";

        echo "Statistics:\n";
        echo "  Total Pharmacies: 2\n";
        echo "  Total Branches: 3\n";
        echo "  Total Medicines: 8\n";
        echo "  Total Users: 5 (1 admin, 2 pharma, 2 users)\n\n";
    }
}
