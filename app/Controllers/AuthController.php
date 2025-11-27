<?php
namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\I18n\Time; // Importante para lidar com datas

class AuthController extends BaseController
{
    // MELHORIA: Adicionado __construct
    public function __construct()
    {
        helper(['form', 'url']);
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
    /**
     * Exibe o formulário onde o usuário digita o e-mail
     */
    public function forgotPasswordForm()
    {
        return view('admin/forgot_password');
    }
    /**
     * Processa o e-mail, gera o token e envia o link
     */
    public function sendResetLink()
    {
        $rules = ['email' => 'required|valid_email'];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        $userModel = model(UserModel::class);
        $user = $userModel->where('email', $email)->first();
        // Se o usuário não existir, não damos erro para evitar enumeração de e-mails
        if (!$user) {
            return redirect()->route('forgot_password')->with('success', 'Se este e-mail estiver cadastrado, enviaremos um link de redefinição.');
        }
        // Gera token seguro e data de expiração (1 hora)
        $token = bin2hex(random_bytes(20));
        $expiresAt = Time::now()->addHour();
        // Salva no banco
        $userModel->update($user->id, [
            'reset_hash' => $token,
            'reset_expires_at' => $expiresAt->toDateTimeString()
        ]);
        // Envia o E-mail
        $this->sendEmail($user->email, $token);

        return redirect()->route('forgot_password')->with('success', 'Se este e-mail estiver cadastrado, enviaremos um link de redefinição.');
    }
    /**
     * Tela para digitar a nova senha (acessada via link do e-mail)
     */
    public function resetPasswordForm($token)
    {
        $userModel = model(UserModel::class);
        // Busca usuário com esse token E que o token ainda não tenha expirado
        $user = $userModel->where('reset_hash', $token)
            ->where('reset_expires_at >=', date('Y-m-d H:i:s'))
            ->first();

        if (!$user) {
            return redirect()->route('forgot_password')->with('error', 'Link inválido ou expirado. Solicite um novo.');
        }

        return view('admin/reset_password', ['token' => $token]);
    }
    /**
     * Salva a nova senha no banco
     */
    public function updatePassword()
    {
        $rules = [
            'token' => 'required',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'matches[password]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');

        $userModel = model(UserModel::class);
        $user = $userModel->where('reset_hash', $token)
            ->where('reset_expires_at >=', date('Y-m-d H:i:s'))
            ->first();

        if (!$user) {
            return redirect()->route('forgot_password')->with('error', 'Link inválido ou expirado.');
        }
        // Atualiza a senha, limpa o hash e a data de expiração
        $userModel->update($user->id, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'reset_hash' => null,
            'reset_expires_at' => null
        ]);

        return redirect()->route('login')->with('success', 'Senha atualizada com sucesso! Faça login.');
    }
    /**
     * Método privado para envio de e-mail
     */
    private function sendEmail($to, $token)
    {
        $email = \Config\Services::email();
        $resetLink = route_to('reset_password', $token);
        // Configurações básicas
        $email->setFrom(env('email.fromEmail', 'noreply@seusite.com'), env('email.fromName', 'ML Bot Admin'));
        $email->setTo($to);
        $email->setSubject('Redefinição de Senha - ML Bot');

        $message = "Olá,<br><br>";
        $message .= "Você solicitou a redefinição de sua senha.<br>";
        $message .= "Clique no link abaixo para criar uma nova senha:<br><br>";
        $message .= "<a href='" . site_url($resetLink) . "'>Redefinir Minha Senha</a><br><br>";
        $message .= "Este link expira em 1 hora.";

        $email->setMessage($message);

        if (!$email->send()) {
            // Loga o erro se falhar, útil para debug (veja em writable/logs)
            log_message('error', 'Email Error: ' . $email->printDebugger(['headers']));
        }
    }
}