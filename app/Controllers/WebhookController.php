<?php
namespace App\Controllers;

use CodeIgniter\API\ResponseTrait; // Para facilitar o envio de respostas HTTP

class WebhookController extends BaseController
{
    use ResponseTrait;

    public function handle()
    {
        // 1. Obter o corpo (body) da requisição POST (que é um JSON)
        $json = $this->request->getJSON();

        // 2. Logar a notificação recebida (MUITO útil para depuração)
        log_message('info', 'Webhook ML recebido: ' . json_encode($json));

        // 3. Verificar se é uma notificação de pedido
        if (isset($json->topic) && $json->topic === 'orders_v2') {

            // --- LÓGICA PRINCIPAL VIRÁ AQUI ---
            // a. Obter o ID do pedido ($json->resource -> /orders/ID_DO_PEDIDO)
            // b. Usar a SDK do Mercado Livre para buscar os detalhes do pedido pelo ID.
            // c. Verificar se o status do pagamento é 'approved'.
            // d. Pegar o ml_item_id do item vendido.
            // e. Usar ProductModel para encontrar o produto no seu DB.
            // f. Verificar o product_type:
            //    - Se 'unique_code': Usar StockCodeModel para pegar um código, marcar como vendido e guardar ml_order_id.
            //    - Se 'static_link': Pegar o delivery_data do ProductModel.
            // g. Usar a SDK do ML para enviar a mensagem com o código/link para o comprador.
            // h. Lidar com erros (sem estoque, falha na API, etc.).

            log_message('info', 'Notificação de pedido V2 processada (lógica pendente). Resource: ' . $json->resource);

        } else {
            log_message('warning', 'Webhook ML recebido com tópico desconhecido ou ausente.');
        }

        // 4. Responder ao Mercado Livre com HTTP 200 OK (OBRIGATÓRIO)
        return $this->respond(null, 200);
    }
}