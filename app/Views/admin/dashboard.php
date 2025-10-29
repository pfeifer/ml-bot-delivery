<?= $this->extend('admin/template') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('page_title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="alert alert-info" role="alert">
    Bem-vindo ao painel administrativo do Mercado Livre Bot Delivery!
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Produtos Cadastrados</h5>
                <p class="card-text fs-3"><?= esc($product_count ?? '0') // Exibe a contagem ou 0 se não existir ?></p>
                <a href="<?= route_to('admin.products') ?>" class="btn btn-sm btn-outline-primary">Ver Produtos</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Códigos Disponíveis</h5>
                <p class="card-text fs-3"><?= esc($stock_count ?? '0') // Exibe a contagem ou 0 se não existir ?></p>
                <a href="<?= route_to('admin.stock') ?>" class="btn btn-sm btn-outline-primary">Gerenciar Estoque</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>