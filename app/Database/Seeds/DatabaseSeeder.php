<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
/**
 * Este Seeder é o ponto de entrada principal.
 * Quando você executa `php spark db:seed`, este é o arquivo que o CodeIgniter procura.
 * Ele então chama todos os outros seeders listados no método run().
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Chama os seeders individuais na ordem de importância
        // 1. Cria o usuário admin
        $this->call('AdminUserSeeder');
        // 2. Cria as credenciais iniciais do ML
        $this->call('MlCredentialsSeeder');
        // 3. Cria o template de mensagem padrão
        $this->call('DefaultMessageTemplateSeeder');
        // (Adicione qualquer outro seeder que você criar aqui)
        // Ex: $this->call('OutroSeeder');
    }
}