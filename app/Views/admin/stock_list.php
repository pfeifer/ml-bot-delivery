<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Gerenciar Códigos de Estoque<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Códigos de Estoque Cadastrados<?= $this->endSection() ?>

<?= $this->section('page_actions') ?>
    <a href="<?= route_to('admin.stock.new') ?>" 
       class="btn btn-success"
       data-bs-toggle="ajax-modal"
       data-title="Adicionar ao Estoque"
       data-modal-size="modal-lg">
        <i class="fa-solid fa-plus fa-fw"></i>
        Adicionar ao Estoque
    </a>
    
    <button type="submit" class="btn btn-outline-danger" id="deleteSelectedStockBtn" form="batchDeleteStockForm">
        <i class="fa-solid fa-trash fa-fw"></i>
        Excluir Selecionados
    </button>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<form method="POST" action="<?= route_to('admin.stock.delete.batch') ?>" id="batchDeleteStockForm">
    <?= csrf_field() ?>
    
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
    (function($) { // Wrapper para garantir que o $ (jQuery) esteja pronto
        
        // Define o objeto de linguagem PT-BR
        const dataTableLangPtBr = {
            "emptyTable": "Nenhum registro encontrado", "info": "Mostrando de _START_ até _END_ de _TOTAL_ registros", "infoEmpty": "Mostrando 0 até 0 de 0 registros", "infoFiltered": "(Filtrados de _MAX_ registros)", "infoThousands": ".", "loadingRecords": "Carregando...", "processing": "Processando...", "zeroRecords": "Nenhum registro encontrado", "search": "Pesquisar:",
            "paginate": { "first": "Primeiro", "last": "Último", "next": "Próximo", "previous": "Anterior" },
            "aria": { "sortAscending": ": Ativar para ordenar a coluna de forma ascendente", "sortDescending": ": Ativar para ordenar a coluna de forma descendente" }
        };

        // 1. Inicializa o DataTables
        var stockTable = null;
        if ($.fn.DataTable.isDataTable('#stockTable')) {
            stockTable = $('#stockTable').DataTable();
            stockTable.columns.adjust().draw();
        } else if ($('#stockTable').length > 0) {
            stockTable = new DataTable('#stockTable', {
                "columnDefs": [
                    {
                        "targets": 0, // A primeira coluna (checkbox)
                        "orderable": false,
                        "searchable": false
                    }
                ],
                "language": dataTableLangPtBr, // <-- CORREÇÃO APLICADA
                "order": [[ 1, "desc" ]] // Ordenar por ID (coluna 1) por padrão
            });
        }

        // 2. Lógica do "Selecionar Todos"
        $('#selectAllStock').on('click', function () {
            if (!stockTable) return;
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

        // =================================================================
        // INÍCIO DA CORREÇÃO: Adicionado .off() para limpar listeners antigos
        // =================================================================

        // 4. Lógica do botão "Excluir Selecionados"
        $(document).off('submit', '#batchDeleteStockForm').on('submit', '#batchDeleteStockForm', function(e) {
            e.preventDefault(); // Impede o envio imediato
            
            if (!stockTable) {
                showAlert('Por favor, selecione pelo menos um código disponível para excluir.', 'Atenção');
                return false;
            }
            
            const form = $(this); // Pega o formulário
            const selected = stockTable.rows().nodes().to$().find('.row-checkbox-stock:checked');
            
            if (selected.length === 0) {
                showAlert('Por favor, selecione pelo menos um código disponível para excluir.', 'Atenção');
                return false;
            }

            const msg = 'Tem certeza que deseja excluir os <strong>' + selected.length + '</strong> códigos selecionados?<br><br><small>APENAS códigos disponíveis (não vendidos) serão excluídos.</small>';
            
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
                            console.error("Erro ao processar recarga AJAX (delete stock):", e);
                            location.reload(); // Fallback
                        }
                    },
                    error: function() {
                        location.reload(); // Fallback em caso de erro
                    }
                });
            });
        });
        
        // =================================================================
        // FIM DA CORREÇÃO
        // =================================================================
        
    })(jQuery); // Fim do IIFE

    /**
     * Função global para scripts do modal (inalterada)
     */
    window.initModalScripts = function($modalBody) {
        
        const productSelect = $modalBody.find('#product_id_modal').get(0);
        const expiresAtField = $modalBody.find('#expires_at_field_modal').get(0);
        const expiresAtInput = $modalBody.find('#expires_at_modal').get(0);

        if (!productSelect || !expiresAtField || !expiresAtInput) {
            return;
        }

        function checkProductType() {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const productType = selectedOption ? selectedOption.getAttribute('data-type') : null;

            if (productType === 'code') {
                expiresAtField.style.display = 'block'; 
            } else {
                expiresAtField.style.display = 'none'; 
                expiresAtInput.value = ''; 
            }
        }
        checkProductType();
        productSelect.addEventListener('change', checkProductType);
    }
</script>
<?= $this->endSection() ?>