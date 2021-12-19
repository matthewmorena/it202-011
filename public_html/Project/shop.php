<?php
require(__DIR__ . "/../../partials/nav.php");

$results = [];
$item_categories = [];
$db = getDB();

$get_categories = "SELECT DISTINCT category FROM Products WHERE visibility = 1;";
$liststmt = $db->prepare($get_categories);
try {
    $liststmt->execute();
    $c = $liststmt->fetchAll(PDO::FETCH_ASSOC);
    if ($c) {
        $item_categories = $c;
    }
} catch (PDOException $e) {
    flash("<pre>" . var_export($e, true) . "</pre>");
}

//Sort and Filters
$col = se($_GET, "col", "unit_price", false);
//allowed list
if (!in_array($col, ["unit_price", "stock", "name", "created", "category"])) {
    $col = "unit_price"; //default value, prevent sql injection
}
$order = se($_GET, "order", "asc", false);
//allowed list
if (!in_array($order, ["asc", "desc"])) {
    $order = "asc"; //default value, prevent sql injection
}
$name = se($_GET, "name", "", false);
$category = se($_GET, "category", "", false);
//dynamic query
$query = "SELECT id, name, description, unit_price, stock, category, image FROM Products WHERE stock > 0 AND visibility = 1"; //1=1 shortcut to conditionally build AND clauses
$params = []; //define default params, add keys as needed and pass to execute
//apply name filter
if (!empty($name)) {
    $query .= " AND name like :name";
    $params[":name"] = "%$name%";
}
if (!empty($category)) {
    $query .= " AND category = :category";
    $params[":category"] = "$category";
}
//apply column and order sort
if (!empty($col) && !empty($order)) {
    $query .= " ORDER BY $col $order"; //be sure you trust these values, I validate via the in_array checks above
}
$query .= " LIMIT 10";
$stmt = $db->prepare($query);
//$stmt = $db->prepare("SELECT id, name, description, unit_price, stock, image FROM Products WHERE stock > 0 AND visibility = 1 LIMIT 10");
try {
    $stmt->execute($params);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    flash("<pre>" . var_export($e, true) . "</pre>");
}
?>
<script>
    function purchase(item) {
        console.log("TODO purchase item", item);
        //TODO create JS helper to update all show-balance elements
    }
</script>

<div class="container-fluid">
    <h1>Shop</h1>
    <form class="row row-cols-auto g-3 align-items-center">
        <div class="col">
            <div class="input-group">
                <div class="input-group-text">Name</div>
                <input class="form-control" name="name" value="<?php se($name); ?>" />
            </div>
        </div>
        <div class="col">
            <div class="input-group">
                <div class="input-group-text">Category</div>
                <select class="form-control" name="category" value="<?php se($category); ?>">
                    <option value=""> None </option>    
                    <?php foreach ($item_categories as $cat) : ?>
                        <option value="<?php se($cat["category"]); ?>" style="text-transform: capitalize;"> <?php se($cat["category"]); ?> </option>
                    <?php endforeach ?>
                </select>
                <script>
                    //quick fix to ensure proper value is selected since
                    //value setting only works after the options are defined and php has the value set prior
                    document.forms[0].category.value = "<?php se($category); ?>";
                </script>
            </div>
        </div>
        <div class="col">
            <div class="input-group">
                <div class="input-group-text">Sort</div>
                <!-- make sure these match the in_array filter above-->
                <select class="form-control" name="col" value="<?php se($col); ?>">
                    <option value="unit_price">Price</option>
                    <option value="stock">Stock</option>
                    <option value="name">Name</option>
                    <option value="created">Created</option>
                </select>
                <script>
                    //quick fix to ensure proper value is selected since
                    //value setting only works after the options are defined and php has the value set prior
                    document.forms[0].col.value = "<?php se($col); ?>";
                </script>
                <select class="form-control" name="order" value="<?php se($order); ?>">
                    <option value="asc">Up</option>
                    <option value="desc">Down</option>
                </select>
                <script>
                    //quick fix to ensure proper value is selected since
                    //value setting only works after the options are defined and php has the value set prior
                    document.forms[0].order.value = "<?php se($order); ?>";
                </script>
            </div>
        </div>
        <div class="col">
            <div class="input-group">
                <input type="submit" class="btn btn-primary" value="Apply" />
            </div>
        </div>
    </form>
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
                        Price: $<?php number_format(se($item, "unit_price"), 2, '.', ','); ?> <br />
                        Stock: <?php se($item, "stock"); ?>
                        <form method="POST" action="product_details.php">
                            <button class="btn btn-dark" name="product" value="<?php se($item, "id"); ?>" >Details</button>
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