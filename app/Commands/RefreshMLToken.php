<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\MercadoLivreAuth;

class RefreshMLToken extends BaseCommand
{
    protected $group = 'MercadoLivre';
    protected $name = 'ml:refresh-token';
    protected $description = 'Atualiza o Access Token do Mercado Livre usando o Refresh Token.';

    public function run(array $params)
    {
        CLI::write('Tentando atualizar o token do Mercado Livre...', 'yellow');

        if (MercadoLivreAuth::refreshToken()) {
            CLI::write('Token atualizado com sucesso!', 'green');
            return 0; // Retorna 0 para indicar sucesso para o terminal/cron
        } else {
            CLI::error('Falha ao atualizar o token. Verifique os logs.');
            return 1; // Retorna 1 para indicar erro para o terminal/cron
        }
    }
}