<?php namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;
use CodeIgniter\CLI\CLI; // MELHORIA: Importa CLI para logs

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();

        // MELHORIA: Busca dados do .env
        $adminEmail = env('admin.email', 'admin@example.com');
        $adminPass = env('admin.password');
        $adminFirstName = env('admin.first_name', 'Admin');
        $adminLastName = env('admin.last_name', 'User');

        // BUG DE SEGURANÇA: Verifica se a senha foi definida no .env
        if (empty($adminPass) || $adminPass === '123456' || strlen($adminPass) < 8) {
            CLI::error("================== ATENÇÃO ==================");
            CLI::error("A senha do administrador não está definida ou é muito fraca no arquivo .env (admin.password).");
            CLI::error("Por favor, defina uma senha forte no .env antes de executar o seeder.");
            CLI::error("=============================================");
            echo "Seeder AdminUserSeeder interrompido.\n";
            return; // Interrompe a execução
        }

        $adminData = [
            'first_name'    => $adminFirstName,
            'last_name'     => $adminLastName,
            'email'         => $adminEmail,
            'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT) 
        ];

        // Verifica se o email já existe para não duplicar
        if ($userModel->where('email', $adminData['email'])->first() === null) {
            if ($userModel->insert($adminData)) {
                echo "-> [SUCESSO] Usuário administrador '{$adminEmail}' criado com sucesso.\n";
            } else {
                 echo "-> [ERRO] Erro ao criar usuário administrador '{$adminEmail}'.\n";
                 print_r($userModel->errors());
            }
        } else {
            echo "-> [INFO] Usuário administrador com email '{$adminEmail}' já existe. Tentando atualizar senha...\n";
            // MELHORIA: Se o usuário já existe, atualiza a senha dele para a do .env
            try {
                $existingUser = $userModel->where('email', $adminData['email'])->first();
                $userModel->update($existingUser->id, ['password_hash' => $adminData['password_hash']]);
                echo "-> [SUCESSO] Senha do usuário '{$adminEmail}' atualizada.\n";
            } catch (\Throwable $e) {
                 echo "-> [ERRO] Erro ao atualizar senha do '{$adminEmail}': " . $e->getMessage() . "\n";
            }
        }
    }
}