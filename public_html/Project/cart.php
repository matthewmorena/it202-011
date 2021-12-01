<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../partials/nav.php");

if (!is_logged_in()) {
    flash("You are not logged in!", "warning");
    die(header("Location: $BASE_PATH" . "login.php"));
}

$results = [];
$user_id = get_user_id();
$db = getDB();

if (isset($_POST['update'])) {
    $quant = $_POST['quantity'];
    $item_id = $_POST['item_id'];
    if ($quant > 0) {
        $stmt = $db->prepare("UPDATE Cart SET quantity = :q WHERE user_id = :uid AND item_id = :iid");
        try {
            $stmt->execute([":q" => $quant, "uid" => $user_id, "iid" => $item_id]);
            $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($r) {
                $results = $r;
            }
        } catch (PDOException $e) {
            flash("<pre>" . var_export($e, true) . "</pre>");
        }
    }
    else {
        $stmt = $db->prepare("DELETE FROM Cart WHERE user_id = :uid AND item_id = :iid");
        try {
            $stmt->execute(["uid" => $user_id, "iid" => $item_id]);
            $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($r) {
                $results = $r;
            }
        } catch (PDOException $e) {
            flash("<pre>" . var_export($e, true) . "</pre>");
        }
    }
}
if (isset($_POST['remove'])) {
    $item_id = $_POST['item_id'];
    $stmt = $db->prepare("DELETE FROM Cart WHERE user_id = :uid AND item_id = :iid");
    try {
        $stmt->execute(["uid" => $user_id, "iid" => $item_id]);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e, true) . "</pre>");
    }
}
if (isset($_POST['clear'])) {
    $stmt = $db->prepare("DELETE FROM Cart WHERE user_id = :uid");
    try {
        $stmt->execute(["uid" => $user_id]);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e, true) . "</pre>");
    }
}

$stmt = $db->prepare("SELECT Products.id, Products.name, Products.description, Products.stock, Products.cost, Cart.quantity FROM Cart LEFT JOIN Products ON Cart.item_id = Products.id WHERE user_id = :name;");
try {
    $stmt->execute([":name" => $user_id]);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    flash("<pre>" . var_export($e, true) . "</pre>");
}

?>
<div class="container-fluid">
    <h1>Your Cart:</h1>
    <?php if (count($results) == 0) : ?>
        <p>Your cart is empty</p>
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
                <?php foreach($record as $column => $value):?>
                    <?php if($column == "quantity"):?>
                        <td><form method="POST">
                            <input type="number" id="quantity" name="quantity" min="1" value="<?php se($value);?>"/>
                            <input type="hidden" name="item_id" value="<?php echo $record['id'];?>"/>
                            <input type="submit" name="update" value="Update" class="btn btn-dark"/>
                        </form><td>
                    <?php else:?>
                        <td><?php se($value);?></td>
                    <?php endif?>
                <?php endforeach;?>
                    <td><form method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $record['id'];?>"/>
                        <input type="submit" name="remove" value="Remove" class="btn btn-dark"/>
                    </form></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <form method="POST">
            <input type="submit" name="clear" value="Clear Cart" class="btn btn-dark"/>
        </form>
    <?php endif; ?>
</div>
<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../partials/footer.php");
?>