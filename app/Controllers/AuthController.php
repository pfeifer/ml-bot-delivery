<?php namespace App\Controllers;

class AuthController extends BaseController
{
    /**
     * Mostra o formulário de login.
     */
    public function loginForm()
    {
        // Se já estiver logado, redireciona para o dashboard
        if (session()->get('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin/login'); // Carrega a view de login
    }

    /**
     * Tenta autenticar o usuário.
     */
    public function attemptLogin()
    {
         // Regras de validação básicas
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]', // Aumente o min_length na prática!
        ];

        if (! $this->validate($rules)) {
            // Se a validação falhar, volta para o formulário com os erros
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // --- Lógica de Autenticação (EXEMPLO SIMPLES) ---
        // !!! NA PRÁTICA: Busque o usuário no banco de dados pelo email
        // !!! e use password_verify() para comparar a senha com o hash armazenado.
        $adminEmail = 'admin@example.com'; // Coloque seu email aqui
        $adminPassword = 'password';       // Coloque sua senha aqui (NÃO FAÇA ISSO EM PRODUÇÃO!)

        if ($email === $adminEmail && $password === $adminPassword) {
            // Login bem-sucedido!
            session()->set([
                'admin_user_id' => 1, // Exemplo
                'admin_email' => $email,
                'admin_logged_in' => true,
            ]);

            // Redireciona para a URL original ou para o dashboard
            $redirectUrl = session()->get('redirect_url') ?? route_to('admin.dashboard');
            session()->remove('redirect_url'); // Limpa a URL de redirecionamento

            return redirect()->to($redirectUrl)->with('success', 'Login realizado com sucesso!');

        } else {
            // Login falhou
            return redirect()->route('login')->withInput()->with('error', 'Email ou senha inválidos.');
        }
        // --- Fim da Lógica de Autenticação ---
    }

    /**
     * Faz logout do usuário.
     */
    public function logout()
    {
        session()->destroy(); // Destroi todos os dados da sessão
        return redirect()->route('login')->with('success', 'Logout realizado com sucesso.');
    }
}