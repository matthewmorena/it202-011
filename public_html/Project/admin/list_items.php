<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "home.php"));
}

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
    //flash("<pre>" . var_export($e, true) . "</pre>");
    flash("We had some problems processing your request, please try again.", "danger");
}

//Sort and Filters
$col = se($_GET, "col", "unit_price", false);
//allowed list
if (!in_array($col, ["unit_price", "stock", "name", "created", "category", "rating"])) {
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
$base_query = "SELECT id, name, description, unit_price, stock, category, image, rating FROM Products"; //1=1 shortcut to conditionally build AND clauses
$total_query = "SELECT count(*) as total FROM Products";
$stock = se($_GET, "stock", "", false);

if ($stock == "out") {
    $query = " WHERE stock <= 0";
}
elseif ($stock == "in") {
    $query = " WHERE stock > 0";
}
else {
    $stock = "all";
    $query = " WHERE 1=1";
}

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

$per_page = 10;
paginate($total_query . $query, $params, $per_page);

$query .= " LIMIT :offset, :count";
$params[":offset"] = $offset;
$params[":count"] = $per_page;

$stmt = $db->prepare($base_query . $query);
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
?>
<div class="container-fluid">
    <h1>List Items</h1>
    <div class="row">
        <div class="col-auto">
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
                            <option value="rating">Rating</option>
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
                        <div class="input-group-text">Stock</div>
                        <select class="form-control" name="stock" value="<?php se($stock); ?>">
                            <option value="all"> All </option>    
                            <option value="in"> In Stock </option>    
                            <option value="out"> Out of Stock </option>    
                        </select>
                        <script>
                            //quick fix to ensure proper value is selected since
                            //value setting only works after the options are defined and php has the value set prior
                            document.forms[0].stock.value = "<?php se($stock); ?>";
                        </script>
                    </div>
                </div>
                <div class="col">
                    <div class="input-group">
                        <input type="submit" class="btn btn-primary" value="Apply" />
                    </div>
                </div>
            </form>
        </div>
        <div class="col">
            <nav aria-label="Page navigation example">
                <ul class="pagination">
                    <li class="page-item <?php echo ($page - 1) < 1 ? "disabled" : ""; ?>">
                        <a class="page-link" href="?<?php se(persistQueryString($page - 1)); ?>" tabindex="-1">Previous</a>
                    </li>
                    <?php for ($i = 0; $i < $total_pages; $i++) : ?>
                        <li class="page-item <?php echo ($page - 1) == $i ? "active" : ""; ?>"><a class="page-link" href="?<?php se(persistQueryString($i + 1)); ?>"><?php echo ($i + 1); ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page) >= $total_pages ? "disabled" : ""; ?>">
                        <a class="page-link" href="?<?php se(persistQueryString($page + 1)); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php if (count($results) == 0) : ?>
        <p>No results to show</p>
    <?php else : ?>
        <table class="table text-dark">
            <?php foreach ($results as $index => $record) : ?>
                <?php if ($index == 0) : ?>
                    <thead>
                        <?php foreach ($record as $column => $value) : ?>
                            <th><?php se($column); ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </thead>
                <?php endif; ?>
                <tr>
                    <?php foreach ($record as $column => $value) : ?>
                        <?php if ($column == "image") : ?>
                            <td><img src="<?php se($value, null, "N/A"); ?>" height=100px></td>
                        <?php else : ?>
                            <td><?php se($value, null, "N/A"); ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>


                    <td>
                        <a href="edit_item.php?id=<?php se($record, "id"); ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/footer.php");
?>