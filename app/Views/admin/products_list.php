<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Gerenciar Produtos<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Produtos Cadastrados<?= $this->endSection() ?>

<?= $this->section('content') ?>

<form method="POST" action="<?= route_to('admin.products.delete.batch') ?>" id="batchDeleteForm">
    <?= csrf_field() ?>
    
    <div class="mb-3">
        <a href="<?= route_to('admin.products.new') ?>" 
           class="btn btn-success" 
           data-bs-toggle="ajax-modal" 
           data-title="Adicionar Novo Produto"
           data-modal-size="modal-lg"> <i class="fa-solid fa-plus fa-fw"></i>
            Adicionar Produto
        </a>

        <button type="button" class="btn btn-outline-primary" 
                id="editSelectedBtnModal" 
                data-bs-toggle="ajax-modal" 
                data-title="Editar Produto"
                data-modal-size="modal-lg">
            <i class="fa-solid fa-pencil fa-fw"></i>
            Editar Selecionado
        </button>
        
        <button type="submit" class="btn btn-outline-danger" id="deleteSelectedBtn">
            <i class="fa-solid fa-trash fa-fw"></i>
            Excluir Selecionados
        </button>
    </div>

    <?php if (!empty($products) && is_array($products)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="productsTable">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 20px;">
                            <input class="form-check-input" type="checkbox" id="selectAllProducts">
                        </th>
                        <th>ID</th>
                        <th>ML Item ID</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th style="width: 50px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <input class="form-check-input row-checkbox" type="checkbox" name="selected_ids[]" value="<?= esc($product->id) ?>">
                            </td>
                            <td><?= esc($product->id) ?></td>
                            <td><?= esc($product->ml_item_id) ?></td>
                            <td><?= esc($product->title) ?></td>
                            <td>
                                <span class="badge <?= ($product->product_type === 'code') ? 'bg-primary' : 'bg-info' ?>">
                                    <?= esc($product->product_type === 'code' ? 'Código Único' : 'Link Estático') ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= route_to('admin.products.edit', $product->id) ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="ajax-modal"
                                   data-title="Editar Produto #<?= esc($product->id) ?>"
                                   data-modal-size="modal-lg"
                                   title="Editar">
                                    <i class="fa-solid fa-pencil fa-fw"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning" role="alert">
            Nenhum produto cadastrado ainda.
        </div>
    <?php endif; ?>

</form> <?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        // 1. Inicializa o DataTables
        const productsTable = new DataTable('#productsTable', {
            "columnDefs": [
                {
                    "targets": [0, 5], // A primeira (checkbox) e última (Ações)
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
        $('#selectAllProducts').on('click', function () {
            const isChecked = $(this).prop('checked');
            productsTable.rows().nodes().to$().find('.row-checkbox').prop('checked', isChecked);
        });

        // 3. Lógica para desmarcar o "Selecionar Todos"
        $('#productsTable tbody').on('change', '.row-checkbox', function () {
            if (!$(this).prop('checked')) {
                $('#selectAllProducts').prop('checked', false);
            }
        });

        // 4. Lógica do botão "Editar Selecionado" (para o modal)
        // Usamos 'mousedown' para definir o data-url ANTES do script do modal ser disparado pelo 'click'
        $('#editSelectedBtnModal').on('mousedown', function(e) {
            const selected = productsTable.rows().nodes().to$().find('.row-checkbox:checked');
            
            if (selected.length === 0) {
                // MODIFICADO: Usa a nova função showAlert
                showAlert('Por favor, selecione um produto para editar.', 'Atenção');
                e.stopImmediatePropagation(); // Impede o 'click' (e o modal) de disparar
                return false;
            }
            if (selected.length > 1) {
                // MODIFICADO: Usa a nova função showAlert
                showAlert('Você só pode editar um produto por vez.', 'Atenção');
                e.stopImmediatePropagation(); // Impede o 'click' (e o modal) de disparar
                return false;
            }
            
            const productId = selected.val();
            const editUrl = '<?= rtrim(route_to('admin.products.edit', 1), '1') ?>' + productId;
            // Define o URL no atributo data-url para o script global pegar
            $(this).attr('data-url', editUrl); 
        });

        // 5. Lógica do botão "Excluir Selecionados" (MODIFICADO)
        $('#batchDeleteForm').on('submit', function(e) {
            e.preventDefault(); // Impede o envio imediato
            const form = $(this);
            const selected = productsTable.rows().nodes().to$().find('.row-checkbox:checked');
            
            if (selected.length === 0) {
                showAlert('Por favor, selecione pelo menos um produto para excluir.', 'Atenção');
                return false;
            }
            
            // Se a validação passar, mostra o modal de confirmação
            const msg = 'Tem certeza que deseja excluir os <strong>' + selected.length + '</strong> produtos selecionados?';
            showConfirm(msg, 'Confirmar Exclusão', function() {
                form.get(0).submit(); // Envia o formulário de verdade
            });
        });
    });
</script>
<?= $this->endSection() ?>