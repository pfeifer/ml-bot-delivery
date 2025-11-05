<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\MessageTemplateModel; // Importa o model

class DefaultMessageTemplateSeeder extends Seeder
{
    public function run()
    {
        $model = new MessageTemplateModel();
        
        $defaultId = 1;
        $defaultName = 'Template Padrão';
        $defaultContent = "Olá! Agradecemos por sua compra.\n\nSegue seu produto:\n\n{delivery_content}\n\nQualquer dúvida, estamos à disposição.";

        // Verifica se o ID=1 já existe
        if ($model->find($defaultId) === null) {
            
            // Se não existe, insere forçando o ID 1
            // Usamos o Query Builder para forçar o ID,
            // já que o Model por padrão protege o ID auto-incremento.
            $this->db->table('message_templates')->insert([
                'id'      => $defaultId,
                'name'    => $defaultName,
                'content' => $defaultContent,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            echo "-> [SUCESSO] Template Padrão (ID: 1) foi criado.\n";
        } else {
            // Se ID=1 já existe, apenas atualiza o nome e conteúdo
            // para garantir que estejam corretos
            $this->db->table('message_templates')->where('id', $defaultId)->update([
                'name'    => $defaultName,
                'content' => $defaultContent,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo "-> [INFO] Template Padrão (ID: 1) já existe e foi verificado/atualizado.\n";
        }

        // Tenta resetar o auto-incremento para ser MAIOR que 1
        // (Isso pode variar entre DBs, mas para MySQL/MariaDB funciona)
        try {
             $maxId = $this->db->table('message_templates')->selectMax('id')->get()->getRow()->id;
             $nextId = ($maxId ?? 1) + 1;
             $this->db->query("ALTER TABLE message_templates AUTO_INCREMENT = {$nextId}");
             echo "-> [INFO] Auto-incremento da tabela 'message_templates' definido para {$nextId}.\n";
        } catch (\Throwable $e) {
             echo "-> [AVISO] Não foi possível redefinir o auto-incremento (normal se estiver usando SQLite): " . $e->getMessage() . "\n";
        }
    }
}