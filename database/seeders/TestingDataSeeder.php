<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Client;
use App\Models\User;
use App\Models\VacancyStatus;
use App\Models\Vacancy;
use App\Models\Candidate;
use App\Models\SubmissionStatus;
use App\Models\VacancySubmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestingDataSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        VacancySubmission::query()->delete();
        Vacancy::query()->delete();
        Candidate::query()->delete();
        User::query()->delete();
        Client::query()->delete();

        // 1. Create Roles
        $adminRole = Role::where('role_name', 'admin')->first();
        $clientRole = Role::where('role_name', 'client')->first();

        // 2. Create Clients (Perusahaan)
        $clients = [
            [
                'company_name' => 'PT. Tech Innovasi Indonesia',
                'contact_person' => 'Budi Santoso',
                'contact_email' => 'budi.santoso@techinnovasi.co.id',
                'contact_phone' => '081234567890',
                'address' => 'Jl. Sudirman No. 123, Jakarta Selatan',
            ],
            [
                'company_name' => 'CV. Digital Kreatif Mandiri',
                'contact_person' => 'Sari Dewi',
                'contact_email' => 'sari.dewi@digitkreatif.com',
                'contact_phone' => '081298765432',
                'address' => 'Jl. Thamrin No. 45, Jakarta Pusat',
            ],
            [
                'company_name' => 'PT. Fintech Nusantara',
                'contact_person' => 'Ahmad Rizki',
                'contact_email' => 'ahmad.rizki@fintech-nusantara.co.id',
                'contact_phone' => '081312345678',
                'address' => 'Jl. Gatot Subroto No. 78, Jakarta Selatan',
            ]
        ];

        foreach ($clients as $clientData) {
            Client::create($clientData);
        }

        // 3. Create Users untuk masing-masing client
        $users = [
            [
                'client_id' => 1,
                'role_id' => $clientRole->id,
                'email' => 'hr@techinnovasi.co.id',
                'password' => Hash::make('password123'),
            ],
            [
                'client_id' => 2,
                'role_id' => $clientRole->id,
                'email' => 'recruitment@digitkreatif.com',
                'password' => Hash::make('password123'),
            ],
            [
                'client_id' => 3,
                'role_id' => $clientRole->id,
                'email' => 'career@fintech-nusantara.co.id',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // 4. Create Vacancy Statuses (jika belum ada)
        $statuses = ['Open', 'On-Process', 'Closed'];
        foreach ($statuses as $status) {
            VacancyStatus::firstOrCreate(['status_name' => $status]);
        }

        // 5. Create Vacancies (Lowongan)
        $vacancies = [
            // Client 1 - PT. Tech Innovasi Indonesia
            [
                'client_id' => 1,
                'status_id' => 1, // Open
                'position_name' => 'Senior Backend Developer',
                'level' => 'Senior',
                'job_description' => 'Membangun dan memelihara sistem backend yang scalable menggunakan Laravel dan Node.js. Bertanggung jawab atas arsitektur API dan integrasi dengan third-party services.',
                'required_skills' => 'Laravel, Node.js, PostgreSQL, REST API, Docker, AWS',
            ],
            [
                'client_id' => 1,
                'status_id' => 1, // Open
                'position_name' => 'Frontend Developer',
                'level' => 'Middle',
                'job_description' => 'Mengembangkan user interface yang responsive dan interaktif menggunakan React.js dan Vue.js. Bekerja sama dengan tim design untuk implementasi UI/UX.',
                'required_skills' => 'React.js, Vue.js, JavaScript, HTML5, CSS3, Tailwind CSS',
            ],
            
            // Client 2 - CV. Digital Kreatif Mandiri
            [
                'client_id' => 2,
                'status_id' => 2, // On-Process
                'position_name' => 'Fullstack Developer',
                'level' => 'Senior',
                'job_description' => 'Mengembangkan aplikasi web dari frontend hingga backend. Memiliki pengalaman dalam framework Laravel dan React/Vue.js.',
                'required_skills' => 'Laravel, React.js, MySQL, JavaScript, Git, RESTful API',
            ],
            
            // Client 3 - PT. Fintech Nusantara
            [
                'client_id' => 3,
                'status_id' => 1, // Open
                'position_name' => 'DevOps Engineer',
                'level' => 'Senior',
                'job_description' => 'Mengelola infrastructure, CI/CD pipeline, dan monitoring system. Memastikan availability dan performance aplikasi production.',
                'required_skills' => 'AWS, Docker, Kubernetes, Jenkins, Terraform, Linux',
            ],
            [
                'client_id' => 3,
                'status_id' => 3, // Closed
                'position_name' => 'Product Manager',
                'level' => 'Senior',
                'job_description' => 'Memimpin pengembangan produk fintech dari konsep hingga launch. Bekerja dengan tim engineering dan business untuk menentukan roadmap produk.',
                'required_skills' => 'Product Management, Agile, JIRA, Analytics, User Research',
            ],
        ];

        foreach ($vacancies as $vacancyData) {
            Vacancy::create($vacancyData);
        }

        // 6. Create Candidates (Kandidat)
        $candidates = [
            [
                'unique_talent_id' => 'TALENT-001',
                'full_name' => 'Rina Wijaya',
                'email' => 'rina.wijaya@email.com',
                'phone_number' => '08111222333',
                'linkedin_url' => 'https://linkedin.com/in/rina-wijaya',
                'experience_summary' => '5+ years experience in backend development with Laravel and Node.js. Strong knowledge in database design and API development.',
                'skills_summary' => 'Laravel, Node.js, PostgreSQL, MySQL, REST API, Docker, AWS EC2',
                'education_summary' => 'Bachelor of Computer Science - Universitas Indonesia',
                'is_available' => true,
            ],
            [
                'unique_talent_id' => 'TALENT-002',
                'full_name' => 'David Setiawan',
                'email' => 'david.setiawan@email.com',
                'phone_number' => '08144555666',
                'linkedin_url' => 'https://linkedin.com/in/david-setiawan',
                'experience_summary' => 'Frontend developer specializing in React and Vue.js. Experience in building responsive web applications and component libraries.',
                'skills_summary' => 'React.js, Vue.js, JavaScript, TypeScript, Tailwind CSS, Redux, Jest',
                'education_summary' => 'Diploma in Information Technology - Politeknik Negeri Jakarta',
                'is_available' => true,
            ],
            [
                'unique_talent_id' => 'TALENT-003',
                'full_name' => 'Sari Permata',
                'email' => 'sari.permata@email.com',
                'phone_number' => '08177888999',
                'portfolio_url' => 'https://sari-portfolio.com',
                'experience_summary' => 'Fullstack developer with 4 years experience in Laravel and React. Passionate about clean code and best practices.',
                'skills_summary' => 'Laravel, React.js, MySQL, JavaScript, Bootstrap, Git, REST API',
                'education_summary' => 'Bachelor of Information Systems - Bina Nusantara University',
                'is_available' => true,
            ],
            [
                'unique_talent_id' => 'TALENT-004',
                'full_name' => 'Andi Pratama',
                'email' => 'andi.pratama@email.com',
                'phone_number' => '08212345678',
                'linkedin_url' => 'https://linkedin.com/in/andi-pratama',
                'experience_summary' => 'DevOps engineer with expertise in cloud infrastructure and CI/CD. Certified AWS Solutions Architect.',
                'skills_summary' => 'AWS, Docker, Kubernetes, Jenkins, Terraform, Linux, Bash, Python',
                'education_summary' => 'Master of Computer Science - Institut Teknologi Bandung',
                'is_available' => true,
            ],
            [
                'unique_talent_id' => 'TALENT-005',
                'full_name' => 'Maya Sari',
                'email' => 'maya.sari@email.com',
                'phone_number' => '08298765432',
                'experience_summary' => 'Experienced product manager in fintech industry. Strong background in agile methodology and user-centered design.',
                'skills_summary' => 'Product Management, Agile, Scrum, JIRA, Analytics, SQL, Figma',
                'education_summary' => 'MBA - Universitas Gadjah Mada',
                'is_available' => false,
            ],
        ];

        foreach ($candidates as $candidateData) {
            Candidate::create($candidateData);
        }

        // 7. Create Submission Statuses (jika belum ada)
        $submissionStatuses = ['submitted', 'client_interested', 'client_rejected'];
        foreach ($submissionStatuses as $status) {
            SubmissionStatus::firstOrCreate(['status_name' => $status]);
        }

        // 8. Create Vacancy Submissions (Pengajuan Kandidat)
        $submissions = [
            // Senior Backend Developer - PT. Tech Innovasi Indonesia
            [
                'vacancy_id' => 1, // Senior Backend Developer
                'candidate_id' => 1, // Rina Wijaya
                'submission_status_id' => 1, // submitted
                'submitted_by_user_id' => 1, // admin user
            ],
            [
                'vacancy_id' => 1, // Senior Backend Developer
                'candidate_id' => 4, // Andi Pratama
                'submission_status_id' => 2, // client_interested
                'submitted_by_user_id' => 1,
                'client_feedback' => 'Kandidat memiliki pengalaman yang sesuai dengan kebutuhan kami. Silakan jadwalkan interview technical.',
            ],

            // Frontend Developer - PT. Tech Innovasi Indonesia
            [
                'vacancy_id' => 2, // Frontend Developer
                'candidate_id' => 2, // David Setiawan
                'submission_status_id' => 1, // submitted
                'submitted_by_user_id' => 1,
            ],

            // Fullstack Developer - CV. Digital Kreatif Mandiri
            [
                'vacancy_id' => 3, // Fullstack Developer
                'candidate_id' => 3, // Sari Permata
                'submission_status_id' => 3, // client_rejected
                'submitted_by_user_id' => 1,
                'client_feedback' => 'Pengalaman kandidat kurang sesuai dengan stack technology yang kami gunakan.',
            ],

            // DevOps Engineer - PT. Fintech Nusantara
            [
                'vacancy_id' => 4, // DevOps Engineer
                'candidate_id' => 4, // Andi Pratama
                'submission_status_id' => 2, // client_interested
                'submitted_by_user_id' => 1,
                'client_feedback' => 'Sangat tertarik dengan pengalaman AWS dan certification kandidat. Mohon dijadwalkan interview dengan CTO.',
            ],

            // Product Manager - PT. Fintech Nusantara (Closed position)
            [
                'vacancy_id' => 5, // Product Manager
                'candidate_id' => 5, // Maya Sari
                'submission_status_id' => 3, // client_rejected
                'submitted_by_user_id' => 1,
                'client_feedback' => 'Posisi sudah terisi. Akan simpan CV untuk kebutuhan di masa depan.',
            ],
        ];

        foreach ($submissions as $submissionData) {
            VacancySubmission::create($submissionData);
        }

        $this->command->info('✅ Testing data berhasil dibuat!');
        $this->command->info('📋 Client Users:');
        $this->command->info('   - hr@techinnovasi.co.id / password123');
        $this->command->info('   - recruitment@digitkreatif.com / password123');
        $this->command->info('   - career@fintech-nusantara.co.id / password123');
        $this->command->info('👤 Admin: admin@talentgo.com / password');
    }
}