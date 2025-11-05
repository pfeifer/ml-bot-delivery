<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Gerenciar Códigos de Estoque<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Códigos de Estoque Cadastrados<?= $this->endSection() ?>

<?= $this->section('content') ?>

<form method="POST" action="<?= route_to('admin.stock.delete.batch') ?>" id="batchDeleteStockForm">
    <?= csrf_field() ?>
    
    <div class="mb-3">
        <a href="<?= route_to('admin.stock.new') ?>" 
           class="btn btn-success"
           data-bs-toggle="ajax-modal"
           data-title="Adicionar ao Estoque"
           data-modal-size="modal-lg">
            <i class="fa-solid fa-plus fa-fw"></i>
            Adicionar ao Estoque
        </a>
        
        <button type="submit" class="btn btn-outline-danger" id="deleteSelectedStockBtn" 
                onclick="return confirm('Tem certeza que deseja excluir os códigos selecionados?\n\nAPENAS códigos disponíveis (não vendidos) serão excluídos.');">
            <i class="fa-solid fa-trash fa-fw"></i>
            Excluir Selecionados
        </button>
    </div>

    <div class="alert alert-info" role="alert">
        <i class="fa-solid fa-circle-info fa-fw"></i>
        Por segurança, os códigos em si não são exibidos. Apenas códigos <strong>Disponíveis</strong> podem ser selecionados e excluídos.
    </div>


    <div class="table-responsive">
        <table class="table table-striped table-hover" id="stockTable">
            <thead class="table-dark">
                <tr>
                    <th style="width: 20px;">
                        <input class="form-check-input" type="checkbox" id="selectAllStock">
                    </th>
                    <th>ID</th>
                    <th>Produto</th>
                    <th>Status</th>
                    <th>Data Venda</th>
                    <th>Data Expira</th>
                    <th>Data Cadastro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($codes) && is_array($codes)): ?>
                    <?php foreach ($codes as $code): ?>
                        <tr>
                            <td>
                                <input class="form-check-input row-checkbox-stock" type="checkbox" 
                                       name="selected_ids[]" value="<?= esc($code->id) ?>"
                                       <?= ($code->is_sold) ? 'disabled' : '' // Desabilita checkbox se já foi vendido ?>>
                            </td>
                            <td><?= esc($code->id) ?></td>
                            <td>
                                <a href="<?= route_to('admin.products.edit', $code->product_id) ?>" 
                                   title="Editar Produto: <?= esc($code->title) ?>"
                                   data-bs-toggle="ajax-modal"
                                   data-title="Editar Produto: <?= esc($code->title) ?>"
                                   data-modal-size="modal-lg">
                                    <?= esc($code->title ?: $code->ml_item_id) ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($code->is_sold): ?>
                                    <span class="badge bg-danger" title="Pedido ML: <?= esc($code->ml_order_id ?? 'N/A') ?>">Vendido</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Disponível</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($code->sold_at ? date('d/m/Y H:i', strtotime($code->sold_at)) : '---') ?></td>
                            <td><?= esc($code->expires_at ? date('d/m/Y', strtotime($code->expires_at)) : '---') ?></td>
                            <td><?= esc($code->created_at ? date('d/m/Y H:i', strtotime($code->created_at)) : '---') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</form> <?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        // 1. Inicializa o DataTables
        const stockTable = new DataTable('#stockTable', {
            "columnDefs": [
                {
                    "targets": 0, // A primeira coluna (checkbox)
                    "orderable": false,
                    "searchable": false
                }
            ],
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json"
            },
            "order": [[ 1, "desc" ]] // Ordenar por ID (coluna 1) por padrão
        });

        // 2. Lógica do "Selecionar Todos"
        $('#selectAllStock').on('click', function () {
            const isChecked = $(this).prop('checked');
            // Seleciona todos os checkboxes na tabela (exceto os desabilitados)
            stockTable.rows().nodes().to$().find('.row-checkbox-stock:not(:disabled)').prop('checked', isChecked);
        });

        // 3. Lógica para desmarcar o "Selecionar Todos"
        $('#stockTable tbody').on('change', '.row-checkbox-stock', function () {
            if (!$(this).prop('checked')) {
                $('#selectAllStock').prop('checked', false);
            }
        });

        // 4. Lógica do botão "Excluir Selecionados"
        $('#batchDeleteStockForm').on('submit', function(e) {
            const selected = stockTable.rows().nodes().to$().find('.row-checkbox-stock:checked');
            if (selected.length === 0) {
                alert('Por favor, selecione pelo menos um código disponível para excluir.');
                e.preventDefault(); // Impede o envio do formulário
                return false;
            }
        });
    });

    /**
     * ADICIONADO: Função global que o template.js procura
     * para inicializar scripts dentro de um modal recém-carregado.
     */
    window.initModalScripts = function($modalBody) {
        
        // Encontra os elementos *dentro* do corpo do modal
        const productSelect = $modalBody.find('#product_id_modal').get(0);
        const expiresAtField = $modalBody.find('#expires_at_field_modal').get(0);
        const expiresAtInput = $modalBody.find('#expires_at_modal').get(0);

        // Se não encontrar os elementos (ex: abriu o modal de "Editar Produto"), não faz nada
        if (!productSelect || !expiresAtField || !expiresAtInput) {
            return;
        }

        function checkProductType() {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const productType = selectedOption ? selectedOption.getAttribute('data-type') : null;

            if (productType === 'code') {
                expiresAtField.style.display = 'block'; // Mostra o campo
            } else {
                expiresAtField.style.display = 'none'; // Esconde o campo
                expiresAtInput.value = ''; // Limpa o valor se não for código único
            }
        }
        // Verifica no carregamento do modal
        checkProductType();
        // Adiciona listener para mudanças no select
        productSelect.addEventListener('change', checkProductType);
    }
</script>
<?= $this->endSection() ?>