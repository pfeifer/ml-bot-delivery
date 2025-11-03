<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Gerenciar Produtos<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Produtos Cadastrados<?= $this->endSection() ?>

<?= $this->section('content') ?>

<form method="POST" action="<?= route_to('admin.products.delete.batch') ?>" id="batchDeleteForm">
    <?= csrf_field() ?>
    
    <div class="mb-3">
        <a href="<?= route_to('admin.products.new') ?>" class="btn btn-success">
            <i class="fa-solid fa-plus fa-fw"></i>
            Adicionar Produto
        </a>
        <button type="button" class="btn btn-outline-primary" id="editSelectedBtn">
            <i class="fa-solid fa-pencil fa-fw"></i>
            Editar Selecionado
        </button>
        <button type="submit" class="btn btn-outline-danger" id="deleteSelectedBtn" 
                onclick="return confirm('Tem certeza que deseja excluir os produtos selecionados?');">
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
                                <span class="badge <?= ($product->product_type === 'unique_code') ? 'bg-primary' : 'bg-info' ?>">
                                    <?= esc($product->product_type === 'unique_code' ? 'Código Único' : 'Link Estático') ?>
                                </span>
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
                    "targets": 0, // A primeira coluna (checkbox)
                    "orderable": false, // Não permite ordenar por ela
                    "searchable": false // Não permite buscar nela
                }
            ],
            "language": { // Opcional: Tradução
                "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json"
            },
            "order": [[ 1, "desc" ]] // Opcional: Ordenar por ID (coluna 1) por padrão
        });

        // 2. Lógica do "Selecionar Todos"
        $('#selectAllProducts').on('click', function () {
            const isChecked = $(this).prop('checked');
            // Seleciona todos os checkboxes na tabela (mesmo em outras páginas do DT)
            productsTable.rows().nodes().to$().find('.row-checkbox').prop('checked', isChecked);
        });

        // 3. Lógica para desmarcar o "Selecionar Todos" se um item for desmarcado
        $('#productsTable tbody').on('change', '.row-checkbox', function () {
            if (!$(this).prop('checked')) {
                $('#selectAllProducts').prop('checked', false);
            }
        });

        // 4. Lógica do botão "Editar Selecionado"
        $('#editSelectedBtn').on('click', function() {
            // Busca checkboxes marcados na tabela
            const selected = productsTable.rows().nodes().to$().find('.row-checkbox:checked');
            
            if (selected.length === 0) {
                alert('Por favor, selecione um produto para editar.');
                return;
            }
            if (selected.length > 1) {
                alert('Você só pode editar um produto por vez.');
                return;
            }
            
            const productId = selected.val();
            // Gera a URL de edição e redireciona
            const editUrl = '<?= rtrim(route_to('admin.products.edit', 1), '1') ?>' + productId;
            window.location.href = editUrl;
        });

        // 5. Lógica do botão "Excluir Selecionados"
        $('#batchDeleteForm').on('submit', function(e) {
            const selected = productsTable.rows().nodes().to$().find('.row-checkbox:checked');
            if (selected.length === 0) {
                alert('Por favor, selecione pelo menos um produto para excluir.');
                e.preventDefault(); // Impede o envio do formulário
                return false;
            }
            // A confirmação 'onclick' já está no botão
        });
    });
</script>
<?= $this->endSection() ?>