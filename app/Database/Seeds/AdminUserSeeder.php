<?php namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel; // Importa o UserModel

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();

        $adminData = [
            'first_name'    => 'Admin', // Substitua pelo seu primeiro nome
            'last_name'     => '',  // Substitua pelo seu sobrenome
            'email'         => 'seu_email@dominio.com', // Substitua pelo email que vai usar para login
            // IMPORTANTE: Substitua 'SUA_SENHA_FORTE_AQUI' pela senha que você quer usar
            'password_hash' => password_hash('123456', PASSWORD_DEFAULT) 
        ];

        // Verifica se o email já existe para não duplicar
        if ($userModel->where('email', $adminData['email'])->first() === null) {
            if ($userModel->insert($adminData)) {
                echo "Utilizador administrador criado com sucesso.\n";
            } else {
                 echo "Erro ao criar utilizador administrador.\n";
                 print_r($userModel->errors()); // Mostra erros de validação/DB se houver
            }
        } else {
            echo "Utilizador administrador com este email já existe.\n";
        }
    }
}