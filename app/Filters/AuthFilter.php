<?php namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Verifica se o usuário está logado antes de acessar rotas protegidas.
     * Redireciona para a página de login se não estiver logado.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Verifica se a chave 'admin_logged_in' existe e é true na sessão
        if (! $session->get('admin_logged_in')) {
            // Guarda a URL que o usuário tentou acessar para redirecionar após o login
            $session->set('redirect_url', current_url()); 

            // Redireciona para a página de login (rota nomeada 'login')
            return redirect()->route('login')->with('error', 'Você precisa fazer login para acessar esta área.');
        }
         // Se chegou aqui, o usuário está logado, permite o acesso
         return; 
    }

    /**
     * Não faz nada após a execução do controller.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nada a fazer aqui por enquanto
    }
}