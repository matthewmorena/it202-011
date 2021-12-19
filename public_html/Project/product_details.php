<?php 

require(__DIR__ . "/../../partials/nav.php");

$results = [];
$db = getDB();

$prod = $_POST['product'];
$stmt = $db->prepare("SELECT id, name, description, unit_price, stock, image FROM Products WHERE id = $prod");
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
<script>
    function cart(item, quantity) {
        let data = new FormData();
        data.append("item_id", item);
        data.append("quantity", quantity);
        fetch("api/cart_item.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: new URLSearchParams(Object.fromEntries(data))
            })
            .then(response => response.json())
            .then(data => {
                console.log('Success:', data);
                flash(data.message, "success");
            })
            .catch((error) => {
                console.error('Error:', error);
            });
    }
</script>
<div class="container-fluid">
    <h1>Product Details</h1>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($results as $item) : ?>
            <div class="col">
                <div class="card bg-light text-center">
                    <?php if (se($item, "image", "", false)) : ?>
                        <img src="<?php se($item, "image"); ?>" class="card-img-top" alt="...">
                    <?php endif; ?>
                </div>
            </div>
            <div class="col">
                <h3><?php se($item, "name"); ?></h3>
                <p>Description: <?php se($item, "description"); ?></p>
                <h5> Price: $<?php se($item, "unit_price"); ?> </h5>
                <div class="input-group">
                    <div class="input-group-text">Quantity</div>
                    <input class="form-control" type="number" id="quantity" name="quantity" min="1">
                    <button onclick="cart('<?php se($item, 'id'); ?>', document.getElementById('quantity').value)" class="btn btn-dark">Add to Cart</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
require(__DIR__ . "/../../partials/footer.php");
?>