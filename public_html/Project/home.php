<?php
    require(__DIR__ . "/../../partials/nav.php");
?>
<?php
if (!is_logged_in()) {
    flash("You are not logged in!", "warning");
    redirect("$BASE_PATH" . "login.php");
}

$results = [];
$item_categories = [];
$db = getDB();

$query = "SELECT distinct Products.id, Products.name, Products.description, Products.unit_price, Products.stock, Products.category, Products.image, Products.rating from Orders JOIN OrderItems on OrderItems.order_id = Orders.id JOIN Products on Products.id = OrderItems.product_id";
$query .= " WHERE stock > 0 AND visibility = 1 AND Orders.user_id = :uid";

$params = []; //define default params, add keys as needed and pass to execute
$params[":uid"] = get_user_id();
$query .= " LIMIT 6";

$stmt = $db->prepare($query);
//we'll want to convert this to use bindValue so ensure they're integers so lets map our array
foreach ($params as $key => $value) {
    $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $type);
}
$params = null; //set it to null to avoid issues
//$stmt = $db->prepare("SELECT id, name, description, unit_price, stock, image FROM Products WHERE stock > 0 AND visibility = 1 LIMIT 10");
try {
    $stmt->execute($params);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    //flash("<pre>" . var_export($e, true) . "</pre>");
    flash("We had some problems processing your request, please try again.", "danger");
}

$pop_items = [];
$popular = "SELECT id, name, description, unit_price, stock, category, image, rating FROM Products p join (SELECT product_id, COUNT(product_id) AS `value_occurrence` FROM OrderItems GROUP BY product_id ORDER BY `value_occurrence` DESC) as t on t.product_id = p.id WHERE stock > 0 AND visibility = 1 ORDER BY value_occurrence DESC LIMIT 6";

$stmt = $db->prepare($popular);
try {
    $stmt->execute($params);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $pop_items = $r;
    }
} catch (PDOException $e) {
    //flash("<pre>" . var_export($e, true) . "</pre>");
    flash("We had some problems processing your request, please try again.", "danger");
}

?>
<div class="container-fluid">
    <h1>Welcome home, <?php echo get_username() ?>!</h1>
    <h5>Popular Items:</h5>
    <div class="row row-cols-1 row-cols-md-5 g-4">
        <?php if (empty($pop_items)) : ?>
            <p>No results to show</p>
        <?php endif; ?>
        <?php foreach ($pop_items as $item) : ?>
            <div class="col-lg-2 d-flex align-items-stretch">
                <div class="card bg-light">
                    <div class="card-header" style="text-transform: capitalize;">
                        <?php se($item, "category"); ?>
                    </div>
                    <?php if (se($item, "image", "", false)) : ?>
                        <img src="<?php se($item, "image"); ?>" class="card-img-top" alt="..." height="200">
                    <?php endif; ?>

                    <div class="card-body">
                        <h5 class="card-title"> <?php se($item, "name"); ?></h5>
                        <p class="card-text"><em> <?php se($item, "description"); ?> </em></p>
                    </div>
                    <div class="card-footer">
                        Price: $<?php se($item, "unit_price"); ?> <br />
                        Stock: <?php se($item, "stock"); ?> <br />
                        Rating: <?php se($item, "rating"); ?> stars
                        <form method="GET" action="product_details.php">
                            <button class="btn btn-dark" name="product" value="<?php se($item, "id"); ?>" >Details</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <p></p>
    <h5>Order it again:</h5>
    <div class="row row-cols-1 row-cols-md-5 g-4">
        <?php if (empty($results)) : ?>
            <p>No results to show</p>
        <?php endif; ?>
        <?php foreach ($results as $item) : ?>
            <div class="col-lg-2 d-flex align-items-stretch">
                <div class="card bg-light">
                    <div class="card-header" style="text-transform: capitalize;">
                        <?php se($item, "category"); ?>
                    </div>
                    <?php if (se($item, "image", "", false)) : ?>
                        <img src="<?php se($item, "image"); ?>" class="card-img-top" alt="..." height="200">
                    <?php endif; ?>

                    <div class="card-body">
                        <h5 class="card-title"> <?php se($item, "name"); ?></h5>
                        <p class="card-text"><em> <?php se($item, "description"); ?> </em></p>
                    </div>
                    <div class="card-footer">
                        Price: $<?php se($item, "unit_price"); ?> <br />
                        Stock: <?php se($item, "stock"); ?> <br />
                        Rating: <?php se($item, "rating"); ?> stars
                        <form method="GET" action="product_details.php">
                            <button class="btn btn-dark" name="product" value="<?php se($item, "id"); ?>" >Details</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <p></p>
    <h5><a href="shop.php">View Full Store</a></h5>
    <p></p>
</div>
<?php
    require(__DIR__ . "/../../partials/footer.php");
?>