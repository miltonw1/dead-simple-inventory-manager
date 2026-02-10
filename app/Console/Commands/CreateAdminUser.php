<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the first administrator user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Create Admin User ===');
        $this->newLine();

        if (! $this->checkAdminExistence()) {
            return Command::SUCCESS;
        }

        $data = $this->requestUserData();

        if ($data['password'] !== $data['password_confirmation']) {
            $this->error('Passwords do not match.');

            return Command::FAILURE;
        }

        $validator = $this->validateData($data);

        if ($validator->fails()) {
            $this->displayValidationErrors($validator);

            return Command::FAILURE;
        }

        return $this->createUser($data);
    }

    /**
     * Decide whether to continue based on admin existence.
     *
     * Ask if the user wants to continue creating an admin.
     */
    private function checkAdminExistence(): bool
    {
        $exists = User::where('role', 'admin')->exists();

        if (! $exists) {
            return true;
        }

        $this->warn('An admin user already exists in the system.');

        if ($this->confirm('Do you want to create another admin user anyway?', false)) {
            return true;
        }

        $this->info('Operation cancelled.');

        return false;
    }

    /**
     * Request user data from console.
     *
     * @return array<string, string>
     */
    private function requestUserData(): array
    {
        return [
            'name' => (string) $this->ask('Administrator name'),
            'email' => (string) $this->ask('Administrator email'),
            'password' => (string) $this->secret('Password (minimum 8 characters)'),
            'password_confirmation' => (string) $this->secret('Confirm password'),
        ];
    }

    /**
     * Validate the provided data.
     *
     * @param  array<string, string>  $data
     */
    private function validateData(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);
    }

    /**
     * Display validation errors.
     */
    private function displayValidationErrors(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $this->error('Validation error:');
        foreach ($validator->errors()->all() as $error) {
            $this->error('- '.$error);
        }
    }

    /**
     * Create the admin user in database.
     *
     * @param  array<string, string>  $data
     */
    private function createUser(array $data): int
    {
        try {
            $user = User::create([...$data, 'role' => 'admin']);

            $this->newLine();
            $this->info('✓ Admin user created successfully!');
            $this->newLine();
            $this->table(
                ['ID', 'UUID', 'Name', 'Email', 'Role'],
                [[$user->id, $user->uuid, $user->name, $user->email, $user->role->value]]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error creating user: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
