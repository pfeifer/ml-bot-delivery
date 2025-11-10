<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Gerenciar Produtos<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Produtos Cadastrados<?= $this->endSection() ?>

<?= $this->section('page_actions') ?>
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
    
    <button type="submit" class="btn btn-outline-danger" id="deleteSelectedBtn" form="batchDeleteForm">
        <i class="fa-solid fa-trash fa-fw"></i>
        Excluir Selecionados
    </button>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<form method="POST" action="<?= route_to('admin.products.delete.batch') ?>" id="batchDeleteForm">
    <?= csrf_field() ?>
    
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
    (function($) { // Wrapper para garantir que o $ (jQuery) esteja pronto
        
        // Define o objeto de linguagem PT-BR
        const dataTableLangPtBr = {
            "emptyTable": "Nenhum registro encontrado", "info": "Mostrando de _START_ até _END_ de _TOTAL_ registros", "infoEmpty": "Mostrando 0 até 0 de 0 registros", "infoFiltered": "(Filtrados de _MAX_ registros)", "infoThousands": ".", "loadingRecords": "Carregando...", "processing": "Processando...", "zeroRecords": "Nenhum registro encontrado", "search": "Pesquisar:",
            "paginate": { "first": "Primeiro", "last": "Último", "next": "Próximo", "previous": "Anterior" },
            "aria": { "sortAscending": ": Ativar para ordenar a coluna de forma ascendente", "sortDescending": ": Ativar para ordenar a coluna de forma descendente" }
        };

        // 1. Inicializa o DataTables
        var productsTable = null;
        if ($.fn.DataTable.isDataTable('#productsTable')) {
            productsTable = $('#productsTable').DataTable();
            productsTable.columns.adjust().draw();
        } else if ($('#productsTable').length > 0) { // Garante que a tabela exista
             productsTable = new DataTable('#productsTable', {
                "columnDefs": [
                    {
                        "targets": [0, 5], // A primeira (checkbox) e última (Ações)
                        "orderable": false,
                        "searchable": false
                    }
                ],
                "language": dataTableLangPtBr, 
                "order": [[ 1, "desc" ]] // Ordenar por ID (coluna 1) por padrão
            });
        }

        // 2. Lógica do "Selecionar Todos" (listener direto, não precisa de .off())
        $('#selectAllProducts').on('click', function () {
            if (!productsTable) return;
            const isChecked = $(this).prop('checked');
            productsTable.rows().nodes().to$().find('.row-checkbox').prop('checked', isChecked);
        });

        // 3. Lógica para desmarcar o "Selecionar Todos" (listener direto)
        $('#productsTable tbody').on('change', '.row-checkbox', function () {
            if (!$(this).prop('checked')) {
                $('#selectAllProducts').prop('checked', false);
            }
        });

        // =================================================================
        // INÍCIO DA CORREÇÃO: Adicionado .off() para limpar listeners antigos
        // =================================================================

        // 4. Lógica do botão "Editar Selecionado"
        $(document).off('mousedown', '#editSelectedBtnModal').on('mousedown', '#editSelectedBtnModal', function(e) {
            if (!productsTable) return;
            const selected = productsTable.rows().nodes().to$().find('.row-checkbox:checked');
            
            if (selected.length === 0) {
                showAlert('Por favor, selecione um produto para editar.', 'Atenção');
                e.stopImmediatePropagation(); 
                return false;
            }
            if (selected.length > 1) {
                showAlert('Você só pode editar um produto por vez.', 'Atenção');
                e.stopImmediatePropagation(); 
                return false;
            }
            
            const productId = selected.val();
            const editUrl = '<?= rtrim(route_to('admin.products.edit', 1), '1') ?>' + productId;
            $(this).attr('data-url', editUrl); 
        });

        // 5. Lógica do botão "Excluir Selecionados"
        $(document).off('submit', '#batchDeleteForm').on('submit', '#batchDeleteForm', function(e) {
            e.preventDefault(); 
            
            if (!productsTable) {
                showAlert('Por favor, selecione pelo menos um produto para excluir.', 'Atenção');
                return false;
            }

            const form = $(this);
            const selected = productsTable.rows().nodes().to$().find('.row-checkbox:checked');
            
            if (selected.length === 0) {
                showAlert('Por favor, selecione pelo menos um produto para excluir.', 'Atenção');
                return false;
            }
            
            const msg = 'Tem certeza que deseja excluir os <strong>' + selected.length + '</strong> produtos selecionados?';
            
            showConfirm(msg, 'Confirmar Exclusão', function() {
                // Mostra spinner no local
                $('#page-content-container').html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>');
                $('#alert-container').empty(); // Limpa alertas antigos

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'html', 
                    
                    success: function(responseHtml) {
                        try {
                            var $newHtml = $('<div>').html(responseHtml);
                            
                            // Extrai os novos blocos
                            var newAlerts = $newHtml.find('#alert-container').html();
                            var newPageContent = $newHtml.find('#page-content-container').html();
                            var newScripts = $newHtml.find('#ajax-scripts').html();
                            var newPageTitle = $newHtml.find('title').text();
                            var newH1Title = $newHtml.find('#page-title-h1').html();
                            var newPageActions = $newHtml.find('#page-action-buttons').html();


                            if (newPageContent !== undefined && newAlerts !== undefined) {
                                // Substitui apenas os blocos
                                $('#alert-container').html(newAlerts);
                                $('#page-content-container').html(newPageContent);
                                
                                // ATUALIZA TÍTULO E BOTÕES
                                if (newH1Title) $('#page-title-h1').html(newH1Title);
                                if (newPageActions) $('#page-action-buttons').html(newPageActions);
                                if (newPageTitle) document.title = newPageTitle;
                                
                                // Recarrega os scripts
                                $('#ajax-scripts-container').empty().html(newScripts ? '<div id="ajax-scripts">' + newScripts + '</div>' : '');
                                
                                $('#main-content-wrapper').scrollTop(0);
                            } else {
                                location.reload(); // Fallback
                            }
                        } catch(e) {
                            console.error("Erro ao processar recarga AJAX (delete):", e);
                            location.reload(); // Fallback
                        }
                    },
                    error: function() {
                        location.reload(); 
                    }
                });
            });
        });

        // =================================================================
        // FIM DA CORREÇÃO
        // =================================================================
        
    })(jQuery); // Fim do IIFE
</script>
<?= $this->endSection() ?>