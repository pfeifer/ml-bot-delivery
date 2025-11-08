<?php
namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    // MELHORIA: Adicionado __construct
    public function __construct()
    {
        helper('form');
    }

    /**
     * Mostra o formulário de login.
     */
    public function loginForm()
    {
        // helper('form'); // Movido para __construct
        
        // Se já estiver logado, redireciona para o dashboard
        if (session()->get('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin/login'); // Carrega a view de login
    }

    /**
     * Tenta autenticar o utilizador usando o banco de dados.
     */
    public function attemptLogin()
    {
        // 1. Regras de validação
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            // Se a validação falhar, volta para o formulário com os erros
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // 2. Pega os dados do formulário
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // 3. Busca o utilizador no banco de dados pelo email
        // MELHORIA: Usa a função model() ao invés de 'new'
        $userModel = model(UserModel::class);
        $user = $userModel->where('email', $email)->first();

        // 4. Verifica se o utilizador existe E se a senha está correta
        if ($user && password_verify($password, $user->password_hash)) {
            // Utilizador encontrado e senha correta!
            // 5. Cria a sessão do administrador
            session()->set([
                'admin_user_id' => $user->id,
                'admin_email' => $user->email,
                'admin_first_name' => $user->first_name,
                'admin_logged_in' => true,
            ]);

            // 6. Redireciona para o painel ou URL anterior
            $redirectUrl = session()->get('redirect_url') ?? route_to('admin.dashboard');
            session()->remove('redirect_url');

            return redirect()->to($redirectUrl)->with('success', 'Login realizado com sucesso!');

        } else {
            // Utilizador não encontrado ou senha incorreta
            return redirect()->route('login')->withInput()->with('error', 'Email ou senha inválidos.');
        }
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