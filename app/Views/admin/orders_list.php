<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Gerenciamento de Pedidos<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Gerenciamento de Pedidos<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="alert alert-info" role="alert">
    <i class="fa-solid fa-circle-info fa-fw"></i>
    Esta página lista todos os pedidos recebidos pelo Webhook. O status do ML (Pago, Pendente, etc.) é atualizado automaticamente.
</div>


<div class="table-responsive">
    <table class="table table-striped table-hover" id="ordersTable">
        <thead class="table-dark">
            <tr>
                <th>Data Criação (ML)</th>
                <th>ID Pedido (ML)</th>
                <th>Produto</th>
                <th>Status (ML)</th>
                <th>Status Entrega (Robô)</th>
                <th>Valor</th>
                <th>ID Comprador (ML)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders) && is_array($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= esc($order->date_created ? date('d/m/Y H:i', strtotime($order->date_created)) : '---') ?></td>
                        <td><?= esc($order->ml_order_id) ?></td>
                        <td>
                            <?php if ($order->product_id): ?>
                                <a href="<?= route_to('admin.products.edit', $order->product_id) ?>" 
                                   title="Editar Produto: <?= esc($order->title) ?>"
                                   data-bs-toggle="ajax-modal"
                                   data-title="Editar Produto: <?= esc($order->title) ?>"
                                   data-modal-size="modal-lg">
                                    <?= esc($order->title ?: $order->ml_item_id) ?>
                                </a>
                            <?php else: ?>
                                <span title="Produto não cadastrado no sistema (ML ID: <?= esc($order->ml_item_id) ?>)">
                                    <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                                    <?= esc($order->ml_item_id) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $statusML = esc($order->status);
                                $badgeClassML = 'bg-secondary';
                                if ($statusML === 'paid') {
                                    $badgeClassML = 'bg-success';
                                } elseif ($statusML === 'payment_in_process' || $statusML === 'partially_paid') {
                                    $badgeClassML = 'bg-warning text-dark';
                                } elseif ($statusML === 'cancelled' || $statusML === 'invalid') {
                                    $badgeClassML = 'bg-danger';
                                }
                            ?>
                            <span class="badge <?= $badgeClassML ?>"><?= ucfirst($statusML) ?></span>
                        </td>
                        <td>
                             <?php
                                $statusDelivery = esc($order->delivery_status);
                                $badgeClassDelivery = 'bg-secondary';
                                if ($statusDelivery === 'delivered') {
                                    $badgeClassDelivery = 'bg-success';
                                } elseif ($statusDelivery === 'pending' && $order->status === 'paid') {
                                    $badgeClassDelivery = 'bg-info text-dark';
                                    $statusDelivery = 'Processando';
                                } elseif ($statusDelivery === 'failed') {
                                    $badgeClassDelivery = 'bg-danger';
                                    $statusDelivery = 'Falhou';
                                } elseif ($statusDelivery === 'out_of_stock') {
                                    $badgeClassDelivery = 'bg-danger';
                                    $statusDelivery = 'Sem Estoque!';
                                } elseif ($statusDelivery === 'pending') {
                                    $statusDelivery = 'Aguardando Pag.';
                                }
                            ?>
                            <span class="badge <?= $badgeClassDelivery ?>"><?= ucfirst($statusDelivery) ?></span>
                        </td>
                        <td><?= esc($order->currency_id) ?> <?= esc(number_format($order->total_amount, 2, ',', '.')) ?></td>
                        <td><?= esc($order->ml_buyer_id) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        // Inicializa o DataTables
        const ordersTable = new DataTable('#ordersTable', {
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json"
            },
            "order": [[ 0, "desc" ]] // Ordenar por Data de Criação (coluna 0) por padrão
        });
    });
</script>
<?= $this->endSection() ?>