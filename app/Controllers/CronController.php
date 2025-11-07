<?php
namespace App\Controllers;

use App\Libraries\MercadoLivreAuth;
use Config\Database;
use Config\Services;

/**
 * ATENÇÃO: Este controlador permite executar tarefas de CLI (spark)
 * através da web, contornando a limitação da versão PHP do terminal.
 * Proteja-o com uma chave secreta forte!
 */
class CronController extends BaseController
{
    // *** DEFINA A SUA CHAVE SECRETA AQUI ***
    // (Use um gerador de senhas, ex: 'aJ9bZ7P3kL6qR8sW2vY5uX1wE0pD4fG7')
    private string $secretKey = 'mR7HAhCdw9JPqylpbSLxWeyC879SoLNw';

    /**
     * Verifica a chave de segurança
     */
    private function checkAuth(string $key): bool
    {
        if (empty($key) || $key !== $this->secretKey) {
            log_message('error', '[CronController] Tentativa de acesso NÃO AUTORIZADA.');
            return false;
        }
        return true;
    }

    /**
     * Executa as Migrations (spark migrate)
     */
    public function runMigrate(string $key)
    {
        if (! $this->checkAuth($key)) {
            return $this->response->setStatusCode(403, 'Acesso Negado');
        }

        $migrate = Services::migrations();
        try {
            $migrate->latest();
            echo "Migrations executadas com sucesso!";
        } catch (\Throwable $e) {
            log_message('error', '[CronController] Erro ao migrar: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody($e->getMessage());
        }
    }

    /**
     * Executa os Seeders (spark db:seed)
     *
     */
    public function runSeed(string $key)
    {
        if (! $this->checkAuth($key)) {
            return $this->response->setStatusCode(403, 'Acesso Negado');
        }

        $seeder = Database::seeder();
        try {
            // Chama o seeder principal
            $seeder->call('DatabaseSeeder'); 
            echo "Seeders executados com sucesso!";
        } catch (\Throwable $e) {
            log_message('error', '[CronController] Erro ao executar seed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody($e->getMessage());
        }
    }

    /**
     * Executa o Refresh Token (spark ml:refresh-token)
     *
     */
    public function refreshToken(string $key)
    {
        if (! $this->checkAuth($key)) {
            return $this->response->setStatusCode(403, 'Acesso Negado');
        }

        log_message('info', '[CronController] Executando refresh-token via URL.');
        
        // Chama a mesma lógica do comando spark
        if (MercadoLivreAuth::refreshToken()) {
            echo "Token atualizado com sucesso!";
        } else {
            log_message('error', '[CronController] Falha ao atualizar o token via URL.');
            return $this->response->setStatusCode(500, 'Falha ao atualizar o token');
        }
    }
}