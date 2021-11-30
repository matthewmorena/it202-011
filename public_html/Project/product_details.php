<?php 

require(__DIR__ . "/../../partials/nav.php");

$results = [];
$db = getDB();

$prod = $_POST['product'];
$stmt = $db->prepare("SELECT id, name, description, cost, stock, image FROM Products WHERE id = $prod");
try {
    $stmt->execute();
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    flash("<pre>" . var_export($e, true) . "</pre>");
}
?>
<div class="container-fluid">
    <h1>Shop</h1>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($results as $item) : ?>
            <div class="col">
                <div class="card bg-light text-center">
                    <div class="card-header">
                        <h5 class="card-title"><?php se($item, "name"); ?></h5>
                    </div>
                    <?php if (se($item, "image", "", false)) : ?>
                        <img src="<?php se($item, "image"); ?>" class="card-img-top" alt="...">
                    <?php endif; ?>

                    <div class="card-body">
                        <p class="card-text">Description: <?php se($item, "description"); ?></p>
                    </div>
                    <div class="card-footer">
                        Cost: $<?php se($item, "cost"); ?>
                        <form method="POST" action="product_details.php">
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" min="1">
                            <button class="btn btn-dark" name="product" value="<?php se($item, "id"); ?>" >Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
require(__DIR__ . "/../../partials/footer.php");
?>