<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!is_logged_in()) {
    flash("You are not logged in!", "warning");
    die(header("Location: $BASE_PATH" . "login.php"));
}

$results = [];
$db = getDB();

$stmt = $db->prepare("SELECT OrderItems.order_id, OrderItems.product_id, Products.name, Products.unit_price, OrderItems.quantity, OrderItems.user_id
FROM OrderItems LEFT JOIN Products ON OrderItems.product_id = Products.id WHERE OrderItems.order_id = :oid;");
try {
    $stmt->execute([":oid" => $_GET["id"]]);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    //flash("<pre>" . var_export($e, true) . "</pre>");
    flash("We had some problems processing your request, please try again.", "danger");
}
$stmt = $db->prepare("SELECT address, payment_method from Orders WHERE id = :oid");
try {
    $stmt->execute([":oid" => $_GET["id"]]);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $user_info = $r;
    }
} catch (PDOException $e) {
    //flash("<pre>" . var_export($e, true) . "</pre>");
    flash("We had some problems processing your request, please try again.", "danger");
}
$total_cost = 0;
?>
<div class="container-fluid">
    <h1>Order Confirmation</h1>
    <?php if (count($results) == 0) : ?>
        <p>No results to show</p>
    <?php else : ?>
        <table class="table text-dark">
            <?php foreach ($results as $index => $record) : ?>
                <?php if ($index == 0) : ?>
                    <thead>
                        <?php foreach ($record as $column => $value) : ?>
                            <?php if ($column != 'order_id' && $column != 'product_id' && $column != 'user_id') : ?>
                                <th><?php se($column); ?></th>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <th>Subtotal</th>
                    </thead>
                <?php endif; ?>
                <tr>
                    <?php foreach ($record as $column => $value) : ?>
                        <?php if ($column == 'unit_price') : ?>
                            <td>$<?php se($value, null, "N/A"); ?></td>
                        <?php elseif ($column != 'order_id' && $column != 'product_id' && $column != 'user_id') : ?>
                            <td> <?php se($value, null, "N/A"); ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <td> $<?php echo $record['quantity'] * $record['unit_price']; $total_cost += $record['quantity'] * $record['unit_price'];?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td> $<?php echo $total_cost; ?> </td>
            </tr>
        </table>
        <p><strong>Shipping Address:</strong> <?php echo $user_info[0]['address'];?></p>
        <p><strong>Payment Method:</strong> <?php echo $user_info[0]['payment_method'];?></p>
        <a href="all_purchases.php">All Purchase History</a>
    <?php endif; ?>
</div>
<?php
    require_once(__DIR__ . "/../../../partials/footer.php");
?>