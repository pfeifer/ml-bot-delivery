<?= $this->extend('admin/template') ?>

<?= $this->section('title') ?>Gerenciar Produtos<?= $this->endSection() ?>

<?= $this->section('page_title') ?>Produtos Cadastrados<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= route_to('admin.products.new') ?>" class="btn btn-success">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg"
            viewBox="0 0 16 16">
            <path fill-rule="evenodd"
                d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
        </svg>
        Adicionar Produto
    </a>
</div>

<?php if (!empty($products) && is_array($products)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>ML Item ID</th>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= esc($product->id) ?></td>
                        <td><?= esc($product->ml_item_id) ?></td>
                        <td><?= esc($product->title) ?></td>
                        <td>
                            <span class="badge <?= ($product->product_type === 'unique_code') ? 'bg-primary' : 'bg-info' ?>">
                                <?= esc($product->product_type === 'unique_code' ? 'Código Único' : 'Link Estático') ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= route_to('admin.products.edit', $product->id) ?>"
                                class="btn btn-sm btn-outline-secondary">Editar</a>
                            <a href="<?= route_to('admin.products.delete', $product->id) ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Tem certeza que deseja excluir este produto?');">Excluir</a>
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

<?= $this->endSection() ?>